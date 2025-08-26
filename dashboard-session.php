<?php
// dashboard-session.php - Verificar sesión antes de mostrar dashboard
session_start();

// Debug para desarrollo
$isDebug = ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '.local') !== false);

if (!isset($_SESSION['user_id'])) {
    if ($isDebug) {
        echo "<div style='background: red; color: white; padding: 10px; margin: 10px;'>❌ No hay sesión activa. Redirigiendo a login...</div>";
        echo "<script>setTimeout(() => window.location.href = 'login.php', 2000);</script>";
        exit;
    } else {
        header('Location: login.php');
        exit;
    }
}

// Verificar que el usuario aún existe y está activo
require 'backend/db.php';

try {
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, subscription_type, subscription_status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['subscription_status'] !== 'active') {
        if ($isDebug) {
            echo "<div style='background: red; color: white; padding: 10px; margin: 10px;'>❌ Usuario no válido o inactivo. Destruyendo sesión...</div>";
            echo "<script>setTimeout(() => window.location.href = 'login.php', 2000);</script>";
        }
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // Actualizar variables de sesión con datos frescos (solo si no existen o están vacías)
    if (!isset($_SESSION['user_email']) || empty($_SESSION['user_email'])) {
        $_SESSION['user_email'] = $user['email'];
    }
    if (!isset($_SESSION['user_name']) || empty($_SESSION['user_name'])) {
        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email'];
    }
    if (!isset($_SESSION['subscription_type']) || empty($_SESSION['subscription_type'])) {
        $_SESSION['subscription_type'] = $user['subscription_type'];
    }
    
    if ($isDebug) {
        echo "<div style='background: green; color: white; padding: 10px; margin: 10px;'>";
        echo "✅ Sesión válida<br>";
        echo "Usuario: " . htmlspecialchars($_SESSION['user_name']) . "<br>";
        echo "Email: " . htmlspecialchars($_SESSION['user_email']) . "<br>";
        echo "Plan: " . htmlspecialchars($_SESSION['subscription_type']) . "<br>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    error_log("Error verificando sesión: " . $e->getMessage());
    if ($isDebug) {
        echo "<div style='background: red; color: white; padding: 10px; margin: 10px;'>❌ Error de BD: " . $e->getMessage() . "</div>";
        echo "<script>setTimeout(() => window.location.href = 'login.php', 3000);</script>";
    } else {
        session_destroy();
        header('Location: login.php');
    }
    exit;
}

// Si llegamos aquí, la sesión es válida - continuar con el dashboard
?>