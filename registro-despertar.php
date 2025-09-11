<?php
// backend/mail/registro-despertar.php
// Template de email para Plan Despertar (llamado desde success.php)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/backend/phpmailer/src/Exception.php';
require_once __DIR__ . '/backend/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/backend/phpmailer/src/SMTP.php';

try {
    $config = require_once __DIR__ . '/config.php';
    
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    
    // Configuración SMTP
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    $mail->SMTPSecure = $config['smtp']['security'];
    $mail->Port = $config['smtp']['port'];
    $mail->CharSet = $config['smtp']['charset'];
    
    // Configurar email
    $mail->setFrom($config['smtp']['username'], $config['app']['name']);
    $mail->addAddress($userData['email'], trim($userData['firstName'] . ' ' . $userData['lastName']));
    $mail->addReplyTo($config['app']['support_email'], $config['app']['name']);
    
    $mail->isHTML(true);
    $mail->Subject = 'Bienvenido al Plan Despertar - ' . $config['app']['name'];
    
    // Template HTML (usar el mismo diseño que ya tienes)
    $appName = $config['app']['name'];
    $loginUrl = $config['app']['login_url'];
    $supportEmail = $config['app']['support_email'];
    
    $mail->Body = "
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
                        <span style='background: #cd7f32; color: white; padding: 8px 20px; border-radius: 25px; font-size: 13px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase;'>PLAN DESPERTAR</span>
                    </div>
                    <h2 style='color: #1a1a1a; font-size: 24px; font-weight: 600; margin: 0 0 12px; text-align: center;'>¡Bienvenido, {$userData['firstName']}!</h2>
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
    
    $emailSent = $mail->send();
    
    if (!$emailSent) {
        error_log('Error enviando email Plan Despertar: ' . $mail->ErrorInfo);
    }
    
} catch (Exception $e) {
    error_log('Error en template email Despertar: ' . $e->getMessage());
}
?>