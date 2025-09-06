<?php
// backend/admin/upload-file.php - Endpoint para subir grabaciones y materiales
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
    // Configuración de archivos específica para grabaciones
    $allowedTypes = [
        // Videos (grabaciones)
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'webm' => 'video/webm',
        'mkv' => 'video/x-matroska',
        
        // Audio
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'm4a' => 'audio/mp4',
        'aac' => 'audio/aac',
        
        // Documentos
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    $maxFileSize = 500 * 1024 * 1024; // 500MB para grabaciones
    $uploadDir = '../../uploads/content/';
    
    // Crear directorio si no existe
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validar datos del formulario
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $access = $_POST['access'] ?? '';
    
    if (empty($title) || empty($category) || empty($access)) {
        throw new Exception('Faltan campos obligatorios');
    }
    
    $validCategories = ['grabacion_zoom', 'webinar', 'masterclass', 'podcast', 'documento', 'material', 'guia'];
    if (!in_array($category, $validCategories)) {
        throw new Exception('Categoría no válida');
    }
    
    $validAccess = ['all', 'despertar', 'evolucionar'];
    if (!in_array($access, $validAccess)) {
        throw new Exception('Nivel de acceso no válido');
    }
    
    // Procesar archivos subidos
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        throw new Exception('No se han subido archivos');
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
        $fileName = $_FILES['files']['name'][$i];
        $fileTmpName = $_FILES['files']['tmp_name'][$i];
        $fileSize = $_FILES['files']['size'][$i];
        $fileError = $_FILES['files']['error'][$i];
        $fileType = $_FILES['files']['type'][$i];
        
        // Validar errores de subida
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "Error subiendo $fileName: Código de error $fileError";
            continue;
        }
        
        // Validar tamaño
        if ($fileSize > $maxFileSize) {
            $errors[] = "$fileName es demasiado grande. Máximo 500MB.";
            continue;
        }
        
        // Validar tipo de archivo
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!isset($allowedTypes[$fileExtension])) {
            $errors[] = "$fileName: Tipo de archivo no permitido.";
            continue;
        }
        
        // Generar nombre único para el archivo
        $uniqueName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $uniqueName;
        
        // Mover archivo
        if (move_uploaded_file($fileTmpName, $filePath)) {
            // Determinar tipo de contenido
            $contentType = 'document';
            if (in_array($fileExtension, ['mp4', 'mov', 'avi', 'webm', 'mkv'])) {
                $contentType = 'video';
            } elseif (in_array($fileExtension, ['mp3', 'wav', 'm4a', 'aac'])) {
                $contentType = 'audio';
            }
            
            // Guardar en base de datos
            $stmt = $pdo->prepare("
                INSERT INTO admin_uploads (
                    admin_user_id, original_filename, stored_filename, file_path, 
                    file_type, file_size, mime_type, category, subcategory,
                    access_level, title, description, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $fileName,
                $uniqueName,
                $filePath,
                $contentType,
                $fileSize,
                $allowedTypes[$fileExtension],
                $contentType,
                $category,
                $access,
                $title . ($i > 0 ? " (Parte " . ($i + 1) . ")" : ""),
                $description
            ]);
            
            // También insertar en membership_content para que aparezca a los usuarios
            $stmt = $pdo->prepare("
                INSERT INTO membership_content (
                    title, description, content_type, content_url, 
                    required_membership, category, is_active, publish_date
                ) VALUES (?, ?, ?, ?, ?, ?, 1, CURDATE())
            ");
            
            $contentUrl = 'uploads/content/' . $uniqueName;
            $requiredMembership = ($access === 'all') ? 'despertar' : $access;
            
            $stmt->execute([
                $title . ($i > 0 ? " (Parte " . ($i + 1) . ")" : ""),
                $description,
                $contentType,
                $contentUrl,
                $requiredMembership,
                $category
            ]);
            
            $uploadedFiles[] = [
                'original_name' => $fileName,
                'stored_name' => $uniqueName,
                'size' => $fileSize,
                'type' => $contentType
            ];
            
            // Log de actividad
            $stmt = $pdo->prepare("
                INSERT INTO admin_activity_log (
                    admin_user_id, action_type, target_type, description, ip_address
                ) VALUES (?, 'upload_file', 'content', ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                "Subió archivo: $fileName ($category)",
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            
        } else {
            $errors[] = "Error moviendo el archivo $fileName";
        }
    }
    
    // Preparar respuesta
    if (!empty($uploadedFiles)) {
        $response = [
            'success' => true,
            'message' => count($uploadedFiles) . ' archivo(s) subido(s) correctamente',
            'files' => $uploadedFiles
        ];
        
        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'No se pudo subir ningún archivo',
            'errors' => $errors
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error en upload: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Error de BD en upload: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos'
    ]);
}
?>