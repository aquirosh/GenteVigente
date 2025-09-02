<?php
// forgot-password.php - Step 2: Basic forgot password page
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';

// Check for success message from processing
if (isset($_GET['sent']) && $_GET['sent'] === '1') {
    $message = 'Si el email existe en nuestro sistema, recibirás un enlace de recuperación en unos minutos.';
    $messageType = 'success';
}

// Check for error messages
if (isset($_SESSION['error_message'])) {
    $message = $_SESSION['error_message'];
    $messageType = 'error';
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Gente Vigente</title>
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
                    <img src="img/LogoGVNB.png" alt="Gente Vigente" class="main-logo">
                </div>
                <h1 class="login-title">Recuperar Contraseña</h1>
                <p class="login-subtitle">Ingresa tu email para recibir instrucciones</p>
            </div>
            
            <!-- Show message if exists -->
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>" style="display: block;">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" action="backend/process-forgot-password.php">
                <div class="form-group full-width">
                    <label for="email" class="form-label">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="tu@email.com"
                        required
                        autocomplete="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                </div>
                
                <div class="form-group full-width">
                    <button type="submit" class="btn-login">
                        Enviar Enlace de Recuperación
                    </button>
                </div>
            </form>
            
            <div class="login-links">
                <a href="login.php" class="forgot-link">
                    ← Volver al Login
                </a>
            </div>
            
            <div style="text-align: center;">
                <a href="index.html" class="back-link">
                    ← Volver al sitio principal
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus on email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>