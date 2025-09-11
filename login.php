<?php
// login.php - VERSIÓN ARREGLADA
session_start();

// Debug para desarrollo (eliminar en producción)
$isDebug = ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '.local') !== false);

// Redireccionar si ya está logueado
if (isset($_SESSION['user_id'])) {
    if ($isDebug) {
        echo "<div style='background: yellow; padding: 10px; margin: 10px; border-radius: 5px;'>";
        echo "🔄 Usuario ya logueado (ID: " . $_SESSION['user_id'] . "). Redirigiendo...";
        echo "</div>";
        sleep(1);
    }
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'backend/db.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($isDebug) {
        echo "<div style='background: lightblue; padding: 10px; margin: 10px; border-radius: 5px;'>";
        echo "🔍 <strong>LOGIN ATTEMPT:</strong><br>";
        echo "Email: " . htmlspecialchars($email) . "<br>";
        echo "Password length: " . strlen($password) . " chars<br>";
        echo "</div>";
    }
    
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
            
            if ($isDebug) {
                echo "<div style='background: lightyellow; padding: 10px; margin: 10px; border-radius: 5px;'>";
                if ($user) {
                    echo "✅ <strong>Usuario encontrado:</strong><br>";
                    echo "ID: " . $user['id'] . "<br>";
                    echo "Email: " . $user['email'] . "<br>";
                    echo "Nombre: " . $user['first_name'] . " " . $user['last_name'] . "<br>";
                    echo "Plan: " . $user['subscription_type'] . "<br>";
                    echo "Estado: " . $user['subscription_status'] . "<br>";
                    echo "Primer login: " . ($user['first_time_login'] ? 'SÍ' : 'NO') . "<br>";
                } else {
                    echo "❌ <strong>Usuario NO encontrado en BD</strong>";
                }
                echo "</div>";
            }
            
            if ($user && password_verify($password, $user['password_hash'])) {
                if ($isDebug) {
                    echo "<div style='background: lightgreen; padding: 10px; margin: 10px; border-radius: 5px;'>";
                    echo "✅ <strong>Contraseña correcta</strong><br>";
                }
                
                // Verificar que la suscripción esté activa
                if ($user['subscription_status'] !== 'active') {
                    $error = 'Tu suscripción no está activa (' . $user['subscription_status'] . '). Contacta soporte.';
                    if ($isDebug) {
                        echo "❌ Suscripción no activa: " . $user['subscription_status'] . "<br>";
                        echo "</div>";
                    }
                } else {
                    if ($isDebug) {
                        echo "✅ Suscripción activa<br>";
                    }
                    
                    // Login exitoso - CREAR SESIÓN
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email'];
                    $_SESSION['subscription_type'] = $user['subscription_type'];
                    
                    if ($isDebug) {
                        echo "✅ Sesión creada<br>";
                        echo "Variables de sesión:<br>";
                        echo "- user_id: " . $_SESSION['user_id'] . "<br>";
                        echo "- user_email: " . $_SESSION['user_email'] . "<br>";
                        echo "- user_name: " . $_SESSION['user_name'] . "<br>";
                        echo "- subscription_type: " . $_SESSION['subscription_type'] . "<br>";
                    }
                    
                    // Actualizar último login
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        if ($isDebug) echo "✅ Último login actualizado<br>";
                    } catch (Exception $e) {
                        if ($isDebug) echo "⚠️ Error actualizando último login<br>";
                    }
                    
                    // Determinar redirección
                    if ($user['first_time_login'] == 1) {
                        // PRIMER LOGIN - ir a cambiar contraseña
                        if ($isDebug) {
                            echo "🔄 Primer login detectado - Redirigiendo a backend/first-login.php<br>";
                            echo "</div>";
                            echo "<script>setTimeout(() => window.location.href = 'backend/first-login.php', 2000);</script>";
                        } else {
                            header('Location: backend/first-login.php');
                            exit;
                        }
                    } else {
                        // LOGIN NORMAL - ir al dashboard
                        if ($isDebug) {
                            echo "LOGIN NORMAL - Redirigiendo a dashboard.php<br>";
                            echo "</div>";
                            echo "<script>setTimeout(() => window.location.href = 'dashboard.php', 2000);</script>";
                        } else {
                            header('Location: dashboard.php');
                            exit;
                        }
                    }
                }
            } else {
                // Contraseña incorrecta
                $error = 'Email o contraseña incorrectos.';
                
                if ($isDebug) {
                    echo "<div style='background: lightcoral; padding: 10px; margin: 10px; border-radius: 5px;'>";
                    echo "❌ <strong>Contraseña incorrecta</strong>";
                    echo "</div>";
                }
            }
            
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = 'Error del servidor. Intenta de nuevo en unos momentos.';
            
            if ($isDebug) {
                echo "<div style='background: lightcoral; padding: 10px; margin: 10px; border-radius: 5px;'>";
                echo "💥 <strong>Error de BD:</strong> " . $e->getMessage();
                echo "</div>";
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
    <title>Login - Gente Vigente</title>
    <link rel="icon" type="image/png" href="img/LogoGVNB.png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=visibility,visibility_off" rel="stylesheet">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="geometric-circle"></div>
    <div class="geometric-lines"></div>
    
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="logo-container">
                    <img src="img/LogoGVNB.png" alt="Gente Vigente" class="main-logo">
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
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('password')">
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                </div>
                
                <div class="form-group full-width">
                    <button type="submit" id="loginBtn" class="btn-login">
                        Iniciar Sesión
                    </button>
                </div>
            </form>
            
            <div class="login-links">
                <a href="forgot-password.php" onclick="showForgotPassword()" class="forgot-link">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
            
            <div style="text-align: center;">
                <a href="index.html" class="back-link">
                    ← Volver al sitio principal
                </a>
            </div>

            <!-- Información de testing (solo para desarrollo) -->
            <?php if ($isDebug): ?>
            <div style="margin-top: 2rem; padding: 1rem; background: #f0f9ff; border-radius: 8px; font-size: 0.8rem; color: #0369a1;">
                <strong>🧪 DEBUG MODE ACTIVO</strong><br>
                <?php 
                // Mostrar algunos usuarios de la BD para testing
                try {
                    $stmt = $pdo->query("SELECT email, first_name, subscription_type FROM users ORDER BY created_at DESC LIMIT 3");
                    echo "<strong>Usuarios recientes:</strong><br>";
                    while ($testUser = $stmt->fetch()) {
                        echo "• " . $testUser['email'] . " (" . $testUser['subscription_type'] . ")<br>";
                    }
                } catch (Exception $e) {
                    echo "Error consultando usuarios de prueba";
                }
                ?>
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

        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const button = input.parentNode.querySelector('.password-toggle');
            const icon = button.querySelector('.material-symbols-outlined');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility_off';
            }
        }


    </script>

    <!-- CSS adicional para los mensajes de alerta que vienen del servidor -->
    <style>
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #2563eb;
        }
         /* Fireflies Background Animation for Despertar Plan */
    .fireflies {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
        overflow: hidden;
    }

    .firefly {
        position: absolute;
        width: 4px;
        height: 4px;
        background: #CD7F32;
        border-radius: 50%;
        box-shadow: 
            0 0 6px #CD7F32,
            0 0 12px #CD7F32,
            0 0 18px rgba(205, 127, 50, 0.8),
            0 0 24px rgba(205, 127, 50, 0.6);
        animation: fly 18s linear infinite;
        opacity: 0;
    }

    /* Different sizes for variety */
    .firefly:nth-child(2n) {
        width: 3px;
        height: 3px;
        animation-duration: 22s;
    }

    .firefly:nth-child(3n) {
        width: 5px;
        height: 5px;
        animation-duration: 15s;
        box-shadow: 
            0 0 8px #e6a96d,
            0 0 16px #e6a96d,
            0 0 24px rgba(230, 169, 109, 0.8),
            0 0 32px rgba(230, 169, 109, 0.6);
    }

    .firefly:nth-child(4n) {
        width: 2px;
        height: 2px;
        animation-duration: 25s;
        background: #e6a96d;
        box-shadow: 
            0 0 4px #e6a96d,
            0 0 8px #e6a96d,
            0 0 12px rgba(230, 169, 109, 0.7);
    }

    .firefly:nth-child(5n) {
        width: 6px;
        height: 6px;
        animation-duration: 12s;
        background: #a0522d;
        box-shadow: 
            0 0 10px #a0522d,
            0 0 20px #a0522d,
            0 0 30px rgba(160, 82, 45, 0.8);
    }

    /* Flight animation */
    @keyframes fly {
        0% {
            opacity: 0;
            transform: translateY(100vh) translateX(0) scale(0);
        }
        10% {
            opacity: 1;
            transform: translateY(90vh) translateX(15px) scale(1);
        }
        20% {
            transform: translateY(80vh) translateX(-20px) scale(1);
        }
        30% {
            transform: translateY(70vh) translateX(25px) scale(1.1);
        }
        40% {
            transform: translateY(60vh) translateX(-10px) scale(0.9);
        }
        50% {
            transform: translateY(50vh) translateX(30px) scale(1);
        }
        60% {
            transform: translateY(40vh) translateX(-25px) scale(1.2);
        }
        70% {
            transform: translateY(30vh) translateX(10px) scale(0.8);
        }
        80% {
            transform: translateY(20vh) translateX(-30px) scale(1);
        }
        90% {
            opacity: 1;
            transform: translateY(10vh) translateX(20px) scale(1.1);
        }
        100% {
            opacity: 0;
            transform: translateY(-10vh) translateX(0) scale(0);
        }
    }

    /* Pulsing glow effect */
    .firefly::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(205, 127, 50, 0.4), transparent 70%);
        animation: pulse 4s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
            opacity: 0.8;
        }
        50% {
            transform: scale(1.8);
            opacity: 0.3;
        }
    }

    /* Individual firefly positions and delays - DISTRIBUTED ACROSS FULL SCREEN */
    .firefly:nth-child(1) {
        left: 15%;
        animation-delay: 0s;
    }

    .firefly:nth-child(2) {
        left: 35%;
        animation-delay: 4s;
    }

    .firefly:nth-child(3) {
        left: 55%;
        animation-delay: 8s;
    }

    .firefly:nth-child(4) {
        left: 75%;
        animation-delay: 2s;
    }

    .firefly:nth-child(5) {
        left: 90%;
        animation-delay: 6s;
    }

    /* Mobile optimization - show only 3 fireflies */
    @media (max-width: 768px) {
        .firefly:nth-child(n+4) {
            display: none;
        }
        
        .firefly {
            animation-duration: 25s;
        }
        
        /* Redistribute the remaining 3 fireflies on mobile */
        .firefly:nth-child(1) {
            left: 20%;
        }
        
        .firefly:nth-child(2) {
            left: 50%;
        }
        
        .firefly:nth-child(3) {
            left: 80%;
        }
    }

    /* Reduce motion for users who prefer it */
    @media (prefers-reduced-motion: reduce) {
        .fireflies {
            display: none;
        }
    }
    </style>
</body>
</html>