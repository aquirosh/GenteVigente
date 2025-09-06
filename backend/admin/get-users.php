<?php
// backend/admin/get-users.php
session_start();
header('Content-Type: application/json');

// Verificar que es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

require '../db.php';

try {
    $stmt = $pdo->query("
        SELECT id, email, first_name, last_name, subscription_type, subscription_status, 
               user_role, created_at, last_login
        FROM users 
        ORDER BY created_at DESC
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (PDOException $e) {
    error_log("Error en get-users: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener usuarios'
    ]);
}
?>