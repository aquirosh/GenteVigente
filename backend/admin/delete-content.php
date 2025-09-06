<?php
// backend/admin/delete-content.php
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
    $contentId = $input['contentId'] ?? null;
    
    if (!$contentId || !is_numeric($contentId)) {
        echo json_encode(['success' => false, 'message' => 'ID de contenido inválido']);
        exit;
    }
    
    // Primero intentar eliminar de admin_uploads
    $stmt = $pdo->prepare("
        SELECT file_path, original_filename 
        FROM admin_uploads 
        WHERE id = ?
    ");
    $stmt->execute([$contentId]);
    $file = $stmt->fetch();
    
    if ($file) {
        // Eliminar archivo físico si existe
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        // Eliminar registro de admin_uploads
        $stmt = $pdo->prepare("DELETE FROM admin_uploads WHERE id = ?");
        $stmt->execute([$contentId]);
        
        // Log de actividad
        $stmt = $pdo->prepare("
            INSERT INTO admin_activity_log (
                admin_user_id, action_type, target_type, target_id, description, ip_address
            ) VALUES (?, 'delete_file', 'content', ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $contentId,
            "Eliminó archivo: " . $file['original_filename'],
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Contenido eliminado correctamente'
        ]);
    } else {
        // Si no está en admin_uploads, intentar eliminar de membership_content
        $stmt = $pdo->prepare("DELETE FROM membership_content WHERE id = ?");
        $result = $stmt->execute([$contentId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Contenido eliminado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Contenido no encontrado'
            ]);
        }
    }
    
} catch (PDOException $e) {
    error_log("Error eliminando contenido: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar contenido'
    ]);
}
?>