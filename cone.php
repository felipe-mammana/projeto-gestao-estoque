<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!function_exists('estoque_env')) {
    function estoque_env(string $key, string $default = ''): string
    {
        static $loaded = false;

        if (!$loaded) {
            $envPath = __DIR__ . '/.env';
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

$host = estoque_env('DB_HOST', 'localhost');
$username = estoque_env('DB_USER', 'root');
$password = estoque_env('DB_PASS', '');
$dbname = estoque_env('DB_NAME', 'estoqueti');
$port = (int) estoque_env('DB_PORT', '3306');

$cone = new mysqli($host, $username, $password, $dbname, $port);

if ($cone->connect_error) {
    die("Erro de conexao MySQL: " . $cone->connect_error);
}
?>
