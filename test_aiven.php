<?php
/**
 * CLI Tool to verify Aiven MySQL connection
 * Usage: php test_aiven.php <host> <port> <user> <password> <database>
 */
$host = $argv[1] ?? getenv('DB_SERVER') ?? 'localhost';
$port = (int)($argv[2] ?? getenv('DB_PORT') ?? 3306);
$user = $argv[3] ?? getenv('DB_USERNAME') ?? 'avnadmin';
$pass = $argv[4] ?? getenv('DB_PASSWORD') ?? '';
$db   = $argv[5] ?? getenv('DB_NAME') ?? 'defaultdb';

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
    echo "Existing Tables (" . count($tables) . "): " . (empty($tables) ? "None (Ready for import)" : implode(", ", $tables)) . "\n";
    $conn->close();
    exit(0);
} else {
    echo "\n[FAILED] Connection error: " . ($conn->connect_error ?: mysqli_connect_error()) . "\n";
    exit(1);
}
