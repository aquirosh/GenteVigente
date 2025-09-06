<?php
// backend/admin/upload-file.php - Corregido para estructura de BD actual
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
    // Configuración de archivos
    $allowedTypes = [
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime', 
        'avi' => 'video/x-msvideo',
        'webm' => 'video/webm',
        'mkv' => 'video/x-matroska',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'm4a' => 'audio/mp4',
        'aac' => 'audio/aac',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    $maxFileSize = 500 * 1024 * 1024; // 500MB
    $uploadDir = '../../uploads/content/';
    
    // Crear directorio si no existe
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validar datos del formulario
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    $access = $_POST['access'] ?? '';
    
    if (empty($title) || empty($category) || empty($access)) {
        throw new Exception('Faltan campos obligatorios');
    }
    
    // Procesar archivos subidos
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'])) {
        throw new Exception('No se han subido archivos');
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    // Normalizar estructura de $_FILES para manejar múltiples archivos
    $files = $_FILES['files'];
    if (!is_array($files['name'])) {
        $files = [
            'name' => [$files['name']],
            'type' => [$files['type']], 
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'size' => [$files['size']]
        ];
    }
    
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = $files['name'][$i];
        $fileTmpName = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $fileError = $files['error'][$i];
        
        // Validar errores de subida
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "Error subiendo $fileName: Código $fileError";
            continue;
        }
        
        // Validar tamaño
        if ($fileSize > $maxFileSize) {
            $errors[] = "$fileName es demasiado grande (máx 500MB)";
            continue;
        }
        
        // Validar tipo de archivo
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!isset($allowedTypes[$fileExtension])) {
            $errors[] = "$fileName: Tipo no permitido";
            continue;
        }
        
        // Generar nombre único
        $uniqueName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $uniqueName;
        $dbFilePath = 'uploads/content/' . $uniqueName; // Ruta relativa para la BD
        
        // Mover archivo
        if (move_uploaded_file($fileTmpName, $filePath)) {
            // Determinar categoría para BD
            $dbCategory = 'document';
            if (in_array($fileExtension, ['mp4', 'mov', 'avi', 'webm', 'mkv'])) {
                $dbCategory = 'video';
            } elseif (in_array($fileExtension, ['mp3', 'wav', 'm4a', 'aac'])) {
                $dbCategory = 'audio';  
            }
            
            // **USAR ESTRUCTURA CORRECTA DE TU BD**
            $stmt = $pdo->prepare("
                INSERT INTO admin_uploads (
                    admin_user_id, 
                    original_filename, 
                    stored_filename, 
                    file_path, 
                    file_type, 
                    file_size, 
                    mime_type, 
                    category, 
                    subcategory, 
                    access_level, 
                    title, 
                    description, 
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $result = $stmt->execute([
                $_SESSION['user_id'],           // admin_user_id
                $fileName,                      // original_filename  
                $uniqueName,                    // stored_filename
                $dbFilePath,                    // file_path <- CAMBIAR AQUÍ (era $filePath)
                $dbCategory,                    // file_type
                $fileSize,                      // file_size
                $allowedTypes[$fileExtension],  // mime_type
                $dbCategory,                    // category
                $category,                      // subcategory
                $access,                        // access_level
                $title,                         // title
                $description                    // description
            ]);
            
            if ($result) {
                // También insertar en membership_content para compatibilidad
                $stmt = $pdo->prepare("
                    INSERT INTO membership_content (
                        title, description, content_type, content_url, 
                        required_membership, category, is_active, publish_date
                    ) VALUES (?, ?, ?, ?, ?, ?, 1, CURDATE())
                ");
                
                $contentUrl = 'uploads/content/' . $uniqueName;
                $requiredMembership = ($access === 'all') ? 'despertar' : $access;
                
                $stmt->execute([
                    $title,
                    $description,
                    $dbCategory,
                    $contentUrl,
                    $requiredMembership,
                    $category
                ]);
                
                $uploadedFiles[] = [
                    'original_name' => $fileName,
                    'stored_name' => $uniqueName,
                    'size' => $fileSize,
                    'type' => $dbCategory
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
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
            } else {
                $errors[] = "Error guardando $fileName en BD";
                unlink($filePath); // Eliminar archivo si no se guardó en BD
            }
            
        } else {
            $errors[] = "Error moviendo archivo $fileName";
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
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Error de BD en upload: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>