<?php

// ensure_session_started definit les flags du cookie (httponly, samesite, secure) avant session_start
// security.php est toujours inclus avant ce fichier donc la fonction est disponible
ensure_session_started();

// Si aucun utilisateur est en session, il faut retourner a la connexion
// empty() gere aussi le cas ou la clé n'existe pas, du coup pas besoin de isset() en plus
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit(); // le exit est important sinon le reste de la page s'execute quand meme
}

function require_admin(): void
{
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: dashboard.php');
        exit();
    }
}
