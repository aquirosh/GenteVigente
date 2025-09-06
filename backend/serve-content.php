<?php
// backend/serve-content.php - Versión corregida
session_start();

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Acceso no autorizado');
}

require 'db.php';

try {
    $contentId = $_GET['id'] ?? null;
    
    if (!$contentId) {
        http_response_code(400);
        die('ID de contenido requerido');
    }
    
    // Obtener información del contenido
    $stmt = $pdo->prepare("
        SELECT au.*, u.subscription_type 
        FROM admin_uploads au, users u
        WHERE au.id = ? 
        AND u.id = ? 
        AND au.status = 'active'
    ");
    $stmt->execute([$contentId, $_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    if (!$result) {
        http_response_code(404);
        die('Contenido no encontrado');
    }
    
    $userPlan = $result['subscription_type'];
    $accessLevel = $result['access_level'];
    
    // Verificar permisos de acceso
    $hasAccess = ($accessLevel === 'all' || 
                  $accessLevel === $userPlan || 
                  ($accessLevel === 'evolucionar' && $userPlan === 'evolucionar'));
    
    if (!$hasAccess) {
        http_response_code(403);
        die('No tienes acceso a este contenido');
    }
    
    // CONSTRUIR LA RUTA CORRECTA usando el nombre almacenado
    $fileName = $result['stored_filename'];
    $filePath = __DIR__ . '/../uploads/content/' . $fileName;
    
    // Verificar que el archivo existe
    if (!file_exists($filePath)) {
        http_response_code(404);
        die('Archivo no encontrado: ' . $fileName);
    }
    
    // Obtener información del archivo
    $fileType = $result['file_type'];
    $mimeType = $result['mime_type'];
    $originalName = $result['original_filename'];
    
    // Configurar headers según el tipo de archivo
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    
    // Siempre mostrar inline (no permitir descarga por ahora)
    header('Content-Disposition: inline; filename="' . $originalName . '"');
    
    // Headers de seguridad y cache
    header('Cache-Control: private, max-age=3600');
    header('Pragma: private');
    header('X-Content-Type-Options: nosniff');
    
    // Registrar acceso (opcional)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO content_access_log (user_id, content_id, accessed_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE accessed_at = NOW()
        ");
        $stmt->execute([$_SESSION['user_id'], $contentId]);
    } catch (Exception $e) {
        // Si falla el log, continuar sirviendo el archivo
        error_log("Error logging access: " . $e->getMessage());
    }
    
    // Servir el archivo
    readfile($filePath);
    
} catch (PDOException $e) {
    error_log("Error en serve-content: " . $e->getMessage());
    http_response_code(500);
    die('Error interno del servidor');
} catch (Exception $e) {
    error_log("Error general en serve-content: " . $e->getMessage());
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}
?>