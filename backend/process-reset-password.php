<?php
// backend/process-reset-password.php - Procesar el reseteo de contraseña
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

require_once 'db.php';
$config = require_once '../config.php';

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$token = $_POST['token'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$error = '';
$success = '';

try {
    // Validaciones básicas
    if (empty($token)) {
        throw new Exception('Token de recuperación inválido');
    }
    
    if (empty($newPassword) || empty($confirmPassword)) {
        throw new Exception('Ambas contraseñas son requeridas');
    }
    
    if ($newPassword !== $confirmPassword) {
        throw new Exception('Las contraseñas no coinciden');
    }
    
    // Validar longitud mínima
    if (strlen($newPassword) < 8) {
        throw new Exception('La contraseña debe tener al menos 8 caracteres');
    }
    
    // Validar que contenga mayúsculas, minúsculas y números
    if (!preg_match('/[a-z]/', $newPassword)) {
        throw new Exception('La contraseña debe contener al menos una letra minúscula');
    }
    
    if (!preg_match('/[A-Z]/', $newPassword)) {
        throw new Exception('La contraseña debe contener al menos una letra mayúscula');
    }
    
    if (!preg_match('/[0-9]/', $newPassword)) {
        throw new Exception('La contraseña debe contener al menos un número');
    }
    
    // Verificar token en la base de datos
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        SELECT prt.user_id, prt.token, u.email 
        FROM password_reset_tokens prt
        JOIN users u ON prt.user_id = u.id
        WHERE prt.token = ? 
        AND prt.expires_at > NOW()
        FOR UPDATE
    ");
    $stmt->execute([$token]);
    $resetData = $stmt->fetch();
    
    if (!$resetData) {
        throw new Exception('El enlace de recuperación ha expirado o ya fue utilizado');
    }
    
    // Generar hash de la nueva contraseña
    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // Actualizar la contraseña del usuario
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password_hash = ?
        WHERE id = ?
    ");
    $result = $stmt->execute([$passwordHash, $resetData['user_id']]);
    
    if (!$result) {
        throw new Exception('Error al actualizar la contraseña');
    }
    
    // Marcar el token como usado eliminándolo (como hace tu process-forgot-password.php)
    $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
    $stmt->execute([$token]);
    
    // Registrar actividad en el log
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activity 
            (user_id, activity_type, description, ip_address, user_agent, created_at)
            VALUES (?, 'password_reset', 'Contraseña restablecida exitosamente', ?, ?, CURRENT_TIMESTAMP)
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt->execute([$resetData['user_id'], $ip, $userAgent]);
        
    } catch (Exception $e) {
        // Log pero no fallar la operación principal
        error_log("Error logging password reset activity: " . $e->getMessage());
    }
    
    // Limpiar tokens expirados (limpieza automática)
    $pdo->exec("DELETE FROM password_reset_tokens WHERE expires_at < NOW()");
    
    $pdo->commit();
    
    // Log de seguridad
    error_log("Password reset successful for user ID: {$resetData['user_id']}, email: {$resetData['email']}, IP: " . ($ip ?? 'unknown'));
    
    $success = 'Contraseña restablecida exitosamente. Ahora puedes iniciar sesión.';
    
} catch (Exception $e) {
    $pdo->rollBack();
    $error = $e->getMessage();
    error_log("Password reset error: " . $e->getMessage());
}

// Si hay éxito, redirigir al login con mensaje
if ($success) {
    $_SESSION['password_reset_success'] = $success;
    header('Location: ../login.php?reset=success');
    exit;
}

// Si hay error, volver al formulario de reseteo con el token
$_SESSION['password_reset_error'] = $error;
header('Location: ../reset-password.php?token=' . urlencode($token) . '&error=1');
exit;
?>