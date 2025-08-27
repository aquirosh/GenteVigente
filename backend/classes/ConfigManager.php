<?php
// backend/classes/ConfigManager.php

class ConfigManager {
    private static $configs = [];
    
    public static function load($configName) {
        if (!isset(self::$configs[$configName])) {
            $possiblePaths = [
                __DIR__ . "/../../config/{$configName}.php",
                __DIR__ . "/../../{$configName}.php"
            ];
            
            $configPath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $configPath = $path;
                    break;
                }
            }
            
            if (!$configPath) {
                throw new Exception("Config not found: {$configName}");
            }
            
            self::$configs[$configName] = require $configPath;
        }
        
        return self::$configs[$configName];
    }
    
    public static function get($configName, $key = null) {
        $config = self::load($configName);
        
        if ($key === null) return $config;
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) return null;
            $value = $value[$k];
        }
        
        return $value;
    }
    
    public static function isLocalhost() {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return in_array($host, ['localhost', '127.0.0.1']) 
               || str_contains($host, '.local');
    }
}
?>
?>