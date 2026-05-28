<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Caméras';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$cameras = [];
try {
    $stmt = $conn->query("
        SELECT c.id, c.nom, c.url_flux, c.camera_status, s.nom AS salle_nom
        FROM cameras c
        JOIN salles s ON s.id = c.id_salle
        ORDER BY s.nom, c.nom
    ");
    $cameras = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // table absente, on affiche vide
}
?>

<main class="container page-shell">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h3 fw-bold mb-4">Caméras</h1>

            <?php if (empty($cameras)): ?>
                <div class="alert alert-warning mb-0" role="alert">
                    Aucune caméra n'est encore configurée.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($cameras as $cam): ?>
                        <div class="col-12 col-md-6 col-xl-4" id="camera-<?= (int)$cam['id'] ?>">
                            <div class="card h-100 shadow-sm">

                                <div class="ratio ratio-16x9 bg-dark">
                                    <?php if (!empty($cam['url_flux'])): ?>
                                        <img
                                            src="<?= htmlspecialchars($cam['url_flux']) ?>"
                                            alt="Flux <?= htmlspecialchars($cam['nom']) ?>"
                                            class="w-100 h-100 object-fit-cover"
                                        >
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center h-100">
                                            <div class="text-white text-center p-3">
                                                <i class="bi bi-camera-video-off fs-1"></i>
                                                <div class="small mt-1">Flux non configuré</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-body d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($cam['nom']) ?></h5>
                                        <p class="card-text text-secondary small mb-0"><?= htmlspecialchars($cam['salle_nom']) ?></p>
                                    </div>
                                    <span class="badge <?= $cam['camera_status'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $cam['camera_status'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
