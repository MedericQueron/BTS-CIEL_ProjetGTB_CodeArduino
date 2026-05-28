<?php
require_once __DIR__ . '/includes/security.php';

ensure_session_started();

$pageTitle = 'Mot de passe oublié';
$bodyClass = 'auth-page d-flex align-items-center justify-content-center p-3';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <p class="text-secondary fw-medium mb-2">Assistance</p>
                    <h1 class="h3 fw-bold mb-3">Mot de passe oublié</h1>

                    <div class="alert alert-info" role="alert">
                        <strong>Contactez votre administrateur</strong><br>
                        La réinitialisation du mot de passe se fait uniquement via l'administrateur du système.
                        Veuillez le contacter directement pour modifier votre mot de passe.
                    </div>

                    <p class="text-center text-secondary mt-4 mb-0">
                        <a href="login.php" class="fw-semibold">Retour à la connexion</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
