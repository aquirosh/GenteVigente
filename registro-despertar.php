<?php
$config = require_once 'config.php';
// registro-despertar.php - Plan Despertar con diseño moderno
require_once 'backend/phpmailer/src/Exception.php';
require_once 'backend/phpmailer/src/PHPMailer.php';
require_once 'backend/phpmailer/src/SMTP.php';
require_once 'backend/db.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserRegistration {
    private $pdo;
    private $mail;
    private $config;
    
    public function __construct($pdo, $config) {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->initializeMailer();
    }
    
    private function initializeMailer() {
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        
        // USAR CONFIGURACIÓN EN LUGAR DE VALORES HARDCODEADOS
        $this->mail->Host = $this->config['smtp']['host'];
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->config['smtp']['username'];
        $this->mail->Password = $this->config['smtp']['password'];
        $this->mail->SMTPSecure = $this->config['smtp']['security'];
        $this->mail->Port = $this->config['smtp']['port'];
        $this->mail->CharSet = $this->config['smtp']['charset'];
    }
    
    public function registerUser($userData) {
        try {
            if (!$this->validateUserData($userData)) {
                return ['success' => false, 'message' => 'Datos de registro inválidos'];
            }
            
            if ($this->emailExists($userData['email'])) {
                return ['success' => false, 'message' => 'Este email ya está registrado en nuestro sistema'];
            }
            
            $temporaryPassword = $this->generateTemporaryPassword();
            $userId = $this->createUser($userData, $temporaryPassword);
            
            if (!$userId) {
                return ['success' => false, 'message' => 'Error creando usuario en la base de datos'];
            }
            
            $emailSent = $this->sendWelcomeEmail($userData, $temporaryPassword);
            
            if (!$emailSent) {
                $this->deleteUser($userId);
                return ['success' => false, 'message' => 'Error enviando email de bienvenida. Por favor intenta de nuevo.'];
            }
            
            return [
                'success' => true, 
                'message' => 'Registro exitoso. Revisa tu email para acceder a tu cuenta Plan Despertar.',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            error_log("Error en registro Despertar: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor. Intenta más tarde.'];
        }
    }
    
    private function validateUserData($data) {
        $required = ['email', 'first_name', 'last_name'];
        
        foreach ($required as $field) {
            if (empty(trim($data[$field] ?? ''))) {
                return false;
            }
        }
        
        return filter_var($data['email'], FILTER_VALIDATE_EMAIL) !== false;
    }
    
    private function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    private function generateTemporaryPassword($length = 12) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%&*';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }
    
    private function createUser($userData, $temporaryPassword) {
        try {
            $hashedPassword = password_hash($temporaryPassword, PASSWORD_BCRYPT);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (
                    email, 
                    password_hash, 
                    first_name, 
                    last_name, 
                    subscription_type,
                    subscription_status,
                    temp_password,
                    first_time_login
                ) VALUES (?, ?, ?, ?, 'despertar', 'active', ?, 1)
            ");
            
            $result = $stmt->execute([
                trim($userData['email']),
                $hashedPassword,
                trim($userData['first_name']),
                trim($userData['last_name']),
                $temporaryPassword
            ]);
            
            return $result ? $this->pdo->lastInsertId() : false;
            
        } catch (PDOException $e) {
            error_log("Error creando usuario Despertar: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendWelcomeEmail($userData, $temporaryPassword) {
        try {
            // USAR CONFIGURACIÓN EN LUGAR DE VALORES HARDCODEADOS
            $this->mail->setFrom($this->config['smtp']['username'], $this->config['app']['name']);
            $this->mail->addAddress($userData['email'], trim($userData['first_name'] . ' ' . $userData['last_name']));
            $this->mail->addReplyTo($this->config['app']['support_email'], $this->config['app']['name']);
            
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Bienvenido al Plan Despertar - ' . $this->config['app']['name'];
            $this->mail->Body = $this->getWelcomeEmailTemplateDespertar($userData, $temporaryPassword);
            
            return $this->mail->send();
            
        } catch (Exception $e) {
            error_log("Error enviando email Despertar: " . $e->getMessage());
            return false;
        }
    }
    
    private function getWelcomeEmailTemplateDespertar($userData, $temporaryPassword) {
        // USAR CONFIGURACIÓN PARA URL Y EMAIL DE SOPORTE
        $loginUrl = $this->config['app']['login_url'];
        $supportEmail = $this->config['app']['support_email'];
        $appName = $this->config['app']['name'];
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bienvenido a {$appName}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;'>
            
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);'>
                
                <!-- Header compacto -->
                <div style='background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%); padding: 24px 32px; border-bottom: 3px solid #cd7f32;'>
                    <div style='text-align: center;'>
                        <h1 style='color: #cd7f32; font-size: 24px; font-weight: 600; margin: 0 0 4px; letter-spacing: 1px;'>{$appName}</h1>
                        <div style='color: #999; font-size: 13px; font-style: italic; margin: 0;'>Crea, Trasciende, Lidera</div>
                    </div>
                </div>
                
                <!-- Contenido principal -->
                <div style='padding: 32px;'>
                    
                    <!-- Saludo -->
                    <div style='margin-bottom: 32px;'>
                        <div style='text-align: center; margin-bottom: 20px;'>
                            <span style='background: #cd7f32; color: white; padding: 8px 20px; border-radius: 25px; font-size: 13px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase;';'>PLAN DESPERTAR</span>
                        </div>
                        <h2 style='color: #1a1a1a; font-size: 24px; font-weight: 600; margin: 0 0 12px; text-align: center;'>¡Bienvenido, {$userData['first_name']}!</h2>
                        <p style='color: #666; font-size: 16px; line-height: 1.5; margin: 0; text-align: center;'>Tu cuenta Plan Despertar está lista. Comienza tu transformación profesional ahora.</p>
                    </div>
                    
                    <!-- Credenciales -->
                    <div style='background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 24px; margin: 24px 0;'>
                        <h3 style='color: #1a1a1a; font-size: 16px; font-weight: 600; margin: 0 0 20px; text-align: center;'>Credenciales de acceso</h3>
                        
                        <div style='margin: 16px 0;'>
                            <div style='color: #666; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;'>Usuario</div>
                            <div style='background: white; border: 1px solid #e9ecef; padding: 12px; border-radius: 6px; font-size: 14px; color: #1a1a1a; font-weight: 500;'>{$userData['email']}</div>
                        </div>
                        
                        <div style='margin: 16px 0;'>
                            <div style='color: #666; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;'>Contraseña temporal</div>
                            <div style='background: linear-gradient(135deg, #cd7f32, #e6a555); color: white; padding: 16px; border-radius: 8px; font-family: \"SF Mono\", Monaco, Consolas, monospace; font-size: 18px; font-weight: bold; text-align: center; letter-spacing: 2px; box-shadow: 0 4px 12px rgba(199, 139, 66, 0.3); border: 2px solid rgba(255, 255, 255, 0.2);'>{$temporaryPassword}</div>
                        </div>
                        
                        <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 12px; margin-top: 16px;'>
                            <div style='color: #856404; font-size: 13px; line-height: 1.4; margin: 0;'><strong>Importante:</strong> Esta contraseña es temporal y deberás cambiarla en tu primer acceso.</div>
                        </div>
                    </div>
                    
                    <!-- Botón de acceso -->
                    <div style='text-align: center; margin: 32px 0;'>
                        <a href='{$loginUrl}' style='display: inline-block; background: linear-gradient(135deg, #cd7f32, #a6722e); color: white; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: 600; font-size: 14px; letter-spacing: 0.5px;'>Acceder a mi cuenta</a>
                    </div>
                    
                    <!-- Beneficios -->
                    <div style='border-top: 1px solid #e9ecef; padding-top: 24px; margin-top: 32px;'>
                        <h4 style='color: #1a1a1a; font-size: 16px; font-weight: 600; margin: 0 0 16px; text-align: center;'>Tu Plan Despertar incluye:</h4>
                        
                        <div style='background: #fffbf5; border-left: 4px solid #cd7f32; padding: 16px; margin: 24px 0; border-radius: 0 6px 6px 0;'>
                        <div style='display: grid; gap: 8px;'>
                            <div style='display: flex; align-items: center; gap: 8px;'> 
                                <span style='color: #666; font-size: 14px;'>• Sesiones en vivo cada semana</span>
                            </div>   
                            <div style='display: flex; align-items: center; gap: 8px;'>
                                <span style='color: #666; font-size: 14px;'>• Comunidad privada de líderes</span>
                            </div>
                            <div style='display: flex; align-items: center; gap: 8px;'>
                                <span style='color: #666; font-size: 14px;'>• Acceso a grabaciones y materiales exclusivos</span>
                            </div>
                            <div style='display: flex; align-items: center; gap: 8px;'>
                                <span style='color: #666; font-size: 14px;'>• Inspiración y aprendizajes prácticos para tu vida y carrera</span>
                            </div>
                        </div>
                        </div>
                    </div>
                    
                    <!-- Info de la cuenta -->
                    <div style='background: #f0f9ff; border: 1px solid #e0f2fe; border-radius: 8px; padding: 20px; margin: 24px 0;'>
                        <div style='display: grid; gap: 8px; font-size: 14px;'>
                            <div style='display: flex; justify-content: space-between;'>
                                <span style='color: #475569;'>Plan:</span>
                                <span style='color: #0c4a6e; font-weight: 600;'>Despertar</span>
                            </div>
                            <div style='display: flex; justify-content: space-between;'>
                                <span style='color: #475569;'>Estado:</span>
                                <span style='color: #16a34a; font-weight: 600;'>Activo</span>
                            </div>
                            <div style='display: flex; justify-content: space-between;'>
                                <span style='color: #475569;'>Precio:</span>
                                <span style='color: #0c4a6e; font-weight: 600;'>$75 USD/mes</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pasos siguientes -->
                    <div style='background: #fffbf5; border-left: 4px solid #cd7f32; padding: 16px; margin: 24px 0; border-radius: 0 6px 6px 0;'>
                        <div style='color: #92400e; font-size: 13px; line-height: 1.5;'>
                            <strong style='font-size: 14px; display: block; margin-bottom: 8px;'>Próximos pasos:</strong>
                            1. Haz clic en \"Acceder a mi cuenta\"<br>
                            2. Ingresa tu email y contraseña temporal<br>
                            3. Crea tu nueva contraseña personalizada<br>
                            4. Explora tu nuevo dashboard
                        </div>
                    </div>
                    
                </div>
                
                <!-- Footer minimalista -->
                <div style='background: #f8f9fa; padding: 20px 32px; border-top: 1px solid #e9ecef; text-align: center;'>
                    <p style='color: #666; font-size: 13px; margin: 0 0 8px;'>
                        ¿Necesitas ayuda? <a href='mailto:{$supportEmail}' style='color: #cd7f32; text-decoration: none;'>{$supportEmail}</a>
                    </p>
                    <p style='color: #999; font-size: 12px; margin: 0;'>© " . date('Y') . " {$appName}. Todos los derechos reservados.</p>
                </div>
                
            </div>
        </body>
        </html>";
    }
    
    private function deleteUser($userId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return true;
        } catch (PDOException $e) {
            error_log("Error eliminando usuario: " . $e->getMessage());
            return false;
        }
    }
}

// Procesar formulario
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_user'])) {
    $userData = [
        'email' => trim($_POST['email'] ?? ''),
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? '')
    ];
    
    // PASAR CONFIGURACIÓN AL CONSTRUCTOR
    $registration = new UserRegistration($pdo, $config);
    $result = $registration->registerUser($userData);
    
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Despertar - <?php echo $config['app']['name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"> 
    <style>
        :root {
            --primary-color: #cd7f32;
            --secondary-color: #1a1a1a;
            --accent-color: #a6722e;
            --light-gold: #f5e049;
            --text-color: #666;
            --border-color: #e6e8eb;
            --background: #fafbfc;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, var(--secondary-color) 50%, #2c2c2c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Elementos decorativos de fondo */
        body::before {
            content: '';
            position: absolute;
            top: 10%;
            right: 5%;
            width: 300px;
            height: 300px;
            border: 1px solid rgba(199, 139, 66, 0.1);
            border-radius: 50%;
            animation: rotate 25s linear infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            bottom: 15%;
            left: 8%;
            width: 150px;
            height: 150px;
            border: 1px solid rgba(199, 139, 66, 0.15);
            border-radius: 50%;
            animation: rotate 20s linear infinite reverse;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .container {
            max-width: 480px;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 
                0 32px 64px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 10;
        }
        
        .header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2c2c2c 100%);
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(199, 139, 66, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.3; }
            50% { transform: scale(1.1) rotate(180deg); opacity: 0.1; }
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .brand-title {
            font-family: 'Playfair Display', serif;
            color: var(--primary-color);
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 0.5rem;
        }
        
        .plan-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 300;
            letter-spacing: -1px;
            margin-bottom: 0.5rem;
            font-family: 'Playfair Display', serif;
        }
        
        .plan-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }
        
        .price-display {
            display: inline-flex;
            align-items: baseline;
            gap: 0.5rem;
            background: rgba(199, 139, 66, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(199, 139, 66, 0.3);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            color: white;
        }
        
        .price-currency { font-size: 1.2rem; font-weight: 500; }
        .price-amount { font-size: 2rem; font-weight: 700; }
        .price-period { font-size: 1rem; opacity: 0.9; }
        
        .form-container { 
            padding: 2.5rem; 
        }
        
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: 12px;
            font-weight: 500;
            display: none;
            border: 1px solid transparent;
        }
        
        .alert.success { 
            background: linear-gradient(135deg, #d4edda, #c3e6cb); 
            color: #155724; 
            border-color: #28a745; 
        }
        
        .alert.error { 
            background: linear-gradient(135deg, #f8d7da, #f5c6cb); 
            color: #721c24; 
            border-color: #dc3545; 
        }
        
        .benefits {
            background: linear-gradient(135deg, var(--background) 0%, #ffffff 100%);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .benefits::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color), var(--light-gold));
            border-radius: 16px 16px 0 0;
        }
        
        .benefits h4 { 
            color: var(--secondary-color); 
            margin-bottom: 1.5rem; 
            font-size: 1.25rem; 
            font-weight: 600;
            text-align: center;
        }
        
        .benefits ul { 
            list-style: none; 
        }
        
        .benefits li { 
            margin: 1rem 0; 
            position: relative; 
            padding-left: 2rem; 
            color: var(--text-color); 
            font-size: 0.95rem; 
            line-height: 1.6;
            transition: all 0.3s ease;
        }
        
        .benefits li::before { 
            content: '✓'; 
            position: absolute; 
            left: 0; 
            top: 0;
            color: var(--primary-color); 
            font-weight: bold; 
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(199, 139, 66, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .benefits li:hover {
            transform: translateX(5px);
            color: var(--secondary-color);
        }
        
        .form-group { 
            margin-bottom: 1.75rem; 
        }
        
        .form-label { 
            display: block; 
            margin-bottom: 0.75rem; 
            font-weight: 600; 
            color: var(--secondary-color); 
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }
        
        .form-input {
            width: 100%;
            padding: 1.25rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 400;
            background: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: #fefefe;
            box-shadow: 
                0 0 0 4px rgba(199, 139, 66, 0.1),
                0 4px 12px rgba(199, 139, 66, 0.15);
            transform: translateY(-1px);
        }
        
        .form-input.error { 
            border-color: #dc3545; 
            background: #fff5f5; 
        }
        
        .register-button {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1.5rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        .register-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .register-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 
                0 12px 24px rgba(199, 139, 66, 0.4),
                0 0 0 1px rgba(199, 139, 66, 0.1);
        }
        
        .register-button:hover::before {
            left: 100%;
        }
        
        .register-button:active {
            transform: translateY(0);
        }
        
        .register-button:disabled { 
            opacity: 0.6; 
            cursor: not-allowed; 
            transform: none; 
        }
        
        
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .back-link a { 
            color: var(--primary-color); 
            text-decoration: none; 
            font-weight: 500; 
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .back-link a:hover { 
            color: var(--accent-color);
        }
        
        .back-link a::before {
            content: '←';
            margin-right: 0.5rem;
            transition: transform 0.3s ease;
        }
        
        .back-link a:hover::before {
            transform: translateX(-3px);
        }
        
        @media (max-width: 640px) {
            body { padding: 1rem; }
            .container { border-radius: 16px; }
            .header { padding: 2rem 1.5rem; }
            .form-container { padding: 2rem 1.5rem; }
            .plan-title { font-size: 2rem; }
            .brand-title { font-size: 1.5rem; }
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
</head>
<body>
    <div class="fireflies">
    <div class="firefly"></div>
    <div class="firefly"></div>
    <div class="firefly"></div>
    <div class="firefly"></div>
    <div class="firefly"></div>
    </div>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="brand-title"><?php echo strtoupper($config['app']['name']); ?></div>
                <h1 class="plan-title">Despertar</h1>
                <p class="plan-subtitle">Para emprendedores y profesionales en crecimiento</p>
                <div class="price-display">
                    <span class="price-currency">$</span>
                    <span class="price-amount">75</span>
                    <span class="price-period">/mes</span>
                </div>
            </div>
        </div>
        
        <div class="form-container">
            <div id="alert" class="alert"></div>
            
            
            
            <div class="benefits">
                <h4>Tu Plan Despertar incluye</h4>
                <ul>
                    <li>Acceso a contenido exclusivo para emprendedores</li>
                    <li>Herramientas avanzadas de desarrollo profesional</li>
                    <li>Biblioteca de recursos descargables y plantillas</li>
                    <li>Soporte personalizado por email</li>
                    <li>Comunidad privada de networking</li>
                </ul>
            </div>
            
            <form id="registrationForm" method="POST">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="first_name" class="form-input" required placeholder="Tu nombre" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Apellido</label>
                    <input type="text" name="last_name" class="form-input" required placeholder="Tu apellido" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" required placeholder="tu@email.com" maxlength="255">
                </div>
                
                <input type="hidden" name="register_user" value="1">
                
                <button type="submit" class="register-button">
                    Activar Plan Despertar
                </button>
            </form>
            
            <div class="back-link">
                <a href="index.html">Volver a ver todos los planes</a>
            </div>
        </div>
    </div>

    <script>
        function validateField(field) {
            const value = field.value.trim();
            let isValid = true;
            
            if (field.hasAttribute('required') && !value) isValid = false;
            if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) isValid = false;
            if ((field.name === 'first_name' || field.name === 'last_name') && value && value.length < 2) isValid = false;
            
            field.classList.toggle('error', !isValid);
            return isValid;
        }

        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('blur', function() { validateField(this); });
            input.addEventListener('input', function() { this.classList.remove('error'); });
        });

        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            let isFormValid = true;
            this.querySelectorAll('.form-input[required]').forEach(field => {
                if (!validateField(field)) isFormValid = false;
            });
            
            if (!isFormValid) {
                e.preventDefault();
                showAlert('Por favor, completa todos los campos correctamente.', 'error');
                return;
            }
            
            const submitBtn = this.querySelector('.register-button');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Procesando...';
        });
        
        function showAlert(message, type) {
            const alertDiv = document.getElementById('alert');
            alertDiv.className = `alert ${type}`;
            alertDiv.textContent = message;
            alertDiv.style.display = 'block';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            if (type === 'error') {
                setTimeout(() => {
                    alertDiv.style.display = 'none';
                }, 5000);
            }
        }
        
        <?php if (!empty($message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showAlert('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
            <?php if ($messageType === 'success'): ?>
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 3000);
            <?php endif; ?>
        });
        <?php endif; ?>
    </script>
</body>
</html>