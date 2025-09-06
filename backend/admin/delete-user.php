<?php
// backend/admin/delete-user.php
session_start();
header('Content-Type: application/json');

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
    $userId = $input['userId'] ?? null;
    
    if (!$userId || !is_numeric($userId)) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
        exit;
    }
    
    // No permitir eliminar administradores
    $stmt = $pdo->prepare("SELECT user_role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user && $user['user_role'] === 'admin') {
        echo json_encode(['success' => false, 'message' => 'No se puede eliminar un administrador']);
        exit;
    }
    
    // Eliminar usuario
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_role = 'user'");
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }
    
} catch (PDOException $e) {
    error_log("Error eliminando usuario: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario']);
}
?>