<?php
// dashboard.php - Punto de entrada que redirige según el rol del usuario
session_start();

// Si no hay sesión, redirigir a login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Si ya sabemos el rol, redirigir directamente
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: dashboard-admin.php');
    } else {
        header('Location: dashboard-user.php');
    }
    exit;
}

// Si no tenemos el rol en sesión, redirigir al sistema de detección
header('Location: role-redirect.php');
exit;
?>