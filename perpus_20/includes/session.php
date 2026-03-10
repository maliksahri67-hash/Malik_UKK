<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getRole() {
    return $_SESSION['role'] ?? null;
}

function requireLogin($role = null) {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
    if ($role && getRole() !== $role) {
        $r = strtolower(getRole());
        header('Location: ' . BASE_URL . '/' . $r . '/dashboard.php');
        exit;
    }
}

function requireLoginMulti(array $roles) {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
    if (!in_array(getRole(), $roles)) {
        $r = strtolower(getRole());
        header('Location: ' . BASE_URL . '/' . $r . '/dashboard.php');
        exit;
    }
}

function flashSet($key, $msg) {
    $_SESSION['flash'][$key] = $msg;
}

function flashGet($key) {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}
?>
