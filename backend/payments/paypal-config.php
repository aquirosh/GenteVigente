<?php
// backend/payments/paypal-config.php
// Configuración principal de PayPal

class PayPalConfig {
    // CONFIGURACIÓN DE ENTORNO
    const SANDBOX_MODE = true; // Cambiar a FALSE en producción
    
    // CREDENCIALES SANDBOX (Desarrollo)
    const SANDBOX_CLIENT_ID = '';
    const SANDBOX_CLIENT_SECRET = '';
    
    // CREDENCIALES LIVE (Producción)
    const LIVE_CLIENT_ID = 'TU_LIVE_CLIENT_ID_AQUI';
    const LIVE_CLIENT_SECRET = 'TU_LIVE_CLIENT_SECRET_AQUI';
    
    // CONFIGURACIÓN DEL SITIO
    const SITE_URL = 'https://gentevigente.com'; // CAMBIAR POR TU DOMINIO REAL
    const APP_NAME = 'Gente Vigente';
    const SUPPORT_EMAIL = 'soporte@gentevigente.com';
    
    // CONFIGURACIÓN DE PAGOS
    const DEFAULT_CURRENCY = 'USD';
    const PAYMENT_LOCALE = 'es-CR';
    const SUBSCRIPTION_START_DELAY = '+5 minutes'; // Cuánto tiempo esperar antes de activar
    
    // URLs DINÁMICAS BASADAS EN EL ENTORNO
    public static function getBaseURL() {
        return self::SANDBOX_MODE ? 
            'https://api-m.sandbox.paypal.com' : 
            'https://api-m.paypal.com';
    }
    
    public static function getWebURL() {
        return self::SANDBOX_MODE ? 
            'https://www.sandbox.paypal.com' : 
            'https://www.paypal.com';
    }
    
    // CREDENCIALES DINÁMICAS
    public static function getClientId() {
        return self::SANDBOX_MODE ? 
            self::SANDBOX_CLIENT_ID : 
            self::LIVE_CLIENT_ID;
    }
    
    public static function getClientSecret() {
        return self::SANDBOX_MODE ? 
            self::SANDBOX_CLIENT_SECRET : 
            self::LIVE_CLIENT_SECRET;
    }
    
    // URLs DE RETORNO
    public static function getReturnURL() {
        return self::SITE_URL . '/backend/payments/success.php';
    }
    
    public static function getCancelURL() {
        return self::SITE_URL . '/planes.php?cancelled=1';
    }
    
    public static function getWebhookURL() {
        return self::SITE_URL . '/backend/payments/webhook.php';
    }
    
    // CONFIGURACIÓN DE TIMEOUT Y REINTENTOS
    const CURL_TIMEOUT = 30; // segundos
    const MAX_RETRIES = 3;
    const TOKEN_CACHE_MARGIN = 60; // segundos de margen antes de que expire el token
    
    // CONFIGURACIÓN DE LOGS
    const ENABLE_LOGGING = true;
    const LOG_LEVEL = 'INFO'; // DEBUG, INFO, WARNING, ERROR
    
    // MÉTODO PARA VALIDAR CONFIGURACIÓN
    public static function validateConfig() {
        $errors = [];
        
        // Validar credenciales
        if (empty(self::getClientId())) {
            $errors[] = 'Client ID no configurado';
        }
        
        if (empty(self::getClientSecret())) {
            $errors[] = 'Client Secret no configurado';
        }
        
        // Validar URLs
        if (!filter_var(self::SITE_URL, FILTER_VALIDATE_URL)) {
            $errors[] = 'URL del sitio inválida';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    // MÉTODO PARA OBTENER INFORMACIÓN DEL ENTORNO
    public static function getEnvironmentInfo() {
        return [
            'mode' => self::SANDBOX_MODE ? 'sandbox' : 'live',
            'api_url' => self::getBaseURL(),
            'client_id' => substr(self::getClientId(), 0, 8) . '...',
            'site_url' => self::SITE_URL,
            'app_name' => self::APP_NAME
        ];
    }
}