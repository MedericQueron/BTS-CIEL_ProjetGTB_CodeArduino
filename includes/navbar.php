<?php
// Barre de navigation commune des pages connectées
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/database.php';

// Token utilisé pour sécuriser le bouton de déconnexion
$logout_token = get_csrf_token();
// Permet de savoir quelle page est active dans le menu
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$username = 'Utilisateur';

// On récupère le nom de l'utilisateur pour l'afficher dans le menu
if (!empty($_SESSION['user_id'])) {
    try {
        $query = $conn->prepare("SELECT username FROM users WHERE id = :id");
        $query->execute([':id' => $_SESSION['user_id']]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        if (!empty($user['username'])) {
            $username = $user['username'];
        }
    } catch (PDOException $e) {
        $username = 'Utilisateur';
    }
}

// Liste des liens du menu, comme ça c'est plus facile à changer
$navItems = [
    'dashboard.php' => 'Tableau de bord',
    'salles.php' => 'Salles',
    'alertes.php' => 'Alertes',
];

$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';


// Recupere les 10 dernieres alertes non résolues pour la cloche
$navAlertes = [];
$navAlertesCount = 0;
try {
    $navQuery = $conn->query("
        SELECT
            a.id,
            a.type_alerte,
            a.niveau,
            a.created_at,
            c.type  AS capteur_type,
            s.nom   AS salle_nom
        FROM alertes a
        JOIN capteurs c ON c.id = a.id_capteur
        JOIN salles   s ON s.id = c.id_salle
        WHERE a.is_resolved = 0
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $navAlertes = $navQuery->fetchAll(PDO::FETCH_ASSOC);
    $navAlertesCount = count($navAlertes);
} catch (PDOException $e) {
    // si la table n'existe pas encore on affiche juste rien
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <!-- Nom du projet qui renvoie au tableau de bord -->
        <a class="navbar-brand fw-bold" href="dashboard.php">GTB</a>

        <!-- Bouton affiché sur téléphone pour ouvrir le menu -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Afficher le menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- Boucle sur les liens pour éviter de recopier plein de HTML -->
                <?php foreach ($navItems as $href => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === $href ? 'active fw-semibold' : '' ?>" href="<?= htmlspecialchars($href) ?>">
                            <?= htmlspecialchars($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>

                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'cameras.php' ? 'active fw-semibold' : '' ?>" href="cameras.php">
                        Caméras
                    </a>
                </li>

                <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'admin.php' ? 'active fw-semibold' : '' ?>" href="admin.php">
                            Administration
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

<!-- Cloche de notifications + menu utilisateur -->
            <ul class="navbar-nav ms-auto align-items-center gap-2">

                <!-- Cloche avec badge et dropdown des alertes actives -->
                <li class="nav-item dropdown">
                    <button class="btn btn-outline-light btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <?php if ($navAlertesCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $navAlertesCount ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width: 320px; max-height: 400px; overflow-y: auto;">
                        <!-- En-tête du dropdown -->
                        <li class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <span class="fw-semibold">Notifications</span>
                            <?php if ($navAlertesCount > 0): ?>
                                <span class="badge bg-danger"><?= $navAlertesCount ?></span>
                            <?php endif; ?>
                        </li>

                        <?php if (empty($navAlertes)): ?>
                            <li class="px-3 py-3 text-secondary small">Aucune notification active</li>
                        <?php else: ?>
                            <?php foreach ($navAlertes as $navAlerte): ?>
                                <?php
                                $badgeClass = match ($navAlerte['niveau']) {
                                    'critical' => 'danger',
                                    'info'     => 'primary',
                                    default    => 'warning',
                                };
                                ?>
                                <li>
                                    <a class="dropdown-item py-2 border-bottom" href="alertes.php">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="badge text-bg-<?= $badgeClass ?> mt-1 flex-shrink-0"><?= htmlspecialchars(ucfirst($navAlerte['niveau'])) ?></span>
                                            <div class="overflow-hidden">
                                                <div class="fw-semibold small text-truncate"><?= htmlspecialchars($navAlerte['type_alerte']) ?></div>
                                                <div class="text-secondary" style="font-size: .75rem;"><?= htmlspecialchars($navAlerte['salle_nom']) ?> — <?= htmlspecialchars($navAlerte['capteur_type']) ?></div>
                                                <div class="text-secondary" style="font-size: .7rem;"><?= htmlspecialchars($navAlerte['created_at']) ?></div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Lien vers la page complète -->
                        <li class="px-3 py-2 text-center border-top">
                            <a href="alertes.php" class="small text-primary">Voir toutes les alertes</a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= htmlspecialchars($username) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <form action="logout.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($logout_token) ?>">
                                <button type="submit" class="dropdown-item">Se déconnecter</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

