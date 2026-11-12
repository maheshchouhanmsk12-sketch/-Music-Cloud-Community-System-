<?php
/**
 * Database Connection & Environment Configuration
 * Supports Aiven Cloud MySQL (SSL enforced, custom 5-digit ports), Persistent config, and Local container fallback
 */

// Dynamic BASE_URL detection & Env Support
$env_base_url = getenv('BASE_URL');
if (!empty($env_base_url)) {
    if (substr($env_base_url, -1) !== '/') {
        $env_base_url .= '/';
    }
    if (!defined('base_url')) define('base_url', $env_base_url);
} else {
    if (isset($_SERVER['HTTP_HOST'])) {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                 (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'http://';
        // Strip internal port (e.g. :10000) if leaked behind reverse proxy
        $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);
        if (!defined('base_url')) define('base_url', $proto . $host . '/');
    } else {
        if (!defined('base_url')) define('base_url', 'http://localhost/');
    }
}

// 1. Check for saved credentials in db_config.json (saved via db_setup.php)
$saved_cfg = [];
$config_file = __DIR__ . '/db_config.json';
if (file_exists($config_file)) {
    $raw_cfg = @file_get_contents($config_file);
    if (!empty($raw_cfg)) {
        $saved_cfg = @json_decode($raw_cfg, true) ?: [];
    }
}

// 2. Resolve Database Credentials: Env Vars > Saved Config > Defaults
$env_server = getenv('DB_SERVER') ?: getenv('DB_HOST');

if (!empty($env_server) && !in_array($env_server, ['localhost', '127.0.0.1', '::1'])) {
    $db_server   = $env_server;
    $db_port     = getenv('DB_PORT') ?: (getenv('MYSQL_PORT') ?: 26284);
    $db_username = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'avnadmin');
    $db_password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    $db_name     = getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: 'defaultdb');
    $db_ssl      = getenv('DB_SSL');
} elseif (!empty($saved_cfg['db_server']) && !in_array($saved_cfg['db_server'], ['localhost', '127.0.0.1', '::1'])) {
    $db_server   = $saved_cfg['db_server'];
    $db_port     = $saved_cfg['db_port'] ?? 26284;
    $db_username = $saved_cfg['db_username'] ?? 'avnadmin';
    $db_password = $saved_cfg['db_password'] ?? '';
    $db_name     = $saved_cfg['db_name'] ?? 'defaultdb';
    $db_ssl      = $saved_cfg['db_ssl'] ?? true;
} else {
    $db_server   = $env_server ?: 'localhost';
    $db_port     = getenv('DB_PORT') ?: (getenv('MYSQL_PORT') ?: 3306);
    $db_username = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'root');
    $db_password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    $db_name     = getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: 'music_db');
    $db_ssl      = getenv('DB_SSL');
}

if ($db_ssl === null || $db_ssl === false) {
    // Auto-detect SSL: Enable for Aiven Cloud or remote hosts or non-default ports
    $db_ssl = ((int)$db_port !== 3306 || strpos($db_server, 'aivencloud.com') !== false || !in_array($db_server, ['localhost', '127.0.0.1', '::1'])) ? true : false;
} else {
    $db_ssl = filter_var($db_ssl, FILTER_VALIDATE_BOOLEAN) || $db_ssl === '1' || strtolower($db_ssl) === 'true';
}

if (!defined('DB_SERVER'))   define('DB_SERVER', $db_server);
if (!defined('DB_PORT'))     define('DB_PORT', (int)$db_port);
if (!defined('DB_USERNAME')) define('DB_USERNAME', $db_username);
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', $db_password);
if (!defined('DB_NAME'))     define('DB_NAME', $db_name);
if (!defined('DB_SSL'))      define('DB_SSL', $db_ssl);

// Establish connection
if (!isset($conn) || !$conn || !($conn instanceof mysqli)) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = mysqli_init();
    $connected = false;

    if ($conn) {
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 8);
        $flags = 0;

        if (DB_SSL) {
            $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
            $flags = MYSQLI_CLIENT_SSL;
        }

        $connected = @$conn->real_connect(
            DB_SERVER,
            DB_USERNAME,
            DB_PASSWORD,
            DB_NAME,
            (int)DB_PORT,
            NULL,
            $flags
        );

        // Fallback attempt without SSL
        if (!$connected && $flags !== 0) {
            $conn = mysqli_init();
            $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 6);
            $connected = @$conn->real_connect(
                DB_SERVER,
                DB_USERNAME,
                DB_PASSWORD,
                DB_NAME,
                (int)DB_PORT
            );
        }

        // Secondary fallback to local container database if remote connection failed
        if (!$connected && DB_SERVER !== 'localhost' && DB_SERVER !== '127.0.0.1') {
            $local_conn = mysqli_init();
            $local_conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
            if (@$local_conn->real_connect('localhost', 'root', '', 'music_db', 3306)) {
                $conn = $local_conn;
                $connected = true;
            }
        }

        if ($connected && !$conn->connect_error) {
            $conn->set_charset("utf8mb4");
        } else {
            $db_error = $conn ? $conn->connect_error : mysqli_connect_error();
            error_log("MySQL Connection Warning: " . $db_error);
        }
    }
}
