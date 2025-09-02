<?php
// reset-password.php - Step 4: Reset password with token (CORREGIDO)
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'backend/db.php';
$config = require_once 'config.php';

$error = '';
$token = $_GET['token'] ?? '';
$resetData = null;

// Validate token
if (empty($token)) {
    $error = 'Token de recuperación inválido o faltante.';
} else {
    // Check if token exists and is valid - usando solo los campos que tu tabla tiene
    try {
        $stmt = $pdo->prepare("
            SELECT prt.user_id, prt.token, prt.expires_at, u.email, u.first_name 
            FROM password_reset_tokens prt
            JOIN users u ON prt.user_id = u.id
            WHERE prt.token = ? 
            AND prt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $resetData = $stmt->fetch();
        
        if (!$resetData) {
            $error = 'El enlace de recuperación ha expirado o ya fue utilizado.';
        }
    } catch (Exception $e) {
        error_log("Error validando token de reset: " . $e->getMessage());
        $error = 'Error interno. Por favor solicita un nuevo enlace de recuperación.';
    }
}

// Mostrar errores de sesión si existen
if (isset($_SESSION['password_reset_error'])) {
    $error = $_SESSION['password_reset_error'];
    unset($_SESSION['password_reset_error']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Gente Vigente</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=visibility,visibility_off" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="geometric-circle"></div>
    <div class="geometric-lines"></div>
    
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="logo-container">
                    <img src="img/GenteVigente.png" alt="Gente Vigente" class="main-logo">
                </div>
                <h1 class="login-title">Restablecer Contraseña</h1>
                <?php if (!$error && $resetData): ?>
                <p class="login-subtitle">Nueva contraseña para <?= htmlspecialchars($resetData['email']) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($error): ?>
                <!-- Token invalid or expired -->
                <div class="alert alert-error" style="display: block; background: rgba(220, 38, 38, 0.1); border: 1px solid #dc2626; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
                
                <div class="login-links" style="text-align: center; margin-top: 2rem;">
                    <a href="forgot-password.php" class="forgot-link">
                        Solicitar nuevo enlace de recuperación
                    </a>
                </div>
                
                <!-- Debug info para desarrollo -->
                <?php if (isset($_GET['debug'])): ?>
                <div style="background: #f0f0f0; padding: 1rem; margin-top: 1rem; border-radius: 4px; font-size: 0.8rem;">
                    <strong>Debug info:</strong><br>
                    Token recibido: <?= htmlspecialchars($token) ?><br>
                    <a href="debug-reset.php?token=<?= urlencode($token) ?>">Ver debug completo</a>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Valid token - show password form -->
                <form class="login-form" method="POST" action="backend/process-reset-password.php">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="form-group full-width">
                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                        <div class="password-input-container">
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                class="form-input" 
                                placeholder="Nueva contraseña"
                                required
                                autocomplete="new-password"
                                minlength="8"
                            >
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('new_password')">
                                <span class="material-symbols-outlined">visibility_off</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                        <div class="password-input-container">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input" 
                                placeholder="Confirmar nueva contraseña"
                                required
                                autocomplete="new-password"
                                minlength="8"
                            >
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirm_password')">
                                <span class="material-symbols-outlined">visibility_off</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Password requirements -->
                    <div style="background: rgba(199, 139, 66, 0.05); padding: 1rem; border-radius: 8px; margin: 1rem 0; font-size: 0.85rem;">
                        <strong style="display: block; margin-bottom: 0.5rem;">La contraseña debe tener:</strong>
                        <ul style="margin: 0; padding-left: 1.2rem; list-style: none;">
                            <li id="length-check" style="margin: 0.25rem 0; color: #dc2626;">• Al menos 8 caracteres</li>
                            <li id="uppercase-check" style="margin: 0.25rem 0; color: #dc2626;">• Una letra mayúscula</li>
                            <li id="lowercase-check" style="margin: 0.25rem 0; color: #dc2626;">• Una letra minúscula</li>
                            <li id="number-check" style="margin: 0.25rem 0; color: #dc2626;">• Un número</li>
                        </ul>
                    </div>
                    
                    <div class="form-group full-width">
                        <button type="submit" id="resetBtn" class="btn-login">
                            Restablecer Contraseña
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div style="text-align: center;">
                <a href="login.php" class="back-link">
                    ← Volver al Login
                </a>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('.material-symbols-outlined');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility_off';
            }
        }
        
        // Real-time password validation
        document.getElementById('new_password')?.addEventListener('input', function() {
            const password = this.value;
            
            // Check requirements
            const checks = {
                'length-check': password.length >= 8,
                'uppercase-check': /[A-Z]/.test(password),
                'lowercase-check': /[a-z]/.test(password),
                'number-check': /[0-9]/.test(password)
            };
            
            // Update visual indicators
            Object.keys(checks).forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.style.color = checks[id] ? '#16a34a' : '#dc2626';
                    element.style.fontWeight = checks[id] ? 'bold' : 'normal';
                    element.innerHTML = checks[id] ? '✓ ' + element.innerHTML.substring(2) : '• ' + element.innerHTML.substring(2);
                }
            });
        });
        
        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const btn = document.getElementById('resetBtn');
            
            // Validations
            if (newPassword.length < 8) {
                alert('La contraseña debe tener al menos 8 caracteres');
                e.preventDefault();
                return;
            }
            
            if (!/[A-Z]/.test(newPassword)) {
                alert('La contraseña debe contener al menos una letra mayúscula');
                e.preventDefault();
                return;
            }
            
            if (!/[a-z]/.test(newPassword)) {
                alert('La contraseña debe contener al menos una letra minúscula');
                e.preventDefault();
                return;
            }
            
            if (!/[0-9]/.test(newPassword)) {
                alert('La contraseña debe contener al menos un número');
                e.preventDefault();
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('Las contraseñas no coinciden');
                e.preventDefault();
                return;
            }
            
            // Loading state
            btn.innerHTML = 'Procesando...';
            btn.classList.add('loading');
            btn.disabled = true;
        });
    </script>

    <style>
        .password-input-container {
            position: relative;
        }
        
        .password-input-container .form-input {
            padding-right: 3rem;
        }
        
        .password-input-container .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-color);
            transition: color 0.2s ease;
            z-index: 2;
            border-radius: 4px;
        }
        
        .password-input-container .password-toggle:hover {
            color: var(--primary-color);
            background: rgba(199, 139, 66, 0.1);
        }

        .btn-login.loading {
            opacity: 0.7;
            cursor: not-allowed;
        }
    </style>
</body>
</html>