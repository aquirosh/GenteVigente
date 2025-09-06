<?php
// backend/admin/create-user.php
session_start();
header('Content-Type: application/json');

// Verificar que es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

require '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $firstName = trim($input['firstName'] ?? '');
    $lastName = trim($input['lastName'] ?? '');
    $email = trim($input['email'] ?? '');
    $plan = $input['plan'] ?? 'despertar';
    $status = $input['status'] ?? 'active';
    $role = $input['role'] ?? 'user'; // Nuevo campo para rol
    
    // Validaciones
    if (empty($firstName) || empty($lastName) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        exit;
    }
    
    if (!in_array($plan, ['despertar', 'evolucionar'])) {
        echo json_encode(['success' => false, 'message' => 'Plan inválido']);
        exit;
    }
    
    if (!in_array($status, ['active', 'inactive', 'suspended', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'Estado inválido']);
        exit;
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        echo json_encode(['success' => false, 'message' => 'Rol inválido']);
        exit;
    }
    
    // Verificar que el email no exista
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya existe un usuario con este email']);
        exit;
    }
    
    // Generar contraseña temporal (8 caracteres aleatorios)
    $tempPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 8);
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $stmt = $pdo->prepare("
        INSERT INTO users (
            email, password_hash, first_name, last_name, 
            subscription_type, subscription_status, user_role,
            temp_password, first_time_login
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->execute([
        $email,
        $hashedPassword,
        $firstName,
        $lastName,
        $plan,
        $status,
        $role,
        $hashedPassword // temp_password igual que password_hash inicialmente
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Log de actividad
    $stmt = $pdo->prepare("
        INSERT INTO admin_activity_log (
            admin_user_id, action_type, target_type, target_id, description, ip_address
        ) VALUES (?, 'create_user', 'user', ?, ?, ?)
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $userId,
        "Creó usuario: $firstName $lastName ($email) - Rol: $role - Plan: $plan",
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuario creado correctamente',
        'data' => [
            'id' => $userId,
            'email' => $email,
            'tempPassword' => $tempPassword,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'role' => $role,
            'plan' => $plan
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error creando usuario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear usuario'
    ]);
}
?>