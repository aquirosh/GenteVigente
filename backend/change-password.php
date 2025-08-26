<?php
// change-password.php - Para cambiar contraseña desde el perfil
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Todos los campos son requeridos';
    } elseif (strlen($newPassword) < 8) {
        $error = 'La nueva contraseña debe tener al menos 8 caracteres';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'La nueva contraseña y la confirmación no coinciden';
    } else {
        try {
            // Verificar contraseña actual
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                $error = 'La contraseña actual es incorrecta';
            } else {
                // Actualizar contraseña
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                
                // Log de actividad
                if (function_exists('logActivity')) {
                    logActivity($pdo, $_SESSION['user_id'], 'password_change', 'Contraseña cambiada desde perfil');
                }
                
                $success = '¡Contraseña actualizada exitosamente!';
            }
            
        } catch (PDOException $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            $error = 'Error del servidor. Intenta de nuevo.';
        }
    }
}

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Gente Vigente</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header h1 {
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }
        .header p {
            color: #666;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #c78b42;
            box-shadow: 0 0 0 3px rgba(199, 139, 66, 0.1);
        }
        .btn {
            width: 100%;
            background: linear-gradient(135deg, #c78b42, #a6722e);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(199, 139, 66, 0.3);
        }
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover {
            color: #c78b42;
        }
        .password-requirements {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            padding: 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #0369a1;
            margin: 1rem 0;
        }
        .user-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .user-info strong {
            color: #c78b42;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Cambiar Contraseña</h1>
            <p>Actualiza tu contraseña de forma segura</p>
        </div>
        
        <div class="user-info">
            <p>Conectado como: <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
        </div>
        
        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="current_password">🔒 Contraseña Actual:</label>
                <input 
                    type="password" 
                    id="current_password" 
                    name="current_password" 
                    required
                    placeholder="Tu contraseña actual"
                >
            </div>
            
            <div class="form-group">
                <label for="new_password">🔑 Nueva Contraseña:</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    required
                    placeholder="Tu nueva contraseña"
                    minlength="8"
                >
            </div>
            
            <div class="form-group">
                <label for="confirm_password">✅ Confirmar Nueva Contraseña:</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required
                    placeholder="Repite tu nueva contraseña"
                    minlength="8"
                >
            </div>
            
            <div class="password-requirements">
                <strong>Requisitos de la contraseña:</strong><br>
                • Mínimo 8 caracteres<br>
                • Se recomienda incluir mayúsculas, minúsculas y números<br>
                • Evita usar información personal
            </div>
            
            <button type="submit" class="btn">
                Cambiar Contraseña
            </button>
        </form>
        
        <a href="dashboard.html" class="back-link">← Volver al Dashboard</a>
    </div>
    
    <script>
        // Validación en tiempo real
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePasswords() {
            if (newPassword.value.length > 0 && confirmPassword.value.length > 0) {
                if (newPassword.value === confirmPassword.value) {
                    confirmPassword.style.borderColor = '#28a745';
                } else {
                    confirmPassword.style.borderColor = '#dc3545';
                }
            }
        }
        
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
        
        // Auto-hide success message after 5 seconds
        <?php if ($success): ?>
        setTimeout(() => {
            const successMsg = document.querySelector('.message.success');
            if (successMsg) {
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 300);
            }
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>