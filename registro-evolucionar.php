<?php
$config = require_once 'config.php';

// registro-evolucionar.php - Plan Evolucionar
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
                'message' => 'Registro exitoso. Revisa tu email para acceder a tu cuenta Plan Evolucionar.',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            error_log("Error en registro Evolucionar: " . $e->getMessage());
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
                ) VALUES (?, ?, ?, ?, 'evolucionar', 'active', ?, 1)
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
            error_log("Error creando usuario Evolucionar: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendWelcomeEmail($userData, $temporaryPassword) {
        try {
            $this->mail->setFrom('andyquirosh@gmail.com', 'Gente Vigente');
            $this->mail->addAddress($userData['email'], trim($userData['first_name'] . ' ' . $userData['last_name']));
            $this->mail->addReplyTo('andyquirosh@gmail.com', 'Gente Vigente');
            
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Bienvenido al Plan Evolucionar - Gente Vigente';
            $this->mail->Body = $this->getWelcomeEmailTemplateEvolucionar($userData, $temporaryPassword);
            
            return $this->mail->send();
            
        } catch (Exception $e) {
            error_log("Error enviando email Evolucionar: " . $e->getMessage());
            return false;
        }
    }
    
    // Aquí iría el template de email que ya creamos anteriormente
    private function getWelcomeEmailTemplateEvolucionar($userData, $temporaryPassword) {
        $loginUrl = 'http://localhost/login.php'; // Cambiar por tu URL real
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bienvenido a Gente Vigente - Plan Evolucionar</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;'>
            
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);'>
                
                <!-- Header compacto -->
                <div style='background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%); padding: 24px 32px; border-bottom: 3px solid #c78b42;'>
                    <div style='text-align: center;'>
                        <h1 style='color: #c78b42; font-size: 24px; font-weight: 600; margin: 0 0 4px; letter-spacing: 1px;'>GENTE VIGENTE</h1>
                        <div style='color: #999; font-size: 13px; font-style: italic; margin: 0;'>Crea, Trasciende, Lidera</div>
                    </div>
                </div>
                
                <!-- Contenido principal -->
                <div style='padding: 32px;'>
                    
                    <!-- Saludo -->

                    <div style='margin-bottom: 32px;'>
                        <div style='text-align: center; margin-bottom: 20px;'>
                            <span style='background: #c78b42; color: white; padding: 8px 20px; border-radius: 25px; font-size: 13px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase;'>PLAN EVOLUCIONAR</span>
                        </div>

                        <h2 style='color: #1a1a1a; font-size: 24px; font-weight: 600; margin: 0 0 12px; text-align: center;'>¡Bienvenido, {$userData['first_name']}!</h2>
                        <p style='color: #666; font-size: 16px; line-height: 1.5; margin: 0; text-align: center;'>Tu cuenta Plan Evolucionar está lista. Comienza tu transformación profesional ahora.</p>
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
                        <div style='background: linear-gradient(135deg, #c78b42 0%, #d4a94c 50%, #e6b555 100%); color: white; padding: 18px; border-radius: 8px; font-family: \"SF Mono\", Monaco, Consolas, monospace; font-size: 18px; font-weight: bold; text-align: center; letter-spacing: 2px; box-shadow: 0 4px 20px rgba(199, 139, 66, 0.4); border: 2px solid rgba(255, 255, 255, 0.3); text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);'>{$temporaryPassword}</div>
                    </div>

                        <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 12px; margin-top: 16px;'>
                            <div style='color: #856404; font-size: 13px; line-height: 1.4; margin: 0;'><strong>Importante:</strong> Esta contraseña es temporal y deberás cambiarla en tu primer acceso.</div>
                        </div>
                    </div>
                    
                    <!-- Botón de acceso -->
                    <div style='text-align: center; margin: 32px 0;'>
                        <a href='{$loginUrl}' style='display: inline-block; background: linear-gradient(135deg, #c78b42, #a6722e); color: white; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: 600; font-size: 14px; letter-spacing: 0.5px;'>Acceder a mi cuenta</a>
                    </div>
                    
                    <!-- Beneficios actualizados -->
                    <div style='border-top: 1px solid #e9ecef; padding-top: 24px; margin-top: 32px;'>
                        <h4 style='color: #1a1a1a; font-size: 16px; font-weight: 600; margin: 0 0 16px; text-align: center;'>Tu Plan Evolucionar incluye:</h4>
                        
                        <div style='background: #fffbf5; border-left: 4px solid #c78b42; padding: 16px; margin: 24px 0; border-radius: 0 6px 6px 0;'>
                        <div style='display: grid; gap: 8px;'>
                            <div style='display: flex; align-items: center; gap: 8px;'> 
                                <span style='color: #666; font-size: 14px;'>• Programa formativo \"El Camino Vigente\" (módulos prácticos)</span>
                            </div>   
                            <div style='display: flex; align-items: center; gap: 8px;'>
                                <span style='color: #666; font-size: 14px;'>• Ejercicios de liderazgo y autoconocimiento aplicables al día a día</span>
                            </div>
                            <div style='display: flex; align-items: center; gap: 8px;'>
                                <span style='color: #666; font-size: 14px;'>• Mentoría grupal avanzada y Herramientas digitales (IA Básico y Avanzado)</span>
                            </div>
                            <div style='display: flex; align-items: center; gap: 8px;'>
                                <span style='color: #666; font-size: 14px;'>• Acceso prioritario a futuros programas y Masterclasses</span>
                            </div>
                        </div>
                        </div>
                    </div>
                    
                    <!-- Info de la cuenta -->
                    <div style='background: #f0f9ff; border: 1px solid #e0f2fe; border-radius: 8px; padding: 20px; margin: 24px 0;'>
                        <div style='display: grid; gap: 8px; font-size: 14px;'>
                            <div style='display: flex; justify-content: space-between;'>
                                <span style='color: #475569;'>Plan:</span>
                                <span style='color: #c78b42; font-weight: 600;'>Evolucionar</span>
                            </div>
                            <div style='display: flex; justify-content: space-between;'>
                                <span style='color: #475569;'>Estado:</span>
                                <span style='color: #16a34a; font-weight: 600;'>Activo</span>
                            </div>
                            <div style='display: flex; justify-content: space-between;'>
                                <span style='color: #475569;'>Precio:</span>
                                <span style='color: #c78b42; font-weight: 600;'>$125 USD/mes</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pasos siguientes -->
                    <div style='background: #fffbf5; border-left: 4px solid #c78b42; padding: 16px; margin: 24px 0; border-radius: 0 6px 6px 0;'>
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
                        ¿Necesitas ayuda? <a href='mailto:andyquirosh@gmail.com' style='color: #c78b42; text-decoration: none;'>andyquirosh@gmail.com</a>
                    </p>
                    <p style='color: #999; font-size: 12px; margin: 0;'>© " . date('Y') . " Gente Vigente. Todos los derechos reservados.</p>
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
    <title>Plan Evolucionar - Gente Vigente</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 50%, #1a1a1a 100%);
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(199, 139, 66, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(199, 139, 66, 0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        .container {
            max-width: 480px;
            width: 100%;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 
                0 25px 60px rgba(0,0,0,0.4),
                0 0 0 1px rgba(199, 139, 66, 0.1);
            position: relative;
            z-index: 1;
            transform: translateY(0);
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #c78b42, #d4a94c, #c78b42);
            animation: shimmer 2s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .header {
            background: linear-gradient(135deg, #c78b42 0%, #d4a94c 50%, #c78b42 100%);
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpolygon points='15,0 30,15 15,30 0,15'/%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .premium-badge {
            display: inline-block;
            background: rgba(255,255,255,0.25);
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .header h1 { 
            font-size: 2.2rem; 
            margin-bottom: 0.5rem; 
            font-weight: 700; 
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header p { 
            font-size: 1rem; 
            opacity: 0.95; 
            font-weight: 500;
            margin-bottom: 1rem;
        }
        
        .price-badge { 
            background: rgba(255,255,255,0.2); 
            padding: 0.8rem 1.5rem; 
            border-radius: 25px; 
            font-size: 1.1rem; 
            font-weight: 700;
            display: inline-block;
            border: 2px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
        }
        
        .form-container { 
            padding: 2rem 1.5rem; 
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            display: none;
            position: relative;
        }
        
        .alert.success { 
            background: linear-gradient(135deg, #d4edda, #c3e6cb); 
            color: #155724; 
            border-left: 4px solid #28a745;
        }
        
        .alert.error { 
            background: linear-gradient(135deg, #f8d7da, #f5c6cb); 
            color: #721c24; 
            border-left: 4px solid #dc3545;
        }
        
        .benefits {
            background: linear-gradient(135deg, rgba(199, 139, 66, 0.03), rgba(255, 255, 255, 0.8));
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(199, 139, 66, 0.2);
            position: relative;
        }
        
        .benefits::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #c78b42, #d4a94c);
            border-radius: 12px 12px 0 0;
        }
        
        .benefits h4 { 
            color: #c78b42; 
            margin-bottom: 1rem; 
            font-size: 1rem; 
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .benefits-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .benefits-list li { 
            margin: 0.6rem 0; 
            position: relative; 
            padding-left: 25px; 
            color: #444; 
            font-size: 0.9rem; 
            line-height: 1.4;
        }
        
        .benefits-list li::before { 
            content: '✨'; 
            position: absolute; 
            left: 0; 
            font-size: 1rem;
        }
        
        .form-group { margin-bottom: 1.5rem; }
        
        .form-label { 
            display: block; 
            margin-bottom: 0.6rem; 
            font-weight: 600; 
            color: #333; 
            font-size: 0.9rem;
        }
        
        .form-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
            font-family: inherit;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #c78b42;
            background: white;
            box-shadow: 0 0 0 3px rgba(199, 139, 66, 0.1);
            transform: translateY(-1px);
        }
        
        .form-input.error { 
            border-color: #dc3545; 
            background: #fff5f5; 
        }
        
        .register-button {
            width: 100%;
            background: linear-gradient(135deg, #c78b42 0%, #d4a94c 50%, #c78b42 100%);
            color: white;
            padding: 1.2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(199, 139, 66, 0.3);
        }
        
        .register-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .register-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(199, 139, 66, 0.4);
        }
        
        .register-button:hover::before {
            left: 100%;
        }
        
        .register-button:disabled { 
            opacity: 0.6; 
            cursor: not-allowed; 
            transform: none; 
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .back-link a { 
            color: #c78b42; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover { 
            color: #a6722e;
        }
        
        .premium-highlight {
            background: linear-gradient(135deg, rgba(199, 139, 66, 0.1), rgba(212, 169, 76, 0.08));
            border: 1px solid rgba(199, 139, 66, 0.2);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            position: relative;
        }
        
        .premium-highlight::before {
            content: '👑';
            position: absolute;
            top: -8px;
            right: 10px;
            background: white;
            padding: 0 5px;
            font-size: 0.9rem;
        }
        
        .premium-highlight h5 {
            color: #c78b42;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .premium-features {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .premium-features li {
            margin: 0.4rem 0;
            position: relative;
            padding-left: 20px;
            color: #555;
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        .premium-features li::before {
            content: '⚡';
            position: absolute;
            left: 0;
            color: #c78b42;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .container { max-width: 100%; }
            .header h1 { font-size: 1.8rem; }
            .form-container { padding: 1.5rem 1rem; }
            .header { padding: 1.5rem 1rem; }
        }
        
        /* Animaciones suaves */
        .form-input, .register-button, .benefits {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .form-input:nth-child(1) { animation-delay: 0.1s; }
        .form-input:nth-child(2) { animation-delay: 0.2s; }
        .form-input:nth-child(3) { animation-delay: 0.3s; }
        .register-button { animation-delay: 0.4s; }
        .benefits { animation-delay: 0.0s; }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="premium-badge">Plan Premium</div>
                <h1>Evolucionar</h1>
                <p>Transforma tu liderazgo y alcanza el siguiente nivel</p>
                <div class="price-badge">$35 USD/mes</div>
            </div>
        </div>
        
        <div class="form-container">
            <div id="alert" class="alert"></div>
            
            <div class="benefits">
                <h4>Tu Plan Evolucionar incluye</h4>
                <ul class="benefits-list">
                    <li>Programa formativo "El Camino Vigente" (módulos prácticos)</li>
                    <li>Ejercicios de liderazgo y autoconocimiento aplicables al día a día</li>
                    <li>Mentoría grupal avanzada y Herramientas digitales (IA Básico y Avanzado)</li>
                    <li>Acceso prioritario a futuros programas y Masterclasses</li>
                </ul>
            </div>
            
            <form id="registrationForm" method="POST">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="first_name" class="form-input" required placeholder="Tu nombre" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Apellido *</label>
                    <input type="text" name="last_name" class="form-input" required placeholder="Tu apellido" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" required placeholder="tu@email.com" maxlength="255">
                </div>
                
                <input type="hidden" name="register_user" value="1">
                
                <button type="submit" class="register-button">
                    Activar Plan Evolucionar
                </button>
            </form>
            
            <div class="back-link">
                <p><a href="index.html">← Volver a planes</a></p>
            </div>
        </div>
    </div>

    <script>
        // Validación de campos
        function validateField(field) {
            const value = field.value.trim();
            let isValid = true;
            
            if (field.hasAttribute('required') && !value) isValid = false;
            if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) isValid = false;
            if ((field.name === 'first_name' || field.name === 'last_name') && value && value.length < 2) isValid = false;
            
            field.classList.toggle('error', !isValid);
            return isValid;
        }

        // Event listeners para validación en tiempo real
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('blur', function() { validateField(this); });
            input.addEventListener('input', function() { this.classList.remove('error'); });
        });

        // Validación del formulario al enviar
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
            
            // Mostrar estado de carga
            const button = this.querySelector('.register-button');
            button.textContent = 'Procesando...';
            button.disabled = true;
        });
        
        // Función para mostrar alertas
        function showAlert(message, type) {
            const alertDiv = document.getElementById('alert');
            alertDiv.className = `alert ${type}`;
            alertDiv.textContent = message;
            alertDiv.style.display = 'block';
            
            // Scroll suave hacia arriba
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