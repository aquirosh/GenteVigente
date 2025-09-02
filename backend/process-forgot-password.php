<?php
// backend/process-forgot-password.php - Simplified using your working email method
session_start();

require_once 'phpmailer/src/Exception.php';
require_once 'phpmailer/src/PHPMailer.php';
require_once 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot-password.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

require_once 'db.php';

// Load config the same way as your working registration
if (!isset($config)) {
    $config = require_once(__DIR__ . '/../config.php');
}

$email = trim($_POST['email'] ?? '');

try {
    // Validations
    if (empty($email)) {
        throw new Exception('El correo electrónico es requerido');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El correo electrónico no es válido');
    }
    
    // Find user
    $stmt = $pdo->prepare("SELECT id, first_name, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Clean old tokens
        $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? OR expires_at < NOW()");
        $stmt->execute([$user['id']]);
        
        // Insert new token
        $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $token, $expiresAt]);
        
        // Send email using your working method
        $resetUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../reset-password.php?token=' . $token;
        
        // Use your exact working PHPMailer setup from registration
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['smtp']['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp']['username'];
        $mail->Password = $config['smtp']['password'];
        $mail->SMTPSecure = $config['smtp']['security'];
        $mail->Port = $config['smtp']['port'];
        $mail->CharSet = $config['smtp']['charset'];

        // Use same from/to setup as registration
        $mail->setFrom($config['smtp']['username'], $config['app']['name']);
        $mail->addAddress($user['email'], $user['first_name']);
        $mail->addReplyTo($config['app']['support_email'], $config['app']['name']);

        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de Contraseña - ' . $config['app']['name'];
        
        $mail->Body = "
        <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'>
            <div style='background: linear-gradient(135deg, #c78b42, #e4a853); color: white; padding: 30px; text-align: center;'>
                <h1>Gente Vigente</h1>
                <p>Recuperación de Contraseña</p>
            </div>
            <div style='padding: 30px; background: #f9f9f9;'>
                <h2>Hola {$user['first_name']},</h2>
                <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetUrl}' style='background: #c78b42; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Restablecer Contraseña</a>
                </div>
                <p><strong>Este enlace expira en 1 hora.</strong></p>
                <p>Si no solicitaste este cambio, ignora este email.</p>
            </div>
        </div>";

        $mail->send();
    } else {
        // Simulate delay for security
        usleep(rand(200000, 800000));
    }
    
    // Always show success
    header('Location: ../forgot-password.php?sent=1');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: ../forgot-password.php');
    exit;
}
?>