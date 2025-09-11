<?php
// backend/payments/paypal-api.php
// Clase para manejar todas las operaciones de la API de PayPal

require_once 'paypal-config.php';
require_once 'paypal-plans.php';

class PayPalAPI {
    private $baseURL;
    private $clientId;
    private $clientSecret;
    private $accessToken;
    private $tokenExpiry;
    private $lastError;
    
    public function __construct() {
        $this->baseURL = PayPalConfig::getBaseURL();
        $this->clientId = PayPalConfig::getClientId();
        $this->clientSecret = PayPalConfig::getClientSecret();
        
        // Validar configuración al instanciar
        $configValidation = PayPalConfig::validateConfig();
        if ($configValidation !== true) {
            throw new Exception('Configuración PayPal inválida: ' . implode(', ', $configValidation));
        }
    }
    
    // ===============================
    // GESTIÓN DE AUTENTICACIÓN
    // ===============================
    
    private function getAccessToken() {
        // Verificar si el token actual sigue siendo válido
        if ($this->accessToken && $this->tokenExpiry > time()) {
            return $this->accessToken;
        }
        
        $this->log('Obteniendo nuevo token de acceso');
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseURL . '/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $this->clientId . ':' . $this->clientSecret,
            CURLOPT_TIMEOUT => PayPalConfig::CURL_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US',
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->lastError = 'cURL Error: ' . $error;
            throw new Exception($this->lastError);
        }
        
        if ($httpCode !== 200) {
            $this->lastError = 'Error obteniendo token PayPal: HTTP ' . $httpCode . ' - ' . $response;
            throw new Exception($this->lastError);
        }
        
        $data = json_decode($response, true);
        if (!isset($data['access_token'])) {
            $this->lastError = 'Respuesta inválida de PayPal: ' . $response;
            throw new Exception($this->lastError);
        }
        
        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = time() + ($data['expires_in'] - PayPalConfig::TOKEN_CACHE_MARGIN);
        
        $this->log('Token obtenido exitosamente, expira en: ' . date('Y-m-d H:i:s', $this->tokenExpiry));
        
