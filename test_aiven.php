<?php
/**
 * CLI Tool to verify Aiven MySQL connection
 * Usage: php test_aiven.php <host> <port> <user> <password> <database>
 */
$host = !empty($argv[1]) ? $argv[1] : (getenv('DB_SERVER') ?: 'mysql-1a85412b-maheshchouhanmsk12-c513.a.aivencloud.com');
$port = !empty($argv[2]) ? (int)$argv[2] : (int)(getenv('DB_PORT') ?: 26284);
$user = !empty($argv[3]) ? $argv[3] : (getenv('DB_USERNAME') ?: 'avnadmin');
$pass = isset($argv[4]) ? $argv[4] : ((getenv('DB_PASSWORD') !== false && getenv('DB_PASSWORD') !== '') ? getenv('DB_PASSWORD') : base64_decode('QVZOU19MU0xEdDV6NGFkSzQ5R0xsVFQ4'));
$db   = !empty($argv[5]) ? $argv[5] : (getenv('DB_NAME') ?: 'defaultdb');

echo "========================================\n";
echo "Testing connection to Aiven MySQL Server\n";
echo "========================================\n";
echo "Host:     {$host}:{$port}\n";
echo "User:     {$user}\n";
echo "Database: {$db}\n";
echo "SSL:      REQUIRED (MYSQLI_CLIENT_SSL)\n";
echo "----------------------------------------\n";

mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 15);
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

$start = microtime(true);
$connected = @$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);
$duration = round(microtime(true) - $start, 3);

if ($connected && !$conn->connect_error) {
    echo "\n[SUCCESS] Connected to Aiven MySQL in {$duration}s!\n";
    echo "MySQL Server Version: " . $conn->server_info . "\n";
    echo "Protocol Version:      " . $conn->protocol_version . "\n";
    
    // Check tables
    $tables = [];
    $res = $conn->query("SHOW TABLES");
    if ($res) {
        while ($row = $res->fetch_array()) {
            $tables[] = $row[0];
        }
    }
    echo "Existing Tables (" . count($tables) . "): " . implode(", ", $tables) . "\n";
    
    // Check admin
    $admin = $conn->query("SELECT id, email, password FROM users WHERE email = 'admin@admin.com'");
    if ($admin && $admin->num_rows > 0) {
        echo "Admin User (admin@admin.com): READY in database!\n";
    }
    
    $conn->close();
    exit(0);
} else {
    echo "\n[FAILED] Connection error: " . ($conn->connect_error ?: mysqli_connect_error()) . "\n";
    exit(1);
}
