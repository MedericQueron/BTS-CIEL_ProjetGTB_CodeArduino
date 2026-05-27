<?php
// Page de détail d'une salle : capteurSs, mesures et caméras
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

$salleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$salleId || $salleId < 1) {
    die('Identifiant de salle invalide.');
}

$req = $conn->prepare("SELECT id, nom, type, open_for_all FROM salles WHERE id = :id");
$req->execute([':id' => $salleId]);
$salle = $req->fetch(PDO::FETCH_ASSOC); // Retourne une seule ligne (la salle)

// Arrêt si l'ID ne correspond à aucune salle en base
if (!$salle) {
    die('Salle introuvable.');
}

// Récupère tous les capteurs liés à la salle, triés alphabétiquement par type
$req = $conn->prepare("
    SELECT id, type, unite, id_arduino, is_connected
    FROM capteurs
    WHERE id_salle = :id_salle
    ORDER BY type ASC
");
$req->execute([':id_salle' => $salleId]);
$capteurs = $req->fetchAll(PDO::FETCH_ASSOC); // Retourne toutes les lignes (plusieurs capteurs possibles)

$capteurStatsParType   = [];
$capteurMesuresParType = [];

foreach ($capteurs as $capteur) {
    $cid = (int) $capteur['id'];

    // Statistiques groupées par type de mesure
    $req = $conn->prepare("
        SELECT
            type_mesure,
            COUNT(*)                AS total,
            ROUND(MIN(valeur), 2)   AS valeur_min,
            ROUND(MAX(valeur), 2)   AS valeur_max,
            ROUND(AVG(valeur), 2)   AS valeur_moyenne
        FROM mesures
        WHERE id_capteur = :id_capteur
        GROUP BY type_mesure
        ORDER BY type_mesure ASC
    ");
    $req->execute([':id_capteur' => $cid]);
    $stats = $req->fetchAll(PDO::FETCH_ASSOC);

    // Indexer les stats par type_mesure
    $capteurStatsParType[$cid] = [];
    foreach ($stats as $s) {
        $capteurStatsParType[$cid][$s['type_mesure']] = $s;
    }

    // 10 dernières mesures groupées par type_mesure
    $req = $conn->prepare("
        SELECT type_mesure, ROUND(valeur, 2) AS valeur, created_at
        FROM mesures
        WHERE id_capteur = :id_capteur
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $req->execute([':id_capteur' => $cid]);
    $mesures = $req->fetchAll(PDO::FETCH_ASSOC);

    // Indexer les mesures par type_mesure (max 10 par type)
    $capteurMesuresParType[$cid] = [];
    foreach ($mesures as $m) {
        $t = $m['type_mesure'];
        if (!isset($capteurMesuresParType[$cid][$t])) {
            $capteurMesuresParType[$cid][$t] = [];
        }
        if (count($capteurMesuresParType[$cid][$t]) < 10) {
            $capteurMesuresParType[$cid][$t][] = $m;
        }
    }
}

$req = $conn->prepare("
    SELECT id, nom, url_flux, camera_status
    FROM cameras
    WHERE id_salle = :id_salle
    ORDER BY id ASC
");
$req->execute([':id_salle' => $salleId]);
$cameras = $req->fetchAll(PDO::FETCH_ASSOC);

// Mapping unité par type de mesure
$uniteParType = [
    'temperature' => '°C',
    'humidite'    => '%',
    'co2'         => 'ppm',
    'luminosite'  => 'lux',
];

// Labels lisibles pour les types de mesure
$labelParType = [
    'temperature' => 'Température',
    'humidite'    => 'Humidité',
    'co2'         => 'CO₂',
    'luminosite'  => 'Luminosité',
];

$pageTitle = 'Salle - ' . $salle['nom'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container page-shell">
    <div class="row g-4">

        <!-- En-tête de la salle -->
        <div class="col-12">
            <a href="salles.php" class="btn btn-outline-primary btn-sm mb-3">Retour aux salles</a>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <h1 class="h3 fw-bold mb-1"><?= htmlspecialchars($salle['nom']) ?></h1>
                            <p class="text-secondary mb-2">Type : <?= htmlspecialchars($salle['type']) ?></p>
                            <?php if ((int) $salle['open_for_all'] === 1): ?>
                                <span class="badge text-bg-success">Ouverte à tous</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Accès limité</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-secondary small mb-0">Actualisation automatique : 30 s</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section capteurs -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Capteurs et mesures</h2>

                    <?php if (empty($capteurs)): ?>
                        <div class="alert alert-info mb-0">Aucun capteur rattaché à cette salle.</div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($capteurs as $capteur): ?>
                                <?php
                                $cid           = (int) $capteur['id'];
                                $statsParType  = $capteurStatsParType[$cid]  ?? [];
                                $mesuresParType = $capteurMesuresParType[$cid] ?? [];
                                $typesDisponibles = array_unique(array_merge(
                                    array_keys($statsParType),
                                    array_keys($mesuresParType)
                                ));
                                sort($typesDisponibles);
                                $premierType = $typesDisponibles[0] ?? null;
                                ?>
                                <div class="col-12">
                                    <div class="border rounded p-3">

                                        <!-- En-tête du capteur -->
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                            <div>
                                                <h3 class="h6 fw-bold mb-1"><?= htmlspecialchars($capteur['type']) ?></h3>
                                                <p class="text-secondary small mb-0">
                                                    Arduino : <?= htmlspecialchars((string) $capteur['id_arduino']) ?>
                                                </p>
                                            </div>
                                            <?php if ((int) $capteur['is_connected'] === 1): ?>
                                                <span class="badge text-bg-success">Connecté</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-danger">Déconnecté</span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (empty($typesDisponibles)): ?>
                                            <div class="alert alert-info mb-0">Aucune mesure disponible pour ce capteur.</div>
                                        <?php else: ?>

                                            <!-- Menu déroulant de sélection du type de mesure -->
                                            <div class="mb-3">
                                                <label for="select-capteur-<?= $cid ?>" class="form-label fw-semibold text-secondary small mb-1">
                                                    <i class="bi bi-sliders me-1"></i>Type de mesure
                                                </label>
                                                <select
                                                    class="form-select form-select-sm w-auto"
                                                    id="select-capteur-<?= $cid ?>"
                                                    onchange="afficherMesure(<?= $cid ?>, this.value)"
                                                >
                                                    <?php foreach ($typesDisponibles as $type): ?>
                                                        <option value="<?= htmlspecialchars($type) ?>">
                                                            <?= htmlspecialchars($labelParType[$type] ?? ucfirst($type)) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Sections de données par type de mesure -->
                                            <?php foreach ($typesDisponibles as $type): ?>
                                                <?php
                                                $unite   = $uniteParType[$type] ?? $capteur['unite'];
                                                $label   = $labelParType[$type] ?? ucfirst($type);
                                                $stat    = $statsParType[$type]   ?? null;
                                                $mesures = $mesuresParType[$type] ?? [];
                                                $estVisible = ($type === $premierType);
                                                ?>
                                                <div
                                                    id="section-<?= $cid ?>-<?= htmlspecialchars($type) ?>"
                                                    class="section-mesure-capteur"
                                                    style="display: <?= $estVisible ? 'block' : 'none' ?>;"
                                                >
                                                    <!-- Statistiques -->
                                                    <?php if ($stat): ?>
                                                        <h4 class="h6 text-secondary mb-2">
                                                            Statistiques — <?= htmlspecialchars($label) ?>
                                                        </h4>
                                                        <div class="table-responsive mb-3">
                                                            <table class="table table-sm table-bordered align-middle mb-0">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Total relevés</th>
                                                                        <th>Min</th>
                                                                        <th>Max</th>
                                                                        <th>Moyenne</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td><?= (int) $stat['total'] ?></td>
                                                                        <td><?= htmlspecialchars((string) $stat['valeur_min']) ?> <?= htmlspecialchars($unite) ?></td>
                                                                        <td><?= htmlspecialchars((string) $stat['valeur_max']) ?> <?= htmlspecialchars($unite) ?></td>
                                                                        <td><?= htmlspecialchars((string) $stat['valeur_moyenne']) ?> <?= htmlspecialchars($unite) ?></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- 10 dernières mesures -->
                                                    <?php if (!empty($mesures)): ?>
                                                        <h4 class="h6 text-secondary mb-2">
                                                            10 dernières mesures — <?= htmlspecialchars($label) ?>
                                                        </h4>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered align-middle mb-0">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Valeur</th>
                                                                        <th>Date</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($mesures as $m): ?>
                                                                        <tr>
                                                                            <td><?= htmlspecialchars((string) $m['valeur']) ?> <?= htmlspecialchars($unite) ?></td>
                                                                            <td><?= htmlspecialchars((string) $m['created_at']) ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>

                                                </div><!-- /section-<?= $cid ?>-<?= htmlspecialchars($type) ?> -->

                                            <?php endforeach; ?>

                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Section caméras -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Caméras installées</h2>

                    <?php if (empty($cameras)): ?>
                        <div class="alert alert-info mb-0">Aucune caméra rattachée à cette salle.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($cameras as $camera): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <h3 class="h6 fw-bold mb-1"><?= htmlspecialchars($camera['nom']) ?></h3>
                                            <?php if (!empty($camera['url_flux'])): ?>
                                                <a href="<?= htmlspecialchars($camera['url_flux']) ?>" target="_blank" rel="noopener" class="link-primary">
                                                    Ouvrir le flux
                                                </a>
                                            <?php else: ?>
                                                <p class="text-secondary mb-0">Aucun flux renseigné.</p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ((int) $camera['camera_status'] === 1): ?>
                                            <span class="badge text-bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</main>

<script>
    /**
     * Affiche uniquement la section correspondant au type de mesure sélectionné
     * pour le capteur donné, et masque les autres.
     *
     * @param {number} capteurId  - Identifiant du capteur
     * @param {string} type       - Type de mesure sélectionné (ex: 'temperature')
     */
    function afficherMesure(capteurId, type) {
        // Masquer toutes les sections de ce capteur
        document.querySelectorAll(`[id^="section-${capteurId}-"]`).forEach(function (el) {
            el.style.display = 'none';
        });

        // Afficher la section du type sélectionné
        var cible = document.getElementById('section-' + capteurId + '-' + type);
        if (cible) {
            cible.style.display = 'block';
        }
    }

    // Actualisation automatique toutes les 30 secondes
    setTimeout(function () {
        window.location.reload();
    }, 30000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