        return $this->accessToken;
    }
    
    // ===============================
    // GESTIÓN DE SUSCRIPCIONES
    // ===============================
    
    public function createSubscription($planType, $userData) {
        $this->log("Creando suscripción para plan: {$planType}, usuario: {$userData['email']}");
        
        // Validar plan
        if (!PayPalPlans::isValidPlan($planType)) {
            throw new Exception('Tipo de plan inválido: ' . $planType);
        }
        
        // Validar datos del usuario
        $this->validateUserData($userData);
        
        $token = $this->getAccessToken();
        $subscriptionData = PayPalPlans::buildSubscriptionData($planType, $userData);
        
        $requestId = uniqid('gv_sub_', true);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseURL . '/v1/billing/subscriptions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($subscriptionData),
            CURLOPT_TIMEOUT => PayPalConfig::CURL_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
                'PayPal-Request-Id: ' . $requestId,
                'Prefer: return=representation'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->lastError = 'cURL Error al crear suscripción: ' . $error;
            throw new Exception($this->lastError);
        }
        
        if ($httpCode !== 201) {
            $this->lastError = 'Error creando suscripción en PayPal: HTTP ' . $httpCode . ' - ' . $response;
            $this->log($this->lastError, 'ERROR');
            throw new Exception('Error creando suscripción en PayPal. Inténtalo nuevamente.');
        }
        
        $subscriptionResponse = json_decode($response, true);
        
        if (!isset($subscriptionResponse['id'])) {
            $this->lastError = 'Respuesta inválida de PayPal al crear suscripción';
            throw new Exception($this->lastError);
        }
        
        $this->log("Suscripción creada exitosamente: {$subscriptionResponse['id']}");
        
        return $subscriptionResponse;
    }
    
    public function getSubscription($subscriptionId) {
        $this->log("Obteniendo información de suscripción: {$subscriptionId}");
        
        $token = $this->getAccessToken();
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseURL . '/v1/billing/subscriptions/' . $subscriptionId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => PayPalConfig::CURL_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->lastError = 'cURL Error al obtener suscripción: ' . $error;
            throw new Exception($this->lastError);
        }
        
        if ($httpCode !== 200) {
            $this->lastError = 'Error obteniendo suscripción: HTTP ' . $httpCode . ' - ' . $response;
            throw new Exception($this->lastError);
        }
        
        $subscription = json_decode($response, true);
        
        $this->log("Suscripción obtenida: {$subscriptionId}, estado: {$subscription['status']}");
        
        return $subscription;
    }
    
    public function cancelSubscription($subscriptionId, $reason = 'Usuario solicitó cancelación') {
        $this->log("Cancelando suscripción: {$subscriptionId}");
        
        $token = $this->getAccessToken();
        
        $cancelData = [
            'reason' => $reason
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseURL . '/v1/billing/subscriptions/' . $subscriptionId . '/cancel',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($cancelData),
            CURLOPT_TIMEOUT => PayPalConfig::CURL_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->lastError = 'cURL Error al cancelar suscripción: ' . $error;
            throw new Exception($this->lastError);
        }
        
        if ($httpCode !== 204) {
            $this->lastError = 'Error cancelando suscripción: HTTP ' . $httpCode . ' - ' . $response;
            throw new Exception($this->lastError);
        }
        
        $this->log("Suscripción cancelada exitosamente: {$subscriptionId}");
        
        return true;
    }
    
    // ===============================
    // VALIDACIONES Y UTILIDADES
    // ===============================
    
    private function validateUserData($userData) {
        $required = ['firstName', 'lastName', 'email'];
        
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new Exception("Campo requerido faltante: {$field}");
            }
        }
        
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido: ' . $userData['email']);
        }
        
        if (strlen($userData['firstName']) < 2) {
            throw new Exception('Nombre debe tener al menos 2 caracteres');
        }
        
        if (strlen($userData['lastName']) < 2) {
            throw new Exception('Apellido debe tener al menos 2 caracteres');
        }
    }
    
    // ===============================
    // WEBHOOKS
    // ===============================
    
    public function verifyWebhookSignature($headers, $body, $webhookId) {
        // Implementar verificación de webhook según la documentación de PayPal
        // https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature
        
        $token = $this->getAccessToken();
        
        $verifyData = [
            'auth_algo' => $headers['PAYPAL-AUTH-ALGO'] ?? '',
            'cert_id' => $headers['PAYPAL-CERT-ID'] ?? '',
            'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
            'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
            'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
            'webhook_id' => $webhookId,
            'webhook_event' => json_decode($body, true)
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseURL . '/v1/notifications/verify-webhook-signature',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($verifyData),
            CURLOPT_TIMEOUT => PayPalConfig::CURL_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return isset($result['verification_status']) && $result['verification_status'] === 'SUCCESS';
        }
        
        return false;
    }
    
    // ===============================
    // LOGS Y DEBUG
    // ===============================
    
    private function log($message, $level = 'INFO') {
        if (!PayPalConfig::ENABLE_LOGGING) return;
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] PayPalAPI: {$message}" . PHP_EOL;
        
        // En desarrollo, también mostrar en error_log
        if (PayPalConfig::SANDBOX_MODE) {
            error_log(trim($logMessage));
        }
        
        // Aquí puedes agregar escritura a archivo si lo necesitas
        // file_put_contents('logs/paypal.log', $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    public function getEnvironmentInfo() {
        return PayPalConfig::getEnvironmentInfo();
    }
    
    // ===============================
    // MÉTODOS DE TESTING
    // ===============================
    
    public function testConnection() {
        try {
            $token = $this->getAccessToken();
            return [
                'success' => true,
                'message' => 'Conexión exitosa con PayPal',
                'environment' => PayPalConfig::SANDBOX_MODE ? 'sandbox' : 'live',
                'token_prefix' => substr($token, 0, 20) . '...'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
                'environment' => PayPalConfig::SANDBOX_MODE ? 'sandbox' : 'live'
            ];
        }
    }
}