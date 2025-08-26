<?php
// backend/first-login.php - ARREGLADO COMPLETAMENTE
session_start();

// Debug para desarrollo
$isDebug = ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '.local') !== false);

if (!isset($_SESSION['user_id'])) {
    if ($isDebug) {
        echo "<div style='background: red; color: white; padding: 10px;'>❌ No hay sesión activa. Redirigiendo a login...</div>";
        sleep(1);
    }
    header('Location: ../login.php');
    exit;
}

require 'db.php';

$error = '';
$success = '';

// Verificar que realmente necesita cambiar contraseña
$stmt = $pdo->prepare("SELECT first_time_login, email, first_name, last_name, subscription_type FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    if ($isDebug) {
        echo "<div style='background: red; color: white; padding: 10px;'>❌ Usuario no encontrado. Destruyendo sesión...</div>";
        sleep(1);
    }
    session_destroy();
    header('Location: ../login.php');
    exit;
}

if (!$user['first_time_login']) {
    if ($isDebug) {
        echo "<div style='background: green; color: white; padding: 10px;'>Usuario ya cambio contraseña. Redirigiendo a dashboard...</div>";
        sleep(1);
    }
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($isDebug) {
        echo "<div style='background: lightblue; padding: 10px;'>🔄 Procesando cambio de contraseña...</div>";
    }
    
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
                SET password_hash = ?, first_time_login = 0, temp_password = NULL
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
            
            if ($result) {
                $success = '¡Contraseña actualizada exitosamente! Redirigiendo al dashboard...';
                
                if ($isDebug) {
                    echo "<div style='background: green; color: white; padding: 10px;'>Contraseña actualizada. Redirigiendo...</div>";
                    echo "<script>setTimeout(() => window.location.href = '../dashboard.php', 2000);</script>";
                } else {
                    // Redirección inmediata en producción
                    header('Location: ../dashboard.php');
                    exit;
                }
            } else {
                $error = 'Error al actualizar la contraseña';
            }
            
        } catch (PDOException $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            $error = 'Error actualizando contraseña. Intenta de nuevo.';
            
            if ($isDebug) {
                echo "<div style='background: red; color: white; padding: 10px;'>❌ Error BD: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Gente Vigente</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .security-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .header h1 {
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .header p {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.5;
            font-size: 1.1rem;
        }
        
        .user-welcome {
            background: linear-gradient(135deg, #c78b42, #a6722e);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .user-welcome h2 {
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }
        
        .subscription-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
            margin-top: 0.5rem;
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
            font-size: 1rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #c78b42;
            box-shadow: 0 0 0 3px rgba(199, 139, 66, 0.1);
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
        
        .btn {
            width: 100%;
            background: linear-gradient(135deg, #c78b42, #a6722e);
            color: white;
            padding: 1.25rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(199, 139, 66, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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
        
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .container { padding: 2rem; }
            .header h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="security-icon">🔐</div>
        
        <div class="header">
            <h1>Cambio de Contraseña</h1>
            <p>Por tu seguridad, debes cambiar tu contraseña temporal por una personalizada.</p>
        </div>
        
        <div class="user-welcome">
            <h2>¡Hola <?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email']); ?>!</h2>
            <p>Bienvenido a Gente Vigente</p>
            <span class="subscription-badge">Plan <?php echo ucfirst($user['subscription_type']); ?></span>
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
        
        <form method="POST" id="passwordForm">
            <div class="password-requirements">
                <h4>📋 Requisitos de la contraseña:</h4>
                <ul>
                    <li>Mínimo 8 caracteres</li>
                    <li>Se recomienda incluir mayúsculas y minúsculas</li>
                    <li>Se recomienda incluir números</li>
                    <li>Evita usar información personal</li>
                </ul>
            </div>
            
            <div class="form-group">
                <label for="new_password">Nueva Contraseña *</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    required
                    placeholder="Ingresa tu nueva contraseña"
                    minlength="8"
                >
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña *</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required
                    placeholder="Confirma tu nueva contraseña"
                    minlength="8"
                >
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                🔒 Cambiar Contraseña y Continuar
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="logout.php" style="color: #666; text-decoration: none; font-size: 0.9rem;">Cerrar Sesión</a>
        </div>
    </div>
    
    <script>
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        function validatePasswords() {
            const newPass = newPassword.value;
            const confirmPass = confirmPassword.value;
            
            // Resetear estilos
            newPassword.style.borderColor = '#e9ecef';
            confirmPassword.style.borderColor = '#e9ecef';
            
            // Validar longitud
            if (newPass.length >= 8) {
                newPassword.style.borderColor = '#28a745';
            } else if (newPass.length > 0) {
                newPassword.style.borderColor = '#ffc107';
            }
            
            // Validar coincidencia
            if (confirmPass.length > 0) {
                if (newPass === confirmPass && newPass.length >= 8) {
                    confirmPassword.style.borderColor = '#28a745';
                } else {
                    confirmPassword.style.borderColor = '#dc3545';
                }
            }
        }
        
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
        
        // Validación del formulario
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPass = newPassword.value;
            const confirmPass = confirmPassword.value;
            
            if (newPass.length < 8) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 8 caracteres');
                return;
            }
            
            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Cambiando contraseña...';
        });
        
        // Auto-focus
        setTimeout(() => {
            newPassword.focus();
        }, 500);
    </script>
</body>
</html>