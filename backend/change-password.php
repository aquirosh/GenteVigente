<?php
// backend/change-password.php - VERSIÓN COMPLETA Y CORREGIDA
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require 'db.php';

// Configuración de seguridad por defecto
$security = [
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special' => false
    ],
    'logging' => [
        'enable' => true,
        'log_file' => 'logs/security.log'
    ]
];

// Intentar cargar configuración personalizada si existe
if (file_exists('../config/security.php')) {
    try {
        $securityConfig = require_once '../config/security.php';
        if (isset($securityConfig['security'])) {
            $security = $securityConfig['security'];
        }
    } catch (Exception $e) {
        error_log("Error cargando config de seguridad: " . $e->getMessage());
        // Usar configuración por defecto
    }
}

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Si JSON falla, intentar $_POST
    if (!$input || json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST;
    }
    
    if (empty($input)) {
        throw new Exception('No se recibieron datos válidos');
    }
    
    $user_id = $_SESSION['user_id'];
    $current_password = trim($input['currentPassword'] ?? '');
    $new_password = trim($input['newPassword'] ?? '');
    $confirm_password = trim($input['confirmPassword'] ?? '');
    
    // Validaciones básicas
    if (empty($current_password)) {
        throw new Exception('La contraseña actual es obligatoria');
    }
    
    if (empty($new_password)) {
        throw new Exception('La nueva contraseña es obligatoria');
    }
    
    if (empty($confirm_password)) {
        throw new Exception('Debes confirmar la nueva contraseña');
    }
    
    if ($new_password !== $confirm_password) {
        throw new Exception('Las contraseñas nuevas no coinciden');
    }
    
    // Validaciones de seguridad
    $minLength = $security['password']['min_length'] ?? 8;
    if (strlen($new_password) < $minLength) {
        throw new Exception("La contraseña debe tener al menos {$minLength} caracteres");
    }
    
    if (($security['password']['require_uppercase'] ?? false) && !preg_match('/[A-Z]/', $new_password)) {
        throw new Exception('La contraseña debe contener al menos una letra mayúscula');
    }
    
    if (($security['password']['require_lowercase'] ?? false) && !preg_match('/[a-z]/', $new_password)) {
        throw new Exception('La contraseña debe contener al menos una letra minúscula');
    }
    
    if (($security['password']['require_numbers'] ?? false) && !preg_match('/[0-9]/', $new_password)) {
        throw new Exception('La contraseña debe contener al menos un número');
    }
    
    if (($security['password']['require_special'] ?? false) && !preg_match('/[^a-zA-Z0-9]/', $new_password)) {
        throw new Exception('La contraseña debe contener al menos un carácter especial (!@#$%^&*)');
    }
    
    // Verificar que el usuario existe y obtener contraseña actual
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? AND subscription_status = 'active'");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Usuario no encontrado o inactivo');
    }
    
    // Verificar contraseña actual
    if (!password_verify($current_password, $user['password_hash'])) {
        throw new Exception('La contraseña actual es incorrecta');
    }
    
    // Verificar que la nueva contraseña sea diferente
    if (password_verify($new_password, $user['password_hash'])) {
        throw new Exception('La nueva contraseña debe ser diferente a la actual');
    }
    
    // Generar hash de la nueva contraseña
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    if (!$new_password_hash) {
        throw new Exception('Error al procesar la nueva contraseña');
    }
    
    // Actualizar contraseña en la base de datos
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password_hash = ?, 
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$new_password_hash, $user_id]);
    
    if (!$result) {
        throw new Exception('Error al actualizar la contraseña en la base de datos');
    }
    
    // Verificar que se actualizó correctamente
    if ($stmt->rowCount() === 0) {
        throw new Exception('No se pudo actualizar la contraseña - usuario no encontrado');
    }
    
    // Registrar actividad del usuario
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activity (user_id, activity_type, description, ip_address, user_agent, created_at)
            VALUES (?, 'password_change', ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        $description = 'Contraseña cambiada desde el perfil de usuario';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt->execute([$user_id, $description, $ip_address, $user_agent]);
        
    } catch (PDOException $e) {
        // Log del error pero no fallar la operación principal
        error_log("Error registrando actividad de cambio de contraseña: " . $e->getMessage());
    }
    
    // Log de seguridad
    if (($security['logging']['enable'] ?? false)) {
        try {
            $logFile = $security['logging']['log_file'] ?? 'logs/security.log';
            $logDir = dirname($logFile);
            
            // Crear directorio de logs si no existe
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logMessage = sprintf(
                "[%s] Password changed - User ID: %d - IP: %s - User Agent: %s\n",
                date('Y-m-d H:i:s'),
                $user_id,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            );
            
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            // Log del error pero no fallar la operación principal
            error_log("Error escribiendo log de seguridad: " . $e->getMessage());
        }
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Contraseña actualizada correctamente'
    ]);
    
} catch (PDOException $e) {
    error_log("Error PDO en cambio de contraseña: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor. Por favor inténtalo más tarde.'
    ]);
    
} catch (Exception $e) {
    error_log("Error en cambio de contraseña: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>