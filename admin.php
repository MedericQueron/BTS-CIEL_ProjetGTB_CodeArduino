<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

ensure_session_started();
require_admin();

$erreur      = '';
$succes      = '';
$erreurSeuil = '';
$succesSeuil = '';
$csrf_token  = get_csrf_token();

// Lecture du message flash éventuel
$flash = get_flash_message();

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

        // --- Suppression d'un seuil ---
        elseif ($action === 'delete_seuil') {
            $seuilId = (int) ($_POST['seuil_id'] ?? 0);
            if ($seuilId <= 0) {
                $erreurSeuil = "Identifiant de seuil invalide.";
            } else {
                try {
                    $conn->prepare("DELETE FROM seuils WHERE id = :id")->execute([':id' => $seuilId]);
                    $succesSeuil = "Seuil supprimé.";
                } catch (PDOException $e) {
                    $erreurSeuil = "Erreur lors de la suppression.";
                }
            }
        }

        // --- Création / mise à jour d'un seuil ---
        elseif ($action === 'create_seuil') {
            $idCapteur  = (int) ($_POST['id_capteur'] ?? 0);
            $typeMesure = $_POST['type_mesure'] ?? '';
            $valeurMin  = ($_POST['valeur_min'] ?? '') !== '' ? filter_var($_POST['valeur_min'], FILTER_VALIDATE_FLOAT) : null;
            $valeurMax  = ($_POST['valeur_max'] ?? '') !== '' ? filter_var($_POST['valeur_max'], FILTER_VALIDATE_FLOAT) : null;
            $niveau     = $_POST['niveau'] ?? 'warning';

            $typesMesuresValides = ['temperature', 'humidite', 'co2', 'luminosite'];

            if ($idCapteur <= 0 || !in_array($typeMesure, $typesMesuresValides, true) || !in_array($niveau, ['info', 'avertissement', 'critique'], true)) {
                $erreurSeuil = "Données invalides.";
            } elseif ($valeurMin === null && $valeurMax === null) {
                $erreurSeuil = "Au moins une limite (min ou max) est requise.";
            } else {
                try {
                    $conn->prepare(
                        "INSERT INTO seuils (id_capteur, type_mesure, valeur_min, valeur_max, niveau)
                         VALUES (:id_capteur, :type_mesure, :valeur_min, :valeur_max, :niveau)
                         ON DUPLICATE KEY UPDATE
                             valeur_min = VALUES(valeur_min),
                             valeur_max = VALUES(valeur_max),
                             niveau     = VALUES(niveau)"
                    )->execute([
                        ':id_capteur'  => $idCapteur,
                        ':type_mesure' => $typeMesure,
                        ':valeur_min'  => $valeurMin,
                        ':valeur_max'  => $valeurMax,
                        ':niveau'      => $niveau,
                    ]);
                    $succesSeuil = "Seuil enregistré avec succès.";
                } catch (PDOException $e) {
                    $erreurSeuil = "Erreur lors de l'enregistrement.";
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

// --- Capteurs pour le select du formulaire seuils ---
$capteursPourSelect = [];
try {
    $capteursPourSelect = $conn->query(
        "SELECT c.id, c.type, s.nom AS salle_nom
         FROM capteurs c
         JOIN salles s ON s.id = c.id_salle
         ORDER BY s.nom, c.type"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// --- Liste des seuils existants ---
$seuils = [];
try {
    $seuils = $conn->query(
        "SELECT se.id, se.type_mesure, se.valeur_min, se.valeur_max, se.niveau,
                c.type AS capteur_type, sa.nom AS salle_nom
         FROM seuils se
         JOIN capteurs c  ON c.id  = se.id_capteur
         JOIN salles   sa ON sa.id = c.id_salle
         ORDER BY sa.nom, c.type, se.type_mesure"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

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
            <?php
            // Détermine quel onglet afficher après un POST
            $activeTab = 'liste';
            if ($erreur !== '')      $activeTab = 'creer';
            if ($erreurSeuil !== '') $activeTab = 'seuils';
            if ($succesSeuil !== '') $activeTab = 'seuils';
            ?>

            <!-- Onglets Bootstrap -->
            <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'liste' ? 'active' : '' ?>" id="tab-liste-btn"
                            data-bs-toggle="tab" data-bs-target="#tab-liste" type="button" role="tab">
                        Utilisateurs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'creer' ? 'active' : '' ?>" id="tab-creer-btn"
                            data-bs-toggle="tab" data-bs-target="#tab-creer" type="button" role="tab">
                        Créer un compte
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'seuils' ? 'active' : '' ?>" id="tab-seuils-btn"
                            data-bs-toggle="tab" data-bs-target="#tab-seuils" type="button" role="tab">
                        Seuils d'alerte
                    </button>
                </li>
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom bg-white p-4 shadow-sm">

                <!-- Onglet : liste des utilisateurs -->
                <div class="tab-pane fade <?= $activeTab === 'liste' ? 'show active' : '' ?>"
                     id="tab-liste" role="tabpanel">

                    <?php if ($flash): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
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
                <div class="tab-pane fade <?= $activeTab === 'creer' ? 'show active' : '' ?>"
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

                <!-- Onglet : seuils d'alerte -->
                <div class="tab-pane fade <?= $activeTab === 'seuils' ? 'show active' : '' ?>"
                     id="tab-seuils" role="tabpanel">

                    <?php if ($succesSeuil !== ''): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($succesSeuil) ?></div>
                    <?php endif; ?>
                    <?php if ($erreurSeuil !== ''): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erreurSeuil) ?></div>
                    <?php endif; ?>

                    <!-- Liste des seuils existants -->
                    <h2 class="h6 fw-bold mb-3">Seuils configurés</h2>
                    <?php if (empty($seuils)): ?>
                        <div class="alert alert-info mb-4">Aucun seuil configuré.</div>
                    <?php else: ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Capteur</th>
                                        <th>Salle</th>
                                        <th>Mesure</th>
                                        <th>Min</th>
                                        <th>Max</th>
                                        <th>Niveau</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($seuils as $s):
                                        $badgeClass = match($s['niveau']) {
                                            'critique' => 'danger',
                                            'info'     => 'primary',
                                            default    => 'warning',
                                        };
                                        $labelsMesure = [
                                            'temperature' => 'Température',
                                            'humidite'    => 'Humidité',
                                            'co2'         => 'CO₂',
                                            'luminosite'  => 'Luminosité',
                                        ];
                                    ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars($s['capteur_type']) ?></td>
                                            <td class="text-secondary"><?= htmlspecialchars($s['salle_nom']) ?></td>
                                            <td><?= htmlspecialchars($labelsMesure[$s['type_mesure']] ?? $s['type_mesure']) ?></td>
                                            <td><?= $s['valeur_min'] !== null ? htmlspecialchars((string)$s['valeur_min']) : '<span class="text-secondary">—</span>' ?></td>
                                            <td><?= $s['valeur_max'] !== null ? htmlspecialchars((string)$s['valeur_max']) : '<span class="text-secondary">—</span>' ?></td>
                                            <td><span class="badge text-bg-<?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($s['niveau'])) ?></span></td>
                                            <td class="text-end">
                                                <form method="post" action=""
                                                      onsubmit="return confirm('Supprimer ce seuil ?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                    <input type="hidden" name="action"    value="delete_seuil">
                                                    <input type="hidden" name="seuil_id"  value="<?= (int)$s['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire d'ajout / mise à jour d'un seuil -->
                    <h2 class="h6 fw-bold mb-3">Ajouter ou modifier un seuil</h2>
                    <p class="text-secondary small mb-3">Si un seuil existe déjà pour ce capteur + type de mesure, il sera mis à jour.</p>

                    <form action="" method="post" class="row g-3" style="max-width: 560px;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="action"     value="create_seuil">

                        <div class="col-12">
                            <label for="id_capteur" class="form-label">Capteur</label>
                            <select class="form-select" id="id_capteur" name="id_capteur" required>
                                <option value="">— Choisir un capteur —</option>
                                <?php foreach ($capteursPourSelect as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"
                                        <?= ((int)($_POST['id_capteur'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['type']) ?> — <?= htmlspecialchars($c['salle_nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="type_mesure" class="form-label">Type de mesure</label>
                            <select class="form-select" id="type_mesure" name="type_mesure" required>
                                <option value="">— Choisir —</option>
                                <option value="temperature" <?= (($_POST['type_mesure'] ?? '') === 'temperature') ? 'selected' : '' ?>>Température (°C)</option>
                                <option value="humidite"    <?= (($_POST['type_mesure'] ?? '') === 'humidite')    ? 'selected' : '' ?>>Humidité (%)</option>
                                <option value="co2"         <?= (($_POST['type_mesure'] ?? '') === 'co2')         ? 'selected' : '' ?>>CO₂ (ppm)</option>
                                <option value="luminosite"  <?= (($_POST['type_mesure'] ?? '') === 'luminosite')  ? 'selected' : '' ?>>Luminosité (lux)</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="niveau" class="form-label">Niveau d'alerte</label>
                            <select class="form-select" id="niveau" name="niveau">
                                <option value="info"          <?= (($_POST['niveau'] ?? 'avertissement') === 'info')          ? 'selected' : '' ?>>Info</option>
                                <option value="avertissement" <?= (($_POST['niveau'] ?? 'avertissement') === 'avertissement') ? 'selected' : '' ?>>Avertissement</option>
                                <option value="critique"      <?= (($_POST['niveau'] ?? '') === 'critique')                   ? 'selected' : '' ?>>Critique</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="valeur_min" class="form-label">Valeur minimale <span class="text-secondary">(optionnel)</span></label>
                            <input type="number" step="0.01" class="form-control" id="valeur_min" name="valeur_min"
                                   value="<?= htmlspecialchars($_POST['valeur_min'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="valeur_max" class="form-label">Valeur maximale <span class="text-secondary">(optionnel)</span></label>
                            <input type="number" step="0.01" class="form-control" id="valeur_max" name="valeur_max"
                                   value="<?= htmlspecialchars($_POST['valeur_max'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary fw-bold py-2 px-4">
                                Enregistrer le seuil
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
