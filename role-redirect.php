<?php
// role-redirect.php - Sistema de detección de roles y redirección automática
session_start();

require 'backend/db.php';

// Debug para desarrollo
$isDebug = ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '.local') !== false);

// Verificar que hay sesión activa
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

try {
    // Obtener datos completos del usuario incluyendo rol
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, last_name, subscription_type, subscription_status, 
               phone, country, created_at, user_role, first_time_login
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        if ($isDebug) {
            echo "<div style='background: red; color: white; padding: 10px; margin: 10px;'>❌ Usuario no encontrado. Destruyendo sesión...</div>";
            echo "<script>setTimeout(() => window.location.href = 'login.php', 2000);</script>";
        }
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    if ($user['subscription_status'] !== 'active') {
        if ($isDebug) {
            echo "<div style='background: orange; color: white; padding: 10px; margin: 10px;'>⚠️ Usuario inactivo. Redirigiendo a login...</div>";
            echo "<script>setTimeout(() => window.location.href = 'login.php', 2000);</script>";
        }
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // Actualizar variables de sesión con datos frescos
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_first_name'] = $user['first_name'] ?: '';
    $_SESSION['user_last_name'] = $user['last_name'] ?: '';
    $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email'];
    $_SESSION['subscription_type'] = $user['subscription_type'];
    $_SESSION['user_phone'] = $user['phone'] ?: '';
    $_SESSION['user_country'] = $user['country'] ?: '';
    $_SESSION['member_since'] = $user['created_at'];
    $_SESSION['user_role'] = $user['user_role']; // IMPORTANTE: Guardar rol en sesión
    $_SESSION['is_admin'] = ($user['user_role'] === 'admin');
    
    // Verificar si necesita cambiar contraseña en primer login
    if ($user['first_time_login']) {
        if ($isDebug) {
            echo "<div style='background: blue; color: white; padding: 10px; margin: 10px;'>ℹ️ Primer login detectado. Redirigiendo a cambio de contraseña...</div>";
            echo "<script>setTimeout(() => window.location.href = 'backend/first-login.php', 2000);</script>";
        } else {
            header('Location: backend/first-login.php');
        }
        exit;
    }
    
    // Determinar dashboard según rol
    $targetDashboard = '';
    $userRoleText = '';
    
    switch ($user['user_role']) {
        case 'admin':
            $targetDashboard = 'dashboard-admin.php';
            $userRoleText = 'Administrador';
            break;
        case 'user':
        default:
            $targetDashboard = 'dashboard-user.php';
            $userRoleText = 'Usuario';
            break;
    }
    
    if ($isDebug) {
        echo "<div style='background: green; color: white; padding: 10px; margin: 10px;'>";
        echo "✅ Sesión válida<br>";
        echo "Usuario: " . htmlspecialchars($_SESSION['user_name']) . "<br>";
        echo "Email: " . htmlspecialchars($_SESSION['user_email']) . "<br>";
        echo "Plan: " . htmlspecialchars($_SESSION['subscription_type']) . "<br>";
        echo "Rol: " . htmlspecialchars($userRoleText) . "<br>";
        echo "Redirigiendo a: " . htmlspecialchars($targetDashboard) . "<br>";
        echo "</div>";
        echo "<script>setTimeout(() => window.location.href = '$targetDashboard', 3000);</script>";
    } else {
        // Redirección automática al dashboard apropiado
        header("Location: $targetDashboard");
    }
    exit;
    
} catch (PDOException $e) {
    error_log("Error en detección de roles: " . $e->getMessage());
    if ($isDebug) {
        echo "<div style='background: red; color: white; padding: 10px; margin: 10px;'>❌ Error de BD: " . $e->getMessage() . "</div>";
        echo "<script>setTimeout(() => window.location.href = 'login.php', 3000);</script>";
    } else {
        session_destroy();
        header('Location: login.php');
    }
    exit;
}
?>