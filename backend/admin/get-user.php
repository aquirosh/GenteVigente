<?php
// backend/admin/get-user.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

require '../db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, last_name, subscription_type, subscription_status, 
               phone, country, created_at
        FROM users 
        WHERE id = ? AND user_role = 'user'
    ");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }
    
} catch (PDOException $e) {
    error_log("Error obteniendo usuario: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al obtener usuario']);
}
?>