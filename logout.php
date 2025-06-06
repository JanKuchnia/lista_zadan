<?php
// Rozpocznij sesję
session_start();

// Wyczyść wszystkie dane sesji
$_SESSION = array();

// Usuń ciasteczko sesji
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Zniszcz sesję
session_destroy();

// Przekieruj do strony logowania
header("Location: home.php");
exit;
?>