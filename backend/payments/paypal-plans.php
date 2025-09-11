<?php
// backend/payments/paypal-plans.php
// Configuración de planes de suscripción

require_once 'paypal-config.php';

class PayPalPlans {
    
    // IDs DE PLANES EN SANDBOX (obtener de PayPal Developer Dashboard)
    const SANDBOX_PLANS = [
        'despertar' => 'P-09D60042TK600730VNDA7QQQ',
        'evolucionar' => 'P-0EA45099T7660272YNDA7QQY'
    ];
    
    // IDs DE PLANES EN LIVE (configurar cuando vayas a producción)
    const LIVE_PLANS = [
        'despertar' => 'P-LIVE-DESPERTAR-ID-AQUI',
        'evolucionar' => 'P-LIVE-EVOLUCIONAR-ID-AQUI'
    ];
    
    // INFORMACIÓN DETALLADA DE LOS PLANES
    const PLANS_INFO = [
        'despertar' => [
            'name' => 'Plan Despertar',
            'description' => 'Acceso a contenido inspiracional y herramientas de desarrollo personal',
            'price' => '75.00',
            'currency' => 'USD',
            'interval' => 'MONTH',
            'interval_count' => 1,
            'features' => [
                'Acceso a contenido premium',
                'Newsletter semanal',
                'Recursos descargables',
                'Comunidad exclusiva',
                'Soporte por email'
            ],
            'category' => 'basic',
            'popular' => false
        ],
        'evolucionar' => [
            'name' => 'Plan Evolucionar',
            'description' => 'Acceso completo + contenido premium y mentoría personalizada',
            'price' => '125.00',
            'currency' => 'USD',
            'interval' => 'MONTH',
            'interval_count' => 1,
            'features' => [
                'Todo lo del Plan Despertar',
                'Mentoría personalizada',
                'Sesiones grupales en vivo',
                'Contenido exclusivo VIP',
                'Soporte prioritario',
                'Certificaciones'
            ],
            'category' => 'premium',
            'popular' => true
        ]
    ];
    
    // OBTENER ID DEL PLAN SEGÚN EL ENTORNO
    public static function getPlanId($planType) {
        $plans = PayPalConfig::SANDBOX_MODE ? self::SANDBOX_PLANS : self::LIVE_PLANS;
        return $plans[$planType] ?? null;
    }
    
    // OBTENER INFORMACIÓN COMPLETA DE UN PLAN
    public static function getPlanInfo($planType) {
        if (!isset(self::PLANS_INFO[$planType])) {
            return null;
        }
        
        $plan = self::PLANS_INFO[$planType];
        $plan['plan_id'] = self::getPlanId($planType);
        $plan['type'] = $planType;
        
        return $plan;
    }
    
    // OBTENER TODOS LOS PLANES
    public static function getAllPlans() {
        $plans = [];
        foreach (array_keys(self::PLANS_INFO) as $planType) {
            $plans[$planType] = self::getPlanInfo($planType);
        }
        return $plans;
    }
    
    // VALIDAR SI UN TIPO DE PLAN ES VÁLIDO
    public static function isValidPlan($planType) {
        return isset(self::PLANS_INFO[$planType]) && !is_null(self::getPlanId($planType));
    }
    
    // OBTENER PRECIO FORMATEADO
    public static function getFormattedPrice($planType) {
        $plan = self::getPlanInfo($planType);
        if (!$plan) return null;
        
        return '$' . number_format(floatval($plan['price']), 0) . ' ' . $plan['currency'] . '/mes';
    }
    
    // CALCULAR DESCUENTOS (para futuras promociones)
    public static function applyDiscount($planType, $discountPercent = 0) {
        $plan = self::getPlanInfo($planType);
        if (!$plan || $discountPercent <= 0) return $plan;
        
        $originalPrice = floatval($plan['price']);
        $discountAmount = $originalPrice * ($discountPercent / 100);
        $finalPrice = $originalPrice - $discountAmount;
        
        $plan['original_price'] = $plan['price'];
        $plan['price'] = number_format($finalPrice, 2);
        $plan['discount_percent'] = $discountPercent;
        $plan['discount_amount'] = number_format($discountAmount, 2);
        $plan['has_discount'] = true;
        
        return $plan;
    }
    
    // OBTENER COMPARACIÓN DE PLANES (para mostrar en la página)
    public static function getPlansComparison() {
        return [
            'despertar' => self::getPlanInfo('despertar'),
            'evolucionar' => self::getPlanInfo('evolucionar')
        ];
    }
    
    // VALIDAR CONFIGURACIÓN DE PLANES
    public static function validatePlansConfig() {
        $errors = [];
        
        foreach (array_keys(self::PLANS_INFO) as $planType) {
            $planId = self::getPlanId($planType);
            if (empty($planId) || $planId === 'P-SANDBOX-' . strtoupper($planType) . '-ID-AQUI') {
                $errors[] = "Plan ID no configurado para: {$planType}";
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    // OBTENER CONFIGURACIÓN PARA JAVASCRIPT
    public static function getJSConfig() {
        $plans = [];
        foreach (self::getAllPlans() as $type => $plan) {
            $plans[$type] = [
                'name' => $plan['name'],
                'price' => self::getFormattedPrice($type),
                'description' => $plan['description'],
                'features' => $plan['features'],
                'popular' => $plan['popular']
            ];
        }
        
        return [
            'plans' => $plans,
            'currency' => PayPalConfig::DEFAULT_CURRENCY,
            'environment' => PayPalConfig::SANDBOX_MODE ? 'sandbox' : 'live'
        ];
    }
    
    // MÉTODO PARA CREAR DATOS DE SUSCRIPCIÓN PARA PAYPAL
    public static function buildSubscriptionData($planType, $userData) {
        $plan = self::getPlanInfo($planType);
        if (!$plan) {
            throw new Exception('Plan no encontrado: ' . $planType);
        }
        
        return [
            'plan_id' => $plan['plan_id'],
            'start_time' => date('c', strtotime(PayPalConfig::SUBSCRIPTION_START_DELAY)),
            'subscriber' => [
                'name' => [
                    'given_name' => $userData['firstName'],
                    'surname' => $userData['lastName']
                ],
                'email_address' => $userData['email']
            ],
            'application_context' => [
                'brand_name' => PayPalConfig::APP_NAME,
                'locale' => PayPalConfig::PAYMENT_LOCALE,
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'payment_method' => [
                    'payer_selected' => 'PAYPAL',
                    'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED'
                ],
                'return_url' => PayPalConfig::getReturnURL(),
                'cancel_url' => PayPalConfig::getCancelURL()
            ]
        ];
    }
}