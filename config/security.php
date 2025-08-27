<?php
// config/security.php - Configuración de seguridad para localhost
return [
    'security' => [
        'environment' => 'development', // development para localhost
        'https_only' => false, // false para localhost
        'debug_mode' => true, // true para desarrollo
        
        'session' => [
            'secure' => false, // false para localhost (HTTP)
            'httponly' => true,
            'samesite' => 'Lax', // Lax para desarrollo
            'lifetime' => 7200, // 2 horas
            'regenerate_interval' => 300, // 5 minutos
            'name' => 'GENTE_VIGENTE_DEV'
        ],
        
        'csrf' => [
            'token_name' => 'csrf_token',
            'regenerate_on_use' => false, // false para desarrollo
            'expire_time' => 3600
        ],
        
        'rate_limiting' => [
            'login_attempts' => 10, // Más permisivo en desarrollo
            'lockout_time' => 300, // 5 minutos en vez de 15
            'registration_per_hour' => 50, // Más permisivo
            'contact_per_day' => 100
        ],
        
        'password' => [
            'min_length' => 6, // Más corto para desarrollo
            'require_special' => false, // Opcional en desarrollo
            'require_numbers' => false,
            'require_uppercase' => false,
            'require_lowercase' => true,
            'max_age_days' => 365, // Un año en desarrollo
            'history_count' => 3
        ],
        
        'logging' => [
            'enable' => true,
            'log_file' => 'logs/security_dev.log',
            'log_failed_logins' => true,
            'log_admin_actions' => true,
            'retention_days' => 7 // Solo una semana en desarrollo
        ]
    ]
];
?>