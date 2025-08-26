<?php
// login.php - Conversión manteniendo diseño original
session_start();

// Redireccionar si ya está logueado
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.html');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'backend/db.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, ingresa un email válido.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT id, email, password_hash, first_name, last_name, subscription_type, 
                       subscription_status, first_time_login 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Verificar que la suscripción esté activa
                if ($user['subscription_status'] !== 'active') {
                    $error = 'Tu suscripción no está activa. Contacta soporte.';
                } else {
                    // Login exitoso
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email'];
                    $_SESSION['subscription_type'] = $user['subscription_type'];
                    
                    // Actualizar último login
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Log de actividad (si tienes la función)
                    if (function_exists('logActivity')) {
                        logActivity($pdo, $user['id'], 'login', 'Login exitoso desde IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                    }
                    
                    // Redireccionar según si es primer login
                    if ($user['first_time_login']) {
                        header('Location: first-login.php');
                    } else {
                        header('Location: dashboard.html');
                    }
                    exit;
                }
            } else {
                $error = 'Email o contraseña incorrectos.';
                
                // Opcional: Log de intento fallido
                if (function_exists('logActivity') && $user) {
                    logActivity($pdo, $user['id'], 'login', 'Intento de login fallido');
                }
            }
            
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = 'Error del servidor. Intenta de nuevo en unos momentos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gente Vigente</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                <h1 class="login-title">Acceso Miembros</h1>
                <p class="login-subtitle">Ingresa a tu área exclusiva</p>
            </div>
            
            <div id="alert" class="alert" <?php echo ($error || $success) ? 'style="display: block;"' : ''; ?>>
                <?php if ($error): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showAlert('<?php echo htmlspecialchars($error, ENT_QUOTES); ?>', 'error');
                        });
                    </script>
                <?php endif; ?>
                <?php if ($success): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showAlert('<?php echo htmlspecialchars($success, ENT_QUOTES); ?>', 'success');
                        });
                    </script>
                <?php endif; ?>
            </div>
            
            <form id="loginForm" class="login-form" method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="Correo Electronico"
                        required
                        autocomplete="email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Contraseña"
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <div class="form-group full-width">
                    <button type="submit" id="loginBtn" class="btn-login">
                        Iniciar Sesión
                    </button>
                </div>
            </form>
            
            <div class="login-links">
                <a href="#" onclick="showForgotPassword()" class="forgot-link">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
            
            <div style="text-align: center;">
                <a href="index.html" class="back-link">
                    ← Volver al sitio principal
                </a>
            </div>

            <!-- Información de testing (solo para desarrollo) -->
            <?php if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '.local') !== false): ?>
            <div style="margin-top: 2rem; padding: 1rem; background: #f0f9ff; border-radius: 8px; font-size: 0.8rem; color: #0369a1;">
                <strong>🧪 Usuarios de Prueba (Solo en desarrollo):</strong><br>
                <strong>Gold:</strong> test@genteivigente.com / password123<br>
                <strong>Admin:</strong> admin@gentevigente.com / admin123
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Mantener funciones originales de JavaScript
        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alert.style.display = 'block';
            
            // Auto-hide después de 5 segundos
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        function showForgotPassword() {
            showAlert('Funcionalidad de recuperación de contraseña próximamente...', 'info');
        }

        function setLoadingState(isLoading) {
            const btn = document.getElementById('loginBtn');
            const form = document.getElementById('loginForm');
            
            if (isLoading) {
                btn.classList.add('loading');
                btn.disabled = true;
                form.style.pointerEvents = 'none';
            } else {
                btn.classList.remove('loading');
                btn.disabled = false;
                form.style.pointerEvents = 'auto';
            }
        }

        // Mejorar formulario con validación del lado cliente
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            // Validación básica del lado cliente
            if (!email || !password) {
                e.preventDefault();
                showAlert('Por favor, completa todos los campos.', 'error');
                return;
            }
            
            if (!email.includes('@') || !email.includes('.')) {
                e.preventDefault();
                showAlert('Por favor, ingresa un email válido.', 'error');
                return;
            }
            
            // Activar estado de carga
            setLoadingState(true);
            
            // El formulario continuará con el submit normal a PHP
            // El estado de carga se mantendrá hasta que la página se recargue o redireccione
        });

        // Auto-focus y efectos originales
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.getElementById('email').focus();
            }, 300);
            
            // Mantener efectos de hover mejorados
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
        });

        // Manejo de errores globales
        window.addEventListener('error', function(e) {
            console.error('Error:', e.error);
            showAlert('Ha ocurrido un error inesperado.', 'error');
        });

        // Función para mostrar usuarios de prueba (solo en desarrollo)
        <?php if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '.local') !== false): ?>
        function fillTestUser(type) {
            if (type === 'admin') {
                document.getElementById('email').value = 'admin@gentevigente.com';
                document.getElementById('password').value = 'admin123';
            } else {
                document.getElementById('email').value = 'test@genteivigente.com';
                document.getElementById('password').value = 'password123';
            }
        }
        <?php endif; ?>
    </script>

    <!-- CSS adicional para los mensajes de alerta que vienen del servidor -->
    <style>
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #2563eb;
        }
        
        /* Estilo para la información de testing */
        .testing-info {
            margin-top: 2rem;
            padding: 1rem;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #0369a1;
        }
        
        .testing-info strong {
            display: block;
            margin-bottom: 0.5rem;
        }
        
        /* Hacer que los usuarios de prueba sean clickeables en desarrollo */
        <?php if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '.local') !== false): ?>
        .test-user {
            cursor: pointer;
            text-decoration: underline;
        }
        
        .test-user:hover {
            color: var(--primary-color);
        }
        <?php endif; ?>
    </style>
</body>
</html>