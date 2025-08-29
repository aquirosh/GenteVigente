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
        :root {
            --primary-evolucionar: #c78b42;
            --light-evolucionar: #E0A865;
            --secondary-color: #1a1a1a;
            --accent-evolucionar: #a6722e;
            --gold-highlight: #f5e049;
            --text-color: #666;
            --border-color: #e6e8eb;
            --background: #fafbfc;
            --premium-gradient: linear-gradient(135deg, #c78b42 0%, #E0A865 50%, #c78b42 100%);
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
        
        /* Elementos decorativos de fondo más elegantes */
        body::before {
            content: '';
            position: absolute;
            top: 8%;
            right: 3%;
            width: 350px;
            height: 350px;
            border: 2px solid rgba(199, 139, 66, 0.12);
            border-radius: 50%;
            animation: rotate 30s linear infinite;
            box-shadow: 0 0 40px rgba(199, 139, 66, 0.05);
        }
        
        body::after {
            content: '';
            position: absolute;
            bottom: 12%;
            left: 6%;
            width: 200px;
            height: 200px;
            border: 2px solid rgba(224, 168, 101, 0.15);
            border-radius: 50%;
            animation: rotate 25s linear infinite reverse;
            box-shadow: 0 0 30px rgba(224, 168, 101, 0.08);
        }
        
        /* Elemento adicional para más elegancia */
        body::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100px;
            height: 100px;
            border: 1px solid rgba(199, 139, 66, 0.08);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
            transform: translate(-50%, -50%);
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes float {
            0%, 100% { transform: translate(-50%, -50%) translateY(0px); }
            50% { transform: translate(-50%, -50%) translateY(-20px); }
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(25px);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 
                0 40px 80px rgba(0, 0, 0, 0.45),
                0 0 0 1px rgba(199, 139, 66, 0.15),
                0 0 40px rgba(199, 139, 66, 0.1);
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Borde dorado premium */
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--premium-gradient);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { opacity: 1; transform: scaleX(1); }
            50% { opacity: 0.8; transform: scaleX(1.02); }
        }
        
        .header {
            background: var(--premium-gradient);
            padding: 3.5rem 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -60%;
            left: -60%;
            width: 220%;
            height: 220%;
            background: radial-gradient(circle, rgba(224, 168, 101, 0.15) 0%, transparent 70%);
            animation: pulse 5s ease-in-out infinite;
        }
        
        /* Patrón premium de fondo */
        .header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpolygon points='20,0 40,20 20,40 0,20'/%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.4;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.4; }
            50% { transform: scale(1.15) rotate(180deg); opacity: 0.15; }
        }
        
        .header-content {
            position: relative;
            z-index: 2;
        }
        
        .premium-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 0.75rem 1.75rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .premium-badge::before {
            content: '';
            font-size: 1rem;
        }
        
        .brand-title {
            font-family: 'Playfair Display', serif;
            color: white;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.75px;
            margin-bottom: 0.75rem;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .header h1 {
            font-family: 'Playfair Display', serif;
            color: white;
            font-size: 2.75rem;
            font-weight: 700;
            letter-spacing: -1.25px;
            margin-bottom: 0.75rem;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 400;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 90%;
            margin-left: auto;
            margin-right: auto;
        }
        
        .price-badge {
            display: inline-flex;
            align-items: baseline;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 255, 255, 0.4);
            padding: 1rem 2rem;
            border-radius: 50px;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .plan-title {
            color: white;
            font-size: 2.75rem;
            font-weight: 300;
            letter-spacing: -1.25px;
            margin-bottom: 0.75rem;
            font-family: 'Playfair Display', serif;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .plan-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 400;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 90%;
            margin-left: auto;
            margin-right: auto;
        }
        
        .price-display {
            display: inline-flex;
            align-items: baseline;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 255, 255, 0.4);
            padding: 1rem 2rem;
            border-radius: 50px;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .price-currency { font-size: 1.4rem; font-weight: 600; }
        .price-amount { 
            font-size: 2.5rem; 
            font-weight: 800; 
            font-family: 'Playfair Display', serif;
        }
        .price-period { font-size: 1.1rem; opacity: 0.95; font-weight: 500; }
        
        .form-container { 
            padding: 3rem; 
        }
        
        .alert {
            padding: 1.25rem 1.75rem;
            margin-bottom: 2.25rem;
            border-radius: 16px;
            font-weight: 600;
            display: none;
            border: 1px solid transparent;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(135deg, rgba(199, 139, 66, 0.05), #ffffff);
            border: 1px solid rgba(199, 139, 66, 0.2);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2.25rem;
            position: relative;
            box-shadow: 0 8px 25px rgba(199, 139, 66, 0.08);
        }
        
        .benefits::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--premium-gradient);
            border-radius: 20px 20px 0 0;
        }
        
        .benefits h4 { 
            color: var(--primary-evolucionar); 
            margin-bottom: 2rem; 
            font-size: 1.4rem; 
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .benefits ul { 
            list-style: none; 
        }
        
        .benefits li { 
            margin: 1.25rem 0; 
            position: relative; 
            padding-left: 2.5rem; 
            color: var(--text-color); 
            font-size: 1rem; 
            line-height: 1.7;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .benefits li::before { 
            content: '✓'; 
            position: absolute; 
            left: 0; 
            top: 0;
            color: var(--primary-evolucionar); 
            font-weight: bold; 
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(199, 139, 66, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(199, 139, 66, 0.2);
        }
        
        .benefits li:hover {
            transform: translateX(8px);
            color: var(--secondary-color);
        }
        
        .form-group { 
            margin-bottom: 2rem; 
        }
        
        .form-label { 
            display: block; 
            margin-bottom: 0.875rem; 
            font-weight: 600; 
            color: var(--secondary-color); 
            font-size: 1rem;
            letter-spacing: 0.5px;
        }
        
        .form-input {
            width: 100%;
            padding: 1.5rem 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: 16px;
            font-size: 1.05rem;
            font-weight: 400;
            background: white;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-evolucionar);
            background: #fefefe;
            box-shadow: 
                0 0 0 4px rgba(199, 139, 66, 0.12),
                0 8px 25px rgba(199, 139, 66, 0.15);
            transform: translateY(-2px);
        }
        
        .form-input.error { 
            border-color: #dc3545; 
            background: #fff5f5; 
        }
        
        .register-button {
            width: 100%;
            background: var(--premium-gradient);
            color: white;
            padding: 1.75rem;
            border: none;
            border-radius: 16px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            position: relative;
            overflow: hidden;
            box-shadow: 
                0 8px 25px rgba(199, 139, 66, 0.4),
                0 0 0 1px rgba(199, 139, 66, 0.1);
        }
        
        .register-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }
        
        .register-button::after {
            content: '';
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            transition: transform 0.3s ease;
            font-size: 1.5rem;
        }
        
        .register-button:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 
                0 15px 35px rgba(199, 139, 66, 0.5),
                0 0 0 1px rgba(199, 139, 66, 0.2);
        }
        
        .register-button:hover::before {
            left: 100%;
        }
        
        .register-button:hover::after {
            transform: translateY(-50%) translateX(5px);
        }
        
        .register-button:active {
            transform: translateY(-1px);
        }
        
        .register-button:disabled { 
            opacity: 0.6; 
            cursor: not-allowed; 
            transform: none; 
        }
        
        .back-link {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 2.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .back-link a { 
            color: var(--primary-evolucionar); 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-link a:hover { 
            color: var(--accent-evolucionar);
            transform: translateX(-3px);
        }
        
        .back-link a::before {
            content: '';
            transition: transform 0.3s ease;
            font-size: 1.2rem;
        }
        
        .back-link a:hover::before {
            transform: translateX(-5px);
        }
        
        @media (max-width: 640px) {
            body { padding: 1rem; }
            .container { 
                border-radius: 20px; 
                max-width: 95%;
            }
            .header { padding: 2.5rem 2rem; }
            .form-container { padding: 2.5rem 2rem; }
            .plan-title { font-size: 2.25rem; }
            .brand-title { font-size: 1.75rem; }
            .benefits { padding: 2rem; }
        }
        
        /* Animaciones de entrada */
        .container {
            animation: slideInUp 0.8s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-group {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Fireflies Background Animation */
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
    background: #c78b42;
    border-radius: 50%;
    box-shadow: 
        0 0 6px #c78b42,
        0 0 12px #c78b42,
        0 0 18px rgba(199, 139, 66, 0.8),
        0 0 24px rgba(199, 139, 66, 0.6);
    animation: fly 15s linear infinite;
    opacity: 0;
}

/* Different sizes for variety */
.firefly:nth-child(2n) {
    width: 3px;
    height: 3px;
    animation-duration: 18s;
}

.firefly:nth-child(3n) {
    width: 5px;
    height: 5px;
    animation-duration: 12s;
    box-shadow: 
        0 0 8px #E0A865,
        0 0 16px #E0A865,
        0 0 24px rgba(224, 168, 101, 0.8),
        0 0 32px rgba(224, 168, 101, 0.6);
}

.firefly:nth-child(4n) {
    width: 2px;
    height: 2px;
    animation-duration: 20s;
    background: #E0A865;
    box-shadow: 
        0 0 4px #E0A865,
        0 0 8px #E0A865,
        0 0 12px rgba(224, 168, 101, 0.7);
}

/* Flight animation */
@keyframes fly {
    0% {
        opacity: 0;
        transform: translateY(100vh) translateX(0) scale(0);
    }
    10% {
        opacity: 1;
        transform: translateY(90vh) translateX(10px) scale(1);
    }
    20% {
        transform: translateY(80vh) translateX(-10px) scale(1);
    }
    30% {
        transform: translateY(70vh) translateX(15px) scale(1.1);
    }
    40% {
        transform: translateY(60vh) translateX(-5px) scale(0.9);
    }
    50% {
        transform: translateY(50vh) translateX(20px) scale(1);
    }
    60% {
        transform: translateY(40vh) translateX(-15px) scale(1.2);
    }
    70% {
        transform: translateY(30vh) translateX(5px) scale(0.8);
    }
    80% {
        transform: translateY(20vh) translateX(-20px) scale(1);
    }
    90% {
        opacity: 1;
        transform: translateY(10vh) translateX(10px) scale(1.1);
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
    background: radial-gradient(circle, rgba(199, 139, 66, 0.4), transparent 70%);
    animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 0.8;
    }
    50% {
        transform: scale(1.5);
        opacity: 0.4;
    }
}

/* Individual firefly positions and delays */
.firefly:nth-child(1) {
    left: 8%;
    animation-delay: 0s;
}

.firefly:nth-child(2) {
    left: 20%;
    animation-delay: 2s;
}

.firefly:nth-child(3) {
    left: 32%;
    animation-delay: 4s;
}

.firefly:nth-child(4) {
    left: 44%;
    animation-delay: 1s;
}

.firefly:nth-child(5) {
    left: 56%;
    animation-delay: 6s;
}

.firefly:nth-child(6) {
    left: 68%;
    animation-delay: 3s;
}

.firefly:nth-child(7) {
    left: 80%;
    animation-delay: 8s;
}

.firefly:nth-child(8) {
    left: 92%;
    animation-delay: 5s;
}

.firefly:nth-child(9) {
    left: 14%;
    animation-delay: 9s;
}

.firefly:nth-child(10) {
    left: 26%;
    animation-delay: 7s;
}

.firefly:nth-child(11) {
    left: 38%;
    animation-delay: 11s;
}

.firefly:nth-child(12) {
    left: 50%;
    animation-delay: 4s;
}

.firefly:nth-child(13) {
    left: 62%;
    animation-delay: 12s;
}

.firefly:nth-child(14) {
    left: 74%;
    animation-delay: 10s;
}

.firefly:nth-child(15) {
    left: 86%;
    animation-delay: 13s;
}

/* Reduce animation on mobile for performance */
@media (max-width: 768px) {
    .firefly:nth-child(n+9) {
        display: none;
    }
    
    .firefly {
        animation-duration: 20s;
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
    <div class="firefly"></div>
    <div class="firefly"></div>
    <div class="firefly"></div>
    <div class="firefly"></div>
    <div class="firefly"></div>
    <div class="firefly"></div>
    <div class="firefly"></div>
    <div class="firefly"></div>
    <div class="firefly"></div>
    <div class="firefly"></div>
</div>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="premium-badge">Plan Premium</div>
                <h1>Evolucionar</h1>
                <p>Transforma tu liderazgo y alcanza el siguiente nivel</p>
                <div class="price-badge">$125 USD/mes</div>
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