<?php
// backend/update-profile.php - VERSIÓN CORREGIDA
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

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Intentar obtener datos de $_POST si JSON falló
        $input = $_POST;
    }
    
    if (empty($input)) {
        throw new Exception('No se recibieron datos');
    }
    
    $user_id = $_SESSION['user_id'];
    $first_name = trim($input['firstName'] ?? '');
    $last_name = trim($input['lastName'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '') ?: null;
    $country = trim($input['country'] ?? '') ?: null;
    
    // Validaciones básicas
    if (empty($first_name) || empty($last_name) || empty($email)) {
        throw new Exception('Nombre, apellido y email son obligatorios');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email no válido');
    }
    
    // Verificar que el email no esté en uso por otro usuario
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    
    if ($stmt->fetch()) {
        throw new Exception('Este email ya está en uso por otra cuenta');
    }
    
    // Actualizar usuario
    $stmt = $pdo->prepare("
        UPDATE users 
        SET first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone = ?, 
            country = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $first_name, 
        $last_name, 
        $email, 
        $phone, 
        $country, 
        $user_id
    ]);
    
    if (!$result) {
        throw new Exception('Error al actualizar el perfil en la base de datos');
    }
    
    // Actualizar variables de sesión
    $_SESSION['user_first_name'] = $first_name;
    $_SESSION['user_last_name'] = $last_name;
    $_SESSION['user_name'] = trim($first_name . ' ' . $last_name);
    $_SESSION['user_email'] = $email;
    $_SESSION['user_phone'] = $phone;
    $_SESSION['user_country'] = $country;
    
    // Registrar actividad
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activity (user_id, activity_type, description, ip_address, user_agent)
            VALUES (?, 'profile_update', ?, ?, ?)
        ");
        
        $description = "Perfil actualizado: " . $first_name . " " . $last_name . " (" . $email . ")";
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt->execute([$user_id, $description, $ip_address, $user_agent]);
    } catch (Exception $e) {
        error_log("Error registrando actividad: " . $e->getMessage());
        // No fallar si el log no funciona
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Perfil actualizado correctamente',
        'data' => [
            'firstName' => $first_name,
            'lastName' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'country' => $country,
            'fullName' => trim($first_name . ' ' . $last_name)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error actualizando perfil: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    error_log("Error en update-profile: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>