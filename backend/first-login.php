<?php
// first-login.php - ACTUALIZADO
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require 'db.php';

$error = '';
$success = '';

// Verificar que realmente necesita cambiar contraseña
$stmt = $pdo->prepare("SELECT first_time_login, email, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user['first_time_login']) {
    header('Location: dashboard.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Ambas contraseñas son requeridas';
    } elseif (strlen($newPassword) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            
            // IMPORTANTE: Marcar como NO primer login
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password_hash = ?, first_time_login = 0, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
            
            // Log de actividad
            if (function_exists('logActivity')) {
                logActivity($pdo, $_SESSION['user_id'], 'password_change', 'Contraseña cambiada en primer login');
            }
            
            $success = '¡Contraseña actualizada exitosamente! Redirigiendo al dashboard...';
            
            // Redireccionar después de 2 segundos
            header("Refresh: 2; url=dashboard.html");
            
        } catch (PDOException $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            $error = 'Error actualizando contraseña. Intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Primer Login - Cambiar Contraseña</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            margin: 0;
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            text-align: center;
        }
        .security-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .header h1 {
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            font-family: 'Playfair Display', serif;
        }
        .header p {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
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
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
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
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(199, 139, 66, 0.3);
        }
        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .password-requirements {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #0369a1;
            margin: 1rem 0;
            text-align: left;
        }
        .user-welcome {
            background: linear-gradient(135deg, #c78b42, #a6722e);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="security-icon">🔐</div>
        <div class="header">
            <h1>Bienvenido por Primera Vez</h1>
            <p>Por tu seguridad, debes cambiar tu contraseña temporal por una personalizada.</p>
        </div>
        
        <div class="user-welcome">
            <strong>¡Hola <?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']) ?: 'Usuario'); ?>!</strong><br>
            <small><?php echo htmlspecialchars($user['email']); ?></small>
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
                <label for="confirm_password">✅ Confirmar Contraseña:</label>
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
                <strong>Requisitos de la contraseña:</strong>
                <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                    <li>Mínimo 8 caracteres</li>
                    <li>Se recomienda incluir mayúsculas, minúsculas y números</li>
                    <li>Evita usar información personal</li>
                </ul>
            </div>
            
            <button type="submit" class="btn">
                Cambiar Contraseña y Continuar
            </button>
        </form>
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
        
        // Auto-focus
        newPassword.focus();
    </script>
</body>
</html>