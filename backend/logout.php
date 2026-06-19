<?php
session_start();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $sessionParams = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $sessionParams['path'],
        $sessionParams['domain'],
        $sessionParams['secure'],
        $sessionParams['httponly']
    );
}

session_destroy();

header('Location: ../pages/login.php');
exit();
