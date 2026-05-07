<?php

if (!function_exists('estoque_env')) {
    function estoque_env(string $key, string $default = ''): string
    {
        static $loaded = false;

        if (!$loaded) {
            $envPath = dirname(__DIR__) . '/.env';
            if (is_readable($envPath)) {
                $values = parse_ini_file($envPath, false, INI_SCANNER_RAW);
                if (is_array($values)) {
                    foreach ($values as $envKey => $envValue) {
                        if (getenv($envKey) === false) {
                            putenv($envKey . '=' . $envValue);
                            $_ENV[$envKey] = $envValue;
                        }
                    }
                }
            }
            $loaded = true;
        }

        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

return [
    'host' => estoque_env('SMTP_HOST', 'email-ssl.com.br'),
    'user' => estoque_env('SMTP_USER'),
    'pass' => estoque_env('SMTP_PASS'),
    'port' => (int) estoque_env('SMTP_PORT', '465'),
    'secure' => estoque_env('SMTP_SECURE', 'ssl'),
    'from_name' => estoque_env('SMTP_FROM_NAME', 'Estoque TI'),
];
?>
