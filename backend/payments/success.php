<?php
// backend/payments/success.php
// Manejo de pagos exitosos con la nueva estructura modular

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>DEBUG SUCCESS.PHP</h2>";
echo "Subscription ID: " . ($_GET['subscription_id'] ?? 'NO ENCONTRADO') . "<br>";
echo "Token: " . ($_GET['token'] ?? 'NO ENCONTRADO') . "<br>";
echo "Session ID: " . ($_SESSION['pending_subscription_session'] ?? 'NO ENCONTRADO') . "<br>";

echo "<h3>Datos de sesión:</h3>";
print_r($_SESSION);

echo "<h3>Parámetros GET:</h3>";
print_r($_GET);

require_once '../db.php';
require_once 'paypal-config.php';
require_once 'paypal-plans.php';
require_once 'paypal-api.php';

try {
    $subscriptionId = $_GET['subscription_id'] ?? '';
    $token = $_GET['token'] ?? '';
    $ba_token = $_GET['ba_token'] ?? ''; // Algunos casos de PayPal
    
    if (empty($subscriptionId)) {
        throw new Exception('ID de suscripción no encontrado en la URL');
    }
    
    // Verificar que tenemos una sesión válida
    $sessionId = $_SESSION['pending_subscription_session'] ?? '';
    if (empty($sessionId)) {
        throw new Exception('Sesión inválida. Por favor, inicia el proceso de pago nuevamente.');
    }
    
    // Obtener datos de la suscripción pendiente
    $stmt = $pdo->prepare("
        SELECT * FROM pending_subscriptions 
        WHERE session_id = ? AND paypal_subscription_id = ? AND status = 'pending'
    ");
    $stmt->execute([$sessionId, $subscriptionId]);
    $pendingSubscription = $stmt->fetch();
    
    if (!$pendingSubscription) {
        throw new Exception('Suscripción pendiente no encontrada. El pago pudo haber sido procesado anteriormente.');
    }
    
    // Verificar estado de la suscripción en PayPal
    $paypal = new PayPalAPI();
    $subscription = $paypal->getSubscription($subscriptionId);
    
    if (!in_array($subscription['status'], ['ACTIVE', 'APPROVAL_PENDING'])) {
        error_log('PayPal Subscription Status: ' . json_encode($subscription));
        throw new Exception('La suscripción no está activa. Estado actual: ' . $subscription['status']);
    }
    
    // Si está en APPROVAL_PENDING, podemos proceder ya que el usuario aprobó el pago
    $finalStatus = $subscription['status'] === 'ACTIVE' ? 'active' : 'pending_activation';
    
    // Verificar que no existe ya un usuario con este email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$pendingSubscription['email']]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe una cuenta con este email: ' . $pendingSubscription['email']);
    }
    
    // Generar contraseña temporal
    $tempPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 8);
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
    
    // Obtener información del plan
    $planInfo = PayPalPlans::getPlanInfo($pendingSubscription['plan_type']);
    
    // Iniciar transacción en la base de datos
    $pdo->beginTransaction();
    
    try {
        // Crear usuario en la base de datos
        $stmt = $pdo->prepare("
            INSERT INTO users (
                email, password_hash, first_name, last_name,
                subscription_type, subscription_status, user_role,
                paypal_subscription_id, payment_method, temp_password, 
                first_time_login, subscription_start_date, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, 'user', ?, 'paypal', ?, 1, NOW(), ?)
        ");
        
        $stmt->execute([
            $pendingSubscription['email'],
            $hashedPassword,
            $pendingSubscription['first_name'],
            $pendingSubscription['last_name'],
            $pendingSubscription['plan_type'],
            $finalStatus,
            $subscriptionId,
            $hashedPassword,
            $subscription['status']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Actualizar registro de suscripción pendiente
        $stmt = $pdo->prepare("
            UPDATE pending_subscriptions 
            SET status = 'completed' 
            WHERE id = ?
        ");
        $stmt->execute([$pendingSubscription['id']]);
        
        // Actualizar transacción de PayPal inicial
        $stmt = $pdo->prepare("
            UPDATE paypal_transactions 
            SET user_id = ?, status = 'completed', webhook_data = ?
            WHERE subscription_id = ? AND transaction_type = 'subscription_created'
        ");
        $stmt->execute([
            $userId,
            json_encode([
                'subscription' => $subscription,
                'plan_info' => $planInfo,
                'success_timestamp' => date('c')
            ]),
            $subscriptionId
        ]);
        
        // Registrar activación exitosa
        $stmt = $pdo->prepare("
            INSERT INTO paypal_transactions 
            (user_id, subscription_id, transaction_type, status, amount, currency, webhook_data) 
            VALUES (?, ?, 'subscription_activated', 'completed', ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $subscriptionId,
            $planInfo['price'],
            $planInfo['currency'],
            json_encode([
                'subscription_details' => $subscription,
                'user_created' => true,
                'activation_timestamp' => date('c')
            ])
        ]);
        
        $pdo->commit();
        
        // Preparar datos para el email (compatible con tu sistema actual)
        $userData = [
            'email' => $pendingSubscription['email'],
            'firstName' => $pendingSubscription['first_name'],
            'lastName' => $pendingSubscription['last_name'],
            'plan' => $pendingSubscription['plan_type']
        ];
        
        $temporaryPassword = $tempPassword; // Variable para tu sistema de emails
        
        // Enviar email usando tu sistema actual
        try {
            if ($pendingSubscription['plan_type'] === 'despertar') {
                include '../mail/registro-despertar.php';
            } elseif ($pendingSubscription['plan_type'] === 'evolucionar') {
                include '../mail/registro-evolucionar.php';
            }
        } catch (Exception $e) {
            // Log error de email pero no fallar el proceso
            error_log('Error enviando email de bienvenida: ' . $e->getMessage());
        }
        
        // Limpiar datos de sesión
        unset($_SESSION['pending_subscription_session']);
        unset($_SESSION['pending_subscription_data']);
        
        // Mensaje de éxito personalizado según el estado
        $successMessage = $finalStatus === 'active' ? 'account_created' : 'account_created_pending';
        
        // Redirigir a página de éxito
        $redirectURL = '../../login.php?success=' . $successMessage . 
                      '&plan=' . urlencode($pendingSubscription['plan_type']) .
                      '&email=' . urlencode($pendingSubscription['email']);
        
        header('Location: ' . $redirectURL);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Error en success.php: ' . $e->getMessage());
    error_log('PayPal Success Error Context: ' . json_encode([
        'subscription_id' => $subscriptionId ?? 'unknown',
        'session_id' => $sessionId ?? 'unknown',
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]));
    
    // Limpiar sesión en caso de error
    unset($_SESSION['pending_subscription_session']);
    unset($_SESSION['pending_subscription_data']);
    
    // Mensaje de error más amigable
    $errorMessage = 'Error procesando tu pago. ';
    
    if (strpos($e->getMessage(), 'ya existe') !== false) {
        $errorMessage .= 'Ya existe una cuenta con este email.';
    } elseif (strpos($e->getMessage(), 'no está activa') !== false) {
        $errorMessage .= 'La suscripción necesita ser activada. Contacta soporte.';
    } else {
        $errorMessage .= 'Por favor contacta nuestro soporte técnico.';
    }
    
    // Redirigir con mensaje de error
    header('Location: ../../planes.php?error=' . urlencode($errorMessage));
    exit;
}

// ELIMINAR TODO EL CÓDIGO DE AQUÍ HACIA ABAJO
// El archivo debe terminar aquí, sin código adicional
?>