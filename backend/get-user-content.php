<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

require 'db.php';

try {
    // Obtener plan del usuario
    $stmt = $pdo->prepare("SELECT subscription_type FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    $userPlan = $user['subscription_type'];
    
    // Obtener contenido de admin_uploads
    $query = "
        SELECT 
            id,
            title,
            description,
            subcategory,
            file_type,
            access_level,
            file_size,
            original_filename,
            created_at,
            status
        FROM admin_uploads 
        WHERE status = 'active' 
        AND (
            access_level = 'all' 
            OR access_level = ?
            OR (access_level = 'evolucionar' AND ? = 'evolucionar')
        )
        ORDER BY created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userPlan, $userPlan]);
    $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear contenido
    $formattedContent = array_map(function($item) use ($userPlan) {
        // Determinar icono
        $icon = '📄';
        $category = $item['subcategory'] ?? $item['file_type'];
        
        switch ($category) {
            case 'grabacion_zoom':
            case 'webinar':
            case 'masterclass':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h480q33 0 56.5 23.5T720-720v180l160-160v440L720-420v180q0 33-23.5 56.5T640-160H160Zm0-80h480v-480H160v480Zm0 0v-480 480Z"/></svg>';
                break;
            case 'podcast':
                $icon = '🎧';
                break;
            case 'documento':
            case 'guia':
            case 'material':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M320-440h320v-80H320v80Zm0 120h320v-80H320v80Zm0 120h200v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z"/></svg>';
                break;
        }
        
        // Determinar acceso
        $isPremium = ($item['access_level'] === 'evolucionar');
        $hasAccess = ($userPlan === 'evolucionar' || $item['access_level'] !== 'evolucionar');
        
        // Formatear tamaño
        $fileSize = '';
        if ($item['file_size'] > 0) {
            $sizeMB = round($item['file_size'] / (1024 * 1024), 1);
            $fileSize = $sizeMB . ' MB';
        }
        
        // Determinar duración
        $duration = '';
        if ($item['file_type'] === 'video') {
            $duration = 'Video - ' . ($fileSize ?: 'Duración variable');
        } elseif ($item['file_type'] === 'audio') {
            $duration = 'Audio - ' . ($fileSize ?: 'Duración variable');
        } else {
            $duration = 'Documento - ' . ($fileSize ?: 'Tamaño variable');
        }
        
        return [
            'id' => $item['id'],
            'title' => $item['title'],
            'description' => $item['description'] ?: 'Sin descripción disponible',
            'category' => $category,
            'icon' => $icon,
            'duration' => $duration,
            'isPremium' => $isPremium,
            'hasAccess' => $hasAccess,
            'status' => $item['status'],
            'created_at' => $item['created_at']
        ];
    }, $content);
    
    // Si no hay contenido, mostrar ejemplos
    if (empty($formattedContent)) {
        $formattedContent = [
            [
                'id' => 'demo_1',
                'title' => 'Bienvenido a Gente Vigente',
                'description' => 'Contenido se agregará aquí cuando los administradores suban material.',
                'category' => 'bienvenida',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m430-500 283-283q12-12 28-12t28 12q12 12 12 28t-12 28L487-444l-57-56Zm99 99 254-255q12-12 28.5-12t28.5 12q12 12 12 28.5T840-599L586-345l-57-56ZM211-211q-91-91-91-219t91-219l120-120 59 59q7 7 12 14.5t10 15.5l148-149q12-12 28.5-12t28.5 12q12 12 12 28.5T617-772L444-599l-85 84 19 19q46 46 44 110t-49 111l-57-56q23-23 25.5-54.5T321-440l-47-46q-12-12-12-28.5t12-28.5l57-56q12-12 12-28.5T331-656l-64 64q-68 68-68 162.5T267-267q68 68 163 68t163-68l239-240q12-12 28.5-12t28.5 12q12 12 12 28.5T889-450L649-211q-91 91-219 91t-219-91Zm219-219ZM680-39v-81q66 0 113-47t47-113h81q0 100-70.5 170.5T680-39ZM39-680q0-100 70.5-170.5T280-921v81q-66 0-113 47t-47 113H39Z"/></svg>',
                'duration' => 'Información',
                'isPremium' => false,
                'hasAccess' => true,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'content' => $formattedContent,
        'userPlan' => $userPlan,
        'totalItems' => count($formattedContent)
    ]);
    
} catch (PDOException $e) {
    error_log("Error en get-user-content: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos'
    ]);
} catch (Exception $e) {
    error_log("Error general en get-user-content: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>