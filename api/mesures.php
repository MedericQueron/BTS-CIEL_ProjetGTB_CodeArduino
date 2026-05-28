<?php
// endpoint qui recoit les trames de l'arduino via HTTP POST
// header requis : X-GTB-Key avec la cle API
// body : JSON avec id_arduino + les mesures du capteur

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// definition des types de mesures avec les bons accords de genre
// et les unites pour les messages d'alerte
$typesMesures = [
    'temperature' => [
        'label'    => 'Température',
        'unite'    => '°C',
        'nom_haut' => 'Température trop élevée',
        'nom_bas'  => 'Température trop basse',
    ],
    'humidite' => [
        'label'    => 'Humidité',
        'unite'    => '%',
        'nom_haut' => 'Humidité trop élevée',
        'nom_bas'  => 'Humidité trop basse',
    ],
    'co2' => [
        'label'    => 'CO₂',
        'unite'    => 'ppm',
        'nom_haut' => 'CO₂ trop élevé',   // masculin
        'nom_bas'  => 'CO₂ trop bas',
    ],
    'luminosite' => [
        'label'    => 'Luminosité',
        'unite'    => 'lux',
        'nom_haut' => 'Luminosité trop élevée',
        'nom_bas'  => 'Luminosité trop basse',
    ],
];

// 1. verif methode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

// 2. verif cle API
$apiKey = getenv('GTB_API_KEY');

if ($apiKey === false || $apiKey === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'GTB_API_KEY non configurée côté serveur.']);
    exit;
}

$receivedKey = $_SERVER['HTTP_X_GTB_KEY'] ?? '';

if (!hash_equals($apiKey, $receivedKey)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Clé API invalide.']);
    exit;
}

// 3. decodage du JSON
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Corps de la requête JSON invalide.']);
    exit;
}

$idArduino = trim($data['id_arduino'] ?? '');

if ($idArduino === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Champ id_arduino manquant.']);
    exit;
}

// 4. on cherche le capteur avec son id_arduino
try {
    $stmt = $conn->prepare("SELECT id FROM capteurs WHERE id_arduino = :id_arduino LIMIT 1");
    $stmt->execute([':id_arduino' => $idArduino]);
    $capteur = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur base de données.']);
    exit;
}

if (!$capteur) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => "Arduino « $idArduino » inconnu."]);
    exit;
}

$idCapteur = (int) $capteur['id'];

// 5. insertion des mesures et verification des seuils
$inserted = 0;

try {
    $stmtInsert = $conn->prepare(
        "INSERT INTO mesures (id_capteur, type_mesure, valeur) VALUES (:id_capteur, :type_mesure, :valeur)"
    );

    // on recupere les seuils du capteur en une seule requete
    $stmtSeuils = $conn->prepare(
        "SELECT type_mesure, valeur_min, valeur_max, niveau FROM seuils WHERE id_capteur = :id_capteur"
    );
    $stmtSeuils->execute([':id_capteur' => $idCapteur]);
    $seuilsParType = [];
    foreach ($stmtSeuils->fetchAll(PDO::FETCH_ASSOC) as $s) {
        $seuilsParType[$s['type_mesure']] = $s;
    }

    foreach ($typesMesures as $typeCle => $info) {
        if (!array_key_exists($typeCle, $data)) {
            continue; // pas dans la trame, on skip
        }

        $valeur = filter_var($data[$typeCle], FILTER_VALIDATE_FLOAT);
        if ($valeur === false) {
            continue; // valeur pas numerique, on ignore
        }

        // insertion de la mesure
        $stmtInsert->execute([
            ':id_capteur'  => $idCapteur,
            ':type_mesure' => $typeCle,
            ':valeur'      => $valeur,
        ]);
        $inserted++;

        // pas de seuil defini pour ce type, on passe a la suite
        if (!isset($seuilsParType[$typeCle])) {
            continue;
        }

        $seuil      = $seuilsParType[$typeCle];
        $horsLimite = false;
        $nomAlerte  = '';
        $message    = '';
        $seuilRef   = null;

        if ($seuil['valeur_max'] !== null && $valeur > (float) $seuil['valeur_max']) {
            $horsLimite = true;
            $nomAlerte  = $info['nom_haut'];
            $seuilRef   = $seuil['valeur_max'];
            $message    = "{$info['label']} relevée : {$valeur} {$info['unite']}, seuil maximum dépassé ({$seuil['valeur_max']} {$info['unite']}).";
        } elseif ($seuil['valeur_min'] !== null && $valeur < (float) $seuil['valeur_min']) {
            $horsLimite = true;
            $nomAlerte  = $info['nom_bas'];
            $seuilRef   = $seuil['valeur_min'];
            $message    = "{$info['label']} relevée : {$valeur} {$info['unite']}, en dessous du seuil minimum ({$seuil['valeur_min']} {$info['unite']}).";
        }

        if ($horsLimite) {
            // on verifie si une alerte identique est déja active pour eviter les doublons
            $stmtCheck = $conn->prepare(
                "SELECT id FROM alertes
                 WHERE id_capteur = :id_capteur AND type_alerte = :type_alerte AND is_resolved = 0
                 LIMIT 1"
            );
            $stmtCheck->execute([':id_capteur' => $idCapteur, ':type_alerte' => $nomAlerte]);

            if (!$stmtCheck->fetch()) {
                $conn->prepare(
                    "INSERT INTO alertes (id_capteur, type_alerte, message, valeur_declencheur, seuil, niveau)
                     VALUES (:id_capteur, :type_alerte, :message, :valeur, :seuil, :niveau)"
                )->execute([
                    ':id_capteur'  => $idCapteur,
                    ':type_alerte' => $nomAlerte,
                    ':message'     => $message,
                    ':valeur'      => $valeur,
                    ':seuil'       => $seuilRef,
                    ':niveau'      => $seuil['niveau'],
                ]);
            }
        } else {
            // valeur revenue dans les limites, on resout les alertes actives du meme type
            $conn->prepare(
                "UPDATE alertes
                 SET is_resolved = 1, resolved_at = NOW()
                 WHERE id_capteur = :id_capteur
                   AND (type_alerte = :nom_haut OR type_alerte = :nom_bas)
                   AND is_resolved = 0"
            )->execute([
                ':id_capteur' => $idCapteur,
                ':nom_haut'   => $info['nom_haut'],
                ':nom_bas'    => $info['nom_bas'],
            ]);
        }
    }

    // 6. on marque le capteur comme connecte
    $conn->prepare("UPDATE capteurs SET is_connected = 1 WHERE id = :id")
         ->execute([':id' => $idCapteur]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => "Erreur lors de l'insertion."]);
    exit;
}

echo json_encode(['ok' => true, 'inserted' => $inserted]);
