<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

$alertes    = [];
$erreur     = '';
$succes     = '';
$isAdmin    = ($_SESSION['user_role'] ?? '') === 'admin';
$csrf_token = get_csrf_token();

// --- Changement de statut d'une alerte (admin uniquement) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $erreur = "La session a expiré ou la requête est invalide.";
    } else {
        $alerteId  = (int) ($_POST['alerte_id'] ?? 0);
        $newStatus = (int) ($_POST['new_status'] ?? 0);

        if ($alerteId <= 0 || !in_array($newStatus, [0, 1], true)) {
            $erreur = "Données invalides.";
        } else {
            try {
                if ($newStatus === 1) {
                    $stmt = $conn->prepare(
                        "UPDATE alertes SET is_resolved = 1, resolved_at = NOW() WHERE id = :id"
                    );
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE alertes SET is_resolved = 0, resolved_at = NULL WHERE id = :id"
                    );
                }
                $stmt->execute([':id' => $alerteId]);
                $succes = "Statut de l'alerte mis à jour.";
            } catch (PDOException $e) {
                $erreur = "Une erreur est survenue lors de la mise à jour.";
            }
        }
    }
}

try {
    $query = $conn->query("
        SELECT
            a.id,
            a.type_alerte,
            a.message,
            a.valeur_declencheur,
            a.seuil,
            a.niveau,
            a.is_resolved,
            a.created_at,
            a.resolved_at,
            c.type AS capteur_type,
            s.nom  AS salle_nom
        FROM alertes a
        JOIN capteurs c ON c.id = a.id_capteur
        JOIN salles  s ON s.id = c.id_salle
        ORDER BY a.is_resolved ASC, a.created_at DESC
    ");
    $alertes = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erreur = "Impossible de récupérer les alertes.";
}

$pageTitle = 'Alertes';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container page-shell">
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 fw-bold mb-2">Alertes</h1>
                    <p class="text-secondary mb-0">
                        Liste des alertes déclenchées par les capteurs du système.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <?php if (!empty($succes)): ?>
                        <div class="alert alert-success" role="alert">
                            <?= htmlspecialchars($succes) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($erreur)): ?>
                        <div class="alert alert-danger mb-0" role="alert">
                            <?= htmlspecialchars($erreur) ?>
                        </div>

                    <?php elseif (empty($alertes)): ?>
                        <div class="alert alert-warning mb-0" role="alert">
                            Aucune alerte active pour le moment.
                        </div>

                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($alertes as $alerte): ?>
                                <?php
                                // match() permet de choisir la couleur Bootstrap selon le niveau de l'alerte
                                // c'est comme un switch mais en plus court
                                $niveauClass = match ($alerte['niveau']) {
                                    'critical' => 'danger',
                                    'info'     => 'primary',
                                    default    => 'warning', // warning si le niveau est pas reconnu
                                };
                                $resolved = (int) $alerte['is_resolved'] === 1;
                                ?>
                                <div class="list-group-item <?= $resolved ? 'opacity-50' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge text-bg-<?= $niveauClass ?>">
                                                    <?= htmlspecialchars(ucfirst($alerte['niveau'])) ?>
                                                </span>
                                                <h2 class="h6 fw-bold mb-0">
                                                    <?= htmlspecialchars($alerte['type_alerte']) ?>
                                                </h2>
                                            </div>

                                            <p class="text-secondary mb-1">
                                                <?= htmlspecialchars($alerte['message']) ?>
                                            </p>

                                            <p class="text-secondary small mb-0">
                                                Capteur : <?= htmlspecialchars($alerte['capteur_type']) ?>
                                                &mdash;
                                                Salle : <?= htmlspecialchars($alerte['salle_nom']) ?>
                                                <?php if ($alerte['valeur_declencheur'] !== null): ?>
                                                    &mdash;
                                                    Valeur : <strong><?= htmlspecialchars((string) $alerte['valeur_declencheur']) ?></strong>
                                                <?php endif; ?>
                                                <?php if ($alerte['seuil'] !== null): ?>
                                                    &mdash;
                                                    Seuil : <?= htmlspecialchars((string) $alerte['seuil']) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>

                                        <div class="text-end d-flex flex-column align-items-end gap-2">
                                            <div>
                                                <?php if ($resolved): ?>
                                                    <span class="badge text-bg-success">Résolue</span>
                                                    <?php if ($alerte['resolved_at']): ?>
                                                        <p class="text-secondary small mb-0 mt-1">
                                                            le <?= htmlspecialchars($alerte['resolved_at']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge text-bg-danger">Active</span>
                                                <?php endif; ?>
                                                <p class="text-secondary small mb-0 mt-1">
                                                    <?= htmlspecialchars($alerte['created_at']) ?>
                                                </p>
                                            </div>

                                            <?php if ($isAdmin): ?>
                                                <form method="post" action="">
                                                    <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf_token) ?>">
                                                    <input type="hidden" name="alerte_id"   value="<?= (int) $alerte['id'] ?>">
                                                    <input type="hidden" name="new_status"  value="<?= $resolved ? '0' : '1' ?>">
                                                    <button type="submit" class="btn btn-sm <?= $resolved ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                                                        <?= $resolved ? 'Marquer comme active' : 'Marquer comme résolue' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>