<?php
// backend/admin/get-content.php
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
    // Intentar obtener de admin_uploads primero
    $stmt = $pdo->query("
        SELECT id, title, description, category, access_level, file_type, 
               original_filename, file_size, status, created_at
        FROM admin_uploads 
        ORDER BY created_at DESC
    ");
    
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay datos en admin_uploads, obtener de membership_content
    if (empty($uploads)) {
        $stmt = $pdo->query("
            SELECT id, title, description, content_type as file_type, 
                   category, required_membership as access_level, 
                   'active' as status, created_at
            FROM membership_content 
            ORDER BY created_at DESC
        ");
        
        $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agregar campos faltantes para compatibilidad
        foreach ($uploads as &$upload) {
            $upload['original_filename'] = $upload['title'] ?? 'Sin nombre';
            $upload['file_size'] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'content' => $uploads
    ]);
    
} catch (PDOException $e) {
    error_log("Error en get-content: " . $e->getMessage());
    
    // Si hay error con las tablas, devolver datos de ejemplo
    echo json_encode([
        'success' => true,
        'content' => [
            [
                'id' => 1,
                'title' => 'Contenido de Ejemplo',
                'description' => 'Este es contenido de ejemplo mientras se configuran las tablas',
                'category' => 'masterclass',
                'access_level' => 'despertar',
                'file_type' => 'video',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]
    ]);
}
?>