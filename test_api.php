<?php
// Fichier de test uniquement — À SUPPRIMER avant la mise en production
//
// Ouvre ce fichier dans le navigateur :
// http://localhost/gtb/test_api.php

$url    = 'http://localhost/gtb/api/mesures.php';
$apiKey = 'ma_cle_secrete_gtb'; // doit correspondre à GTB_API_KEY dans httpd.conf

// Trame simulée comme si c'était l'Arduino qui envoyait
$payload = [
    'id_arduino'  => 'ARD-001',
    'temperature' => 28.5,   // > 27°C → doit déclencher une alerte critical
    'humidite'    => 52.0,
    'co2'         => 1500.0, // > 1400 ppm → doit déclencher une alerte warning
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-GTB-Key: ' . $apiKey,
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test API — GTB</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light p-4">
<div class="container" style="max-width: 700px;">
    <h1 class="h4 fw-bold mb-4">Test endpoint <code>api/mesures.php</code></h1>

    <div class="card mb-4">
        <div class="card-header fw-semibold">Trame envoyée</div>
        <div class="card-body">
            <pre class="mb-0"><?= htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-semibold">
            Réponse HTTP
            <span class="badge ms-2 <?= $httpCode === 200 ? 'bg-success' : 'bg-danger' ?>">
                <?= $httpCode ?>
            </span>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger mb-0">Erreur cURL : <?= htmlspecialchars($error) ?></div>
            <?php else: ?>
                <pre class="mb-0"><?= htmlspecialchars(json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($httpCode === 200 && json_decode($response)?->ok): ?>
        <div class="alert alert-success">
            Succès — Vérifie maintenant dans <a href="alertes.php">Alertes</a> que les alertes ont été générées.
        </div>
    <?php elseif ($httpCode === 401): ?>
        <div class="alert alert-danger">
            Clé API refusée — Vérifie que <code>GTB_API_KEY</code> est bien définie dans <code>httpd.conf</code> et que WAMP a été redémarré.
        </div>
    <?php elseif ($httpCode === 404): ?>
        <div class="alert alert-warning">
            Arduino <code><?= htmlspecialchars($payload['id_arduino']) ?></code> introuvable en base — Vérifie que la table <code>capteurs</code> contient bien cet <code>id_arduino</code>.
        </div>
    <?php elseif ($httpCode === 500): ?>
        <div class="alert alert-danger">
            Erreur serveur — <code>GTB_API_KEY</code> n'est probablement pas configurée dans <code>httpd.conf</code>.
        </div>
    <?php endif; ?>

    <a href="test_api.php" class="btn btn-primary me-2">Renvoyer la trame</a>
    <a href="alertes.php" class="btn btn-outline-secondary">Voir les alertes</a>
</div>
</body>
</html>
