<?php
// backend/classes/Security.php

require_once __DIR__ . '/ConfigManager.php';

class Security {
    private static $config;
    
    public static function init() {
        self::$config = ConfigManager::get('security', 'security');
        self::configureSession();
        
        // Crear directorio de logs si no existe
        $logDir = dirname(self::$config['logging']['log_file']);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private static function configureSession() {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        
        // Configuración de sesión para localhost
        ini_set('session.cookie_secure', self::$config['session']['secure'] ? '1' : '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', self::$config['session']['samesite']);
        ini_set('session.gc_maxlifetime', self::$config['session']['lifetime']);
        session_name(self::$config['session']['name']);
        
        session_start();
        
        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        }
        
        $timeSinceRegeneration = time() - $_SESSION['last_regeneration'];
        if ($timeSinceRegeneration > self::$config['session']['regenerate_interval']) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    private static function setSecurityHeaders() {
        if (headers_sent()) return;
        
        // Headers básicos para desarrollo
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Solo aplicar HTTPS headers si no es localhost
        if (!ConfigManager::isLocalhost()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
    
    // ✅ Protección CSRF
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Verificar expiración
        $tokenAge = time() - $_SESSION['csrf_token_time'];
        if ($tokenAge > self::$config['csrf']['expire_time']) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // ✅ Sanitización de entrada
    public static function sanitizeInput($data, $type = 'string') {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $data);
        }
        
        switch ($type) {
            case 'email':
                return filter_var(trim($data), FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var(trim($data), FILTER_SANITIZE_URL);
            case 'string':
            default:
                return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
    }
    
    // ✅ Validación de entrada
    public static function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Required check
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "El campo {$field} es requerido";
                continue;
            }
            
            if (empty($value)) continue; // Skip validation if empty and not required
            
            // Type validation
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Email inválido";
                        }
                        break;
                    case 'string':
                        if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                            $errors[$field] = "Mínimo {$rule['min_length']} caracteres";
                        }
                        if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                            $errors[$field] = "Máximo {$rule['max_length']} caracteres";
                        }
                        break;
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    // ✅ Rate limiting básico
    public static function checkRateLimit($identifier, $action, $limit = null) {
        if (!$limit) {
            $limit = self::$config['rate_limiting'][$action] ?? 10;
        }
        
        $key = $action . '_' . $identifier;
        $attempts = $_SESSION['rate_limit'][$key] ?? [];
        $now = time();
        $timeWindow = 3600; // 1 hora
        
        // Limpiar intentos antiguos
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        if (count($attempts) >= $limit) {
            self::logSecurityEvent('rate_limit_exceeded', [
                'identifier' => $identifier,
                'action' => $action,
                'attempts' => count($attempts)
            ]);
            return false;
        }
        
        // Registrar intento actual
        $attempts[] = $now;
        $_SESSION['rate_limit'][$key] = $attempts;
        
        return true;
    }
    
    // ✅ Validación de contraseñas
    public static function validatePassword($password) {
        $errors = [];
        $config = self::$config['password'];
        
        if (strlen($password) < $config['min_length']) {
            $errors[] = "La contraseña debe tener al menos {$config['min_length']} caracteres";
        }
        
        if ($config['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Debe contener al menos una mayúscula";
        }
        
        if ($config['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Debe contener al menos una minúscula";
        }
        
        if ($config['require_numbers'] && !preg_match('/\d/', $password)) {
            $errors[] = "Debe contener al menos un número";
        }
        
        if ($config['require_special'] && !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\?]/', $password)) {
            $errors[] = "Debe contener al menos un carácter especial";
        }
        
        // Contraseñas comunes (lista básica para desarrollo)
        $commonPasswords = ['password', '123456', 'qwerty', 'admin', 'test', 'password123'];
        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = "La contraseña es demasiado común";
        }
        
        return empty($errors) ? true : $errors;
    }
    
    // ✅ Logging de eventos de seguridad
    public static function logSecurityEvent($event, $details = []) {
        if (!self::$config['logging']['enable']) return;
        
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100),
            'user_id' => $_SESSION['user_id'] ?? null,
            'session_id' => session_id(),
            'details' => $details
        ];
        
        $logMessage = json_encode($logData) . "\n";
        
        // Crear directorio de logs si no existe
        $logDir = dirname(self::$config['logging']['log_file']);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Escribir al log
        file_put_contents(
            self::$config['logging']['log_file'],
            $logMessage,
            FILE_APPEND | LOCK_EX
        );
        
        // También loggear a error_log para desarrollo
        if (self::$config['debug_mode']) {
            error_log("SECURITY EVENT [{$event}]: " . json_encode($details));
        }
    }
    
    // ✅ Debugging para desarrollo
    public static function debugInfo() {
        if (!self::$config['debug_mode']) return;
        
        echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
        echo "<strong>🛡️ Security Debug Info:</strong><br>";
        echo "Environment: " . (ConfigManager::isLocalhost() ? 'LOCALHOST' : 'PRODUCTION') . "<br>";
        echo "Session ID: " . session_id() . "<br>";
        echo "CSRF Token: " . (isset($_SESSION['csrf_token']) ? substr($_SESSION['csrf_token'], 0, 8) . '...' : 'None') . "<br>";
        echo "Rate Limits: " . json_encode($_SESSION['rate_limit'] ?? []) . "<br>";
        echo "</div>";
    }
}
?>