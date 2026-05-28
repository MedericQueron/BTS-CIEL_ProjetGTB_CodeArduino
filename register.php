<?php
// Fichiers utilises pour la securite et la connexion a la base
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

// Demarage de la session si besoin
ensure_session_started();

// Réservé aux administrateurs
require_admin();

$erreur = '';
$username = '';
$email = '';
// Token de protection du formulaire
$csrf_token = get_csrf_token();

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verification du token CSRF
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $erreur = "La session a expiré ou la requête est invalide.";
    }

    // Recuperation des donnee du formulaire
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Verifications de base avant l'insertion en base
    if ($erreur === '' && ($username === '' || $email === '' || $password === '' || $confirm_password === '')) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif ($erreur === '' && $password !== $confirm_password) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif ($erreur === '' && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.";
    } elseif ($erreur === '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    }

    if ($erreur === '') {
        // Hash du mot de passe avant enregistrement
        // PASSWORD_DEFAULT utilise bcrypt, c'est l'algo recommandé pour les mots de passe
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Insertion du nouvelle utilisateur
            $query = $conn->prepare("INSERT INTO users (username, email, passwrd) VALUES (:username, :email, :passwrd)");
            $query->execute([
                ':username' => $username,
                ':email' => $email,
                ':passwrd' => $hash,
            ]);

            set_flash_message('success', 'Compte utilisateur créé avec succès.');
            header('Location: admin.php');
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erreur = "Cette adresse email est déjà utilisée.";
            } else {
                $erreur = "Une erreur est survenue, veuillez réessayer.";
            }
        }
    }
}

$pageTitle = 'Créer un compte';
$bodyClass = 'auth-page d-flex align-items-center justify-content-center p-3';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <!-- Carte centrale pour le formulaire d'inscription -->
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <p class="text-secondary fw-medium mb-2">Inscription</p>
                    <h1 class="h3 fw-bold mb-3">Créer un compte</h1>
                    <p class="text-secondary mb-4">Remplissez les informations ci-dessous pour créer votre accès.</p>

                    <form action="" method="post">
                        <!-- Token cacher pour proteger le formulaire -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <div class="mb-3">
                            <!-- Nom d'utilisateur qui sera enregistrer en base -->
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
                        </div>

                        <div class="mb-3">
                            <!-- Adresse email, elle sert aussi a se connecter apres -->
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        </div>

                        <div class="mb-3">
                            <!-- Premier mot de passe taper par l'utilisateur -->
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <input type="password" class="form-control password-input" id="password" name="password" required>
                                <button class="btn btn-outline-secondary password-toggle" type="button">Afficher</button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <!-- Deuxieme mot de passe pour verifier qu'il a pas fait d'erreur -->
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                            <div class="input-group">
                                <input type="password" class="form-control password-input" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary password-toggle" type="button">Afficher</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Créer le compte</button>
                    </form>

                    <p class="text-center text-secondary mt-4 mb-0">
                        <a href="admin.php" class="fw-semibold">Retour au panel admin</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Meme principe que sur login, on affiche ou cache les mots de passe
    document.querySelectorAll('.password-toggle').forEach(function(button) {
        button.addEventListener('click', function() {
            // Je recupere le champ qui est juste a cote du bouton
            const input = button.closest('.input-group').querySelector('.password-input');
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Masquer' : 'Afficher';
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>