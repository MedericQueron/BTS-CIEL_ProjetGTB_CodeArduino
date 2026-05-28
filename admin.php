<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

ensure_session_started();
require_admin();

$erreur  = '';
$succes  = '';
$csrf_token = get_csrf_token();

// Lecture du message flash éventuel (ex. : retour depuis register.php)
$flash = get_flash_message('success');

// --- Traitement des formulaires POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $erreur = "La session a expiré ou la requête est invalide.";
    } else {
        $action = $_POST['action'] ?? 'create';

        // --- Suppression d'un utilisateur ---
        if ($action === 'delete') {
            $targetId = (int) ($_POST['user_id'] ?? 0);

            if ($targetId <= 0) {
                $erreur = "Identifiant utilisateur invalide.";
            } elseif ($targetId === (int) $_SESSION['user_id']) {
                $erreur = "Vous ne pouvez pas supprimer votre propre compte.";
            } else {
                // Empêcher la suppression du dernier administrateur
                $stmtCount = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                $stmtCount->execute();
                $adminCount = (int) $stmtCount->fetchColumn();

                $stmtRole = $conn->prepare("SELECT role FROM users WHERE id = :id");
                $stmtRole->execute([':id' => $targetId]);
                $targetRole = $stmtRole->fetchColumn();

                if ($targetRole === 'admin' && $adminCount <= 1) {
                    $erreur = "Impossible de supprimer le dernier compte administrateur.";
                } else {
                    try {
                        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
                        $stmt->execute([':id' => $targetId]);
                        $succes = "Compte supprimé avec succès.";
                    } catch (PDOException $e) {
                        $erreur = "Une erreur est survenue lors de la suppression.";
                    }
                }
            }
        }

        // --- Création d'un utilisateur ---
        elseif ($action === 'create') {
            $username         = trim($_POST['username'] ?? '');
            $email            = trim($_POST['email'] ?? '');
            $password         = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $role             = $_POST['role'] ?? 'user';

            if ($username === '' || $email === '' || $password === '' || $confirm_password === '') {
                $erreur = "Tous les champs sont obligatoires.";
            } elseif ($password !== $confirm_password) {
                $erreur = "Les mots de passe ne correspondent pas.";
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
                $erreur = "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreur = "L'adresse email n'est pas valide.";
            } elseif (!in_array($role, ['admin', 'user'], true)) {
                $erreur = "Rôle invalide.";
            }

            if ($erreur === '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $stmt = $conn->prepare(
                        "INSERT INTO users (username, email, passwrd, role) VALUES (:username, :email, :passwrd, :role)"
                    );
                    $stmt->execute([
                        ':username' => $username,
                        ':email'    => $email,
                        ':passwrd'  => $hash,
                        ':role'     => $role,
                    ]);
                    $succes = "Compte de « " . htmlspecialchars($username) . " » créé avec succès.";
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $erreur = "Cette adresse email est déjà utilisée.";
                    } else {
                        $erreur = "Une erreur est survenue, veuillez réessayer.";
                    }
                }
            }
        }
    }
}

// --- Récupération de la liste des utilisateurs ---
$users = [];
try {
    $users = $conn->query("SELECT id, username, email, role FROM users ORDER BY id ASC")
                  ->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // table inaccessible, on affiche vide
}

$pageTitle = 'Administration';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container page-shell">
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 fw-bold mb-1">Administration</h1>
                    <p class="text-secondary mb-0">Gestion des comptes utilisateurs du système GTB.</p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <!-- Onglets Bootstrap -->
            <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-liste-btn" data-bs-toggle="tab"
                            data-bs-target="#tab-liste" type="button" role="tab">
                        Utilisateurs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= ($erreur !== '') ? 'active' : '' ?>" id="tab-creer-btn"
                            data-bs-toggle="tab" data-bs-target="#tab-creer" type="button" role="tab">
                        Créer un compte
                    </button>
                </li>
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom bg-white p-4 shadow-sm">

                <!-- Onglet : liste des utilisateurs -->
                <div class="tab-pane fade <?= ($erreur === '') ? 'show active' : '' ?>"
                     id="tab-liste" role="tabpanel">

                    <?php if ($flash): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
                    <?php endif; ?>
                    <?php if ($succes !== ''): ?>
                        <div class="alert alert-success"><?= $succes ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nom d'utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-4">
                                            Aucun utilisateur trouvé.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td class="text-secondary"><?= (int) $u['id'] ?></td>
                                            <td class="fw-semibold"><?= htmlspecialchars($u['username']) ?></td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td>
                                                <?php if ($u['role'] === 'admin'): ?>
                                                    <span class="badge text-bg-primary">Admin</span>
                                                <?php else: ?>
                                                    <span class="badge text-bg-secondary">Utilisateur</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ((int) $u['id'] !== (int) $_SESSION['user_id']): ?>
                                                    <form method="post" action=""
                                                          onsubmit="return confirm('Supprimer le compte de « <?= htmlspecialchars($u['username'], ENT_QUOTES) ?> » ? Cette action est irréversible.');">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                        <input type="hidden" name="action"    value="delete">
                                                        <input type="hidden" name="user_id"   value="<?= (int) $u['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            Supprimer
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-secondary small">Votre compte</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Onglet : création de compte -->
                <div class="tab-pane fade <?= ($erreur !== '') ? 'show active' : '' ?>"
                     id="tab-creer" role="tabpanel">

                    <?php if ($erreur !== ''): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
                    <?php endif; ?>

                    <form action="" method="post" class="row g-3" style="max-width: 480px;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="action"    value="create">

                        <div class="col-12">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="col-12">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <input type="password" class="form-control password-input" id="password"
                                       name="password" required>
                                <button class="btn btn-outline-secondary password-toggle" type="button">Afficher</button>
                            </div>
                            <div class="form-text">Minimum 8 caractères, une majuscule et un chiffre.</div>
                        </div>

                        <div class="col-12">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                            <div class="input-group">
                                <input type="password" class="form-control password-input" id="confirm_password"
                                       name="confirm_password" required>
                                <button class="btn btn-outline-secondary password-toggle" type="button">Afficher</button>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="role" class="form-label">Rôle</label>
                            <select class="form-select" id="role" name="role">
                                <option value="user" <?= (($_POST['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>Utilisateur</option>
                                <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrateur</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary fw-bold py-2 px-4">
                                Créer le compte
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</main>

<script>
    document.querySelectorAll('.password-toggle').forEach(function(button) {
        button.addEventListener('click', function() {
            const input = button.closest('.input-group').querySelector('.password-input');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Masquer' : 'Afficher';
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
