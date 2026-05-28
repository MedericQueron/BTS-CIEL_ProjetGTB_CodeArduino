<?php
// Fichiers utilises pour la securite et la connexion a la base
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/database.php';

// Demarage de la session si besoin
ensure_session_started();

$erreur = '';
$email = '';
// Token de protection du formulaire
$csrf_token = get_csrf_token();

// --- Fonctions anti-brute-force ---

// Retourne true si l'IP ou l'email a dépassé 5 tentatives dans les 15 dernières minutes
function is_rate_limited(PDO $conn, string $ip, string $email): bool
{
    $window = date('Y-m-d H:i:s', time() - 15 * 60);
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE (ip = :ip OR email = :email) AND attempted_at > :window"
    );
    $stmt->execute([':ip' => $ip, ':email' => $email, ':window' => $window]);
    return (int) $stmt->fetchColumn() >= 5;
}

// Enregistre une tentative échouée en base
function record_failed_attempt(PDO $conn, string $ip, string $email): void
{
    $stmt = $conn->prepare(
        "INSERT INTO login_attempts (ip, email) VALUES (:ip, :email)"
    );
    $stmt->execute([':ip' => $ip, ':email' => $email]);
}

// Supprime les tentatives après une connexion réussie
function clear_failed_attempts(PDO $conn, string $ip, string $email): void
{
    $stmt = $conn->prepare(
        "DELETE FROM login_attempts WHERE ip = :ip AND email = :email"
    );
    $stmt->execute([':ip' => $ip, ':email' => $email]);
}

// --- Traitement du formulaire de connexion ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // L'IP est capturée immédiatement, elle sert au rate-limiting quel que soit le résultat
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Verification du token CSRF
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $erreur = "La session a expiré ou la requête est invalide.";
    }

    // Recuperation des donnee envoyees
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Controles simple avant de consulter la base
    if ($erreur === '' && ($email === '' || $password === '')) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif ($erreur === '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    }

    if ($erreur === '') {
        // Vérification du rate-limit AVANT la requête BDD utilisateur
        // Bloque après 5 échecs sur la même IP ou le même email en 15 minutes
        if (is_rate_limited($conn, $clientIp, $email)) {
            $erreur = "Trop de tentatives échouées. Veuillez réessayer dans 15 minutes.";
        }
    }

    if ($erreur === '') {
        try {
            // Recherche de l'utilisateur avec son email
            $query = $conn->prepare("SELECT id, email, passwrd, role FROM users WHERE email = :email");
            $query->execute([':email' => $email]);
            $user = $query->fetch(PDO::FETCH_ASSOC);

            // password_verify compare le mot de passe tapé avec le hash stocké en base
            // on stocke jamais le vrai mot de passe, juste le hash, c'est plus securisé
            if ($user && password_verify($password, $user['passwrd'])) {
                // Connexion réussie : nettoyer les tentatives et régénérer la session
                clear_failed_attempts($conn, $clientIp, $email);

                // session_regenerate_id empeche le "session fixation", vu en cours
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                set_flash_message('success', 'Connexion réussie.');

                header('Location: dashboard.php');
                exit();
            }

            // Echec : enregistrer la tentative pour le rate-limiting
            record_failed_attempt($conn, $clientIp, $email);
            $erreur = "Identifiants incorrects.";
        } catch (PDOException $e) {
            $erreur = "Une erreur est survenue, veuillez réessayer.";
        }
    }
}

$pageTitle = 'Se connecter';
$bodyClass = 'auth-page d-flex align-items-center justify-content-center p-3';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <!-- Carte au centre pour que la connexion soit bien lisible -->
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <p class="text-secondary fw-medium mb-2">Bienvenue</p>
                    <h1 class="h3 fw-bold mb-3">Connexion</h1>
                    <p class="text-secondary mb-4">Veuillez saisir vos identifiants pour accéder au tableau de bord.</p>

                    <form action="" method="post">
                        <!-- Token cacher pour eviter une requete pas normal -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <div class="mb-3">
                            <!-- Champ pour rentrer l'adresse mail de l'utilisateur -->
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        </div>

                        <div class="mb-3">
                            <!-- Champ mot de passe avec un petit bouton pour voir ce qu'on tape -->
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <input type="password" class="form-control password-input" id="password" name="password" required>
                                <button class="btn btn-outline-secondary password-toggle" type="button">Afficher</button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mb-4">
                            <a href="forgot-password.php" class="link-primary">Mot de passe oublié ?</a>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Se connecter</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Petit script pour afficher ou cacher le mot de passe
    document.querySelectorAll('.password-toggle').forEach(function(button) {
        button.addEventListener('click', function() {
            // On cherche l'input qui est dans le meme groupe que le bouton
            const input = button.closest('.input-group').querySelector('.password-input');
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Masquer' : 'Afficher';
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
