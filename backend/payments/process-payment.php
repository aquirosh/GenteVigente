<?php
// backend/payments/process-payment.php
// Procesamiento de pagos con la nueva estructura modular

session_start();
require_once '../db.php';
require_once 'paypal-config.php';
require_once 'paypal-plans.php';
require_once 'paypal-api.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos inválidos');
    }
    
    // Validar datos requeridos
    $planType = trim($input['plan'] ?? '');
    $firstName = trim($input['firstName'] ?? '');
    $lastName = trim($input['lastName'] ?? '');
    $email = trim($input['email'] ?? '');
    
    // Validaciones básicas
    if (empty($planType)) {
        throw new Exception('Tipo de plan requerido');
    }
    
    if (!PayPalPlans::isValidPlan($planType)) {
        throw new Exception('Plan inválido: ' . $planType);
    }
    
    if (empty($firstName) || strlen($firstName) < 2) {
        throw new Exception('Nombre inválido (mínimo 2 caracteres)');
    }
    
    if (empty($lastName) || strlen($lastName) < 2) {
        throw new Exception('Apellido inválido (mínimo 2 caracteres)');
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    // Verificar si ya existe un usuario con este email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe una cuenta con este email. Inicia sesión o usa otro email.');
    }
    
    // Limpiar registros expirados de suscripciones pendientes
    $stmt = $pdo->prepare("DELETE FROM pending_subscriptions WHERE expires_at < NOW()");
    $stmt->execute();
    
    // Verificar si hay una suscripción pendiente para este email
    $stmt = $pdo->prepare("
        SELECT id, paypal_subscription_id 
        FROM pending_subscriptions 
        WHERE email = ? AND status = 'pending'
    ");
    $stmt->execute([$email]);
    if ($existingPending = $stmt->fetch()) {
        throw new Exception('Ya hay una suscripción en proceso para este email. Completa el pago o espera unos minutos.');
    }
    
    // Preparar datos del usuario
    $userData = [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email
    ];
    
    // Obtener información del plan
    $planInfo = PayPalPlans::getPlanInfo($planType);
    if (!$planInfo) {
        throw new Exception('Información del plan no encontrada');
    }
    
    // Iniciar transacción en base de datos
    $pdo->beginTransaction();
    
    try {
        // Crear instancia de PayPal API
        $paypal = new PayPalAPI();
        
        // Crear suscripción en PayPal
        $subscription = $paypal->createSubscription($planType, $userData);
        
        // Generar session ID único
        $sessionId = uniqid('gv_session_', true);
        
        // Guardar datos temporales en la base de datos
        $stmt = $pdo->prepare("
            INSERT INTO pending_subscriptions 
            (session_id, email, first_name, last_name, plan_type, paypal_subscription_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $sessionId,
            $email,
            $firstName,
            $lastName,
            $planType,
            $subscription['id']
        ]);
        
        // Registrar transacción inicial
        $stmt = $pdo->prepare("
            INSERT INTO paypal_transactions 
            (subscription_id, transaction_type, status, amount, currency, webhook_data) 
            VALUES (?, 'subscription_created', 'pending', ?, ?, ?)
        ");
        
        $stmt->execute([
            $subscription['id'],
            $planInfo['price'],
            $planInfo['currency'],
            json_encode([
                'subscription' => $subscription,
                'plan_info' => $planInfo,
                'user_data' => $userData,
                'created_at' => date('c')
            ])
        ]);
        
        $pdo->commit();
        
        // Guardar session ID en la sesión del usuario
        $_SESSION['pending_subscription_session'] = $sessionId;
        $_SESSION['pending_subscription_data'] = [
            'plan_type' => $planType,
            'email' => $email,
            'subscription_id' => $subscription['id'],
            'created_at' => time()
        ];
        
        // Obtener URL de aprobación
        $approvalUrl = '';
        foreach ($subscription['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approvalUrl = $link['href'];
                break;
            }
        }
        
        if (empty($approvalUrl)) {
            throw new Exception('No se pudo obtener URL de pago de PayPal');
        }
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'approval_url' => $approvalUrl,
            'subscription_id' => $subscription['id'],
            'plan_info' => [
                'name' => $planInfo['name'],
                'price' => PayPalPlans::getFormattedPrice($planType)
            ],
            'message' => 'Suscripción creada exitosamente. Redirigiendo a PayPal...'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    // Log del error para debugging
    error_log('Error en process-payment.php: ' . $e->getMessage());
    
    // Log adicional con contexto
    error_log('PayPal Error Context: ' . json_encode([
        'plan_type' => $planType ?? 'unknown',
        'email' => $email ?? 'unknown',
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]));
    
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'PAYMENT_PROCESSING_ERROR'
    ]);
}
?>