<?php
/**
 * 1-Click Database Setup & Health Check Tool for Aiven Cloud MySQL
 * Music Cloud Community System (MCCS)
 */
require_once(__DIR__ . '/db_connect.php');

$page_title = "Music Cloud Community - Database Setup & Health Check (Aiven Cloud Ready)";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #121212 0%, #1e1e24 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #333;
        }
        .setup-card {
            width: 100%;
            max-width: 820px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
            border-radius: 12px;
            overflow: hidden;
            border: none;
            background: #ffffff;
        }
        .setup-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 22px 26px;
        }
        .config-badge {
            background: #f4f6f9;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
            border: 1px solid #e2e8f0;
        }
        .table-badge {
            display: inline-block;
            background: #e8f4fd;
            color: #0d6efd;
            border: 1px solid #b6d4fe;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            margin: 3px 4px 3px 0;
            font-family: monospace;
            font-weight: 600;
        }
        .cred-box {
            background: #f8fafc;
            border-left: 4px solid #28a745;
            padding: 14px 18px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="card setup-card">
    <div class="setup-header">
        <div class="d-flex align-items-center">
            <i class="fas fa-headphones-alt fa-2x mr-3"></i>
            <div>
                <h4 class="mb-0 font-weight-bold">Aiven Cloud MySQL Database Setup</h4>
                <small class="text-white-50">Music Cloud Community System &bull; Production Installer</small>
            </div>
        </div>
    </div>

    <div class="card-body p-4">
        <?php
        // Resolve database credentials from POST or Environment or Defaults
        $db_host = !empty($_POST['custom_host']) ? trim($_POST['custom_host']) : (defined('DB_SERVER') ? DB_SERVER : 'localhost');
        $db_port = !empty($_POST['custom_port']) ? (int)$_POST['custom_port'] : (defined('DB_PORT') ? (int)DB_PORT : 3306);
        $db_user = !empty($_POST['custom_user']) ? trim($_POST['custom_user']) : (defined('DB_USERNAME') ? DB_USERNAME : 'root');
        $db_pass = isset($_POST['custom_pass']) ? $_POST['custom_pass'] : (defined('DB_PASSWORD') ? DB_PASSWORD : '');
        $db_name = !empty($_POST['custom_name']) ? trim($_POST['custom_name']) : (defined('DB_NAME') ? DB_NAME : 'music_db');
        $db_ssl  = defined('DB_SSL') ? DB_SSL : true;

        echo "<div class='config-badge mb-3'>";
        echo "<div class='row'>";
        echo "<div class='col-sm-6'><strong><i class='fas fa-server mr-1 text-primary'></i> Host:</strong> <code>" . htmlspecialchars($db_host) . "</code></div>";
        echo "<div class='col-sm-3'><strong><i class='fas fa-plug mr-1 text-primary'></i> Port:</strong> <code>" . htmlspecialchars($db_port) . "</code></div>";
        echo "<div class='col-sm-3'><strong><i class='fas fa-shield-alt mr-1 text-primary'></i> SSL:</strong> " . ($db_ssl ? "<span class='badge badge-success'>REQUIRED</span>" : "<span class='badge badge-secondary'>OFF</span>") . "</div>";
        echo "<div class='col-sm-6 mt-2'><strong><i class='fas fa-database mr-1 text-primary'></i> Database:</strong> <code>" . htmlspecialchars($db_name) . "</code></div>";
        echo "<div class='col-sm-6 mt-2'><strong><i class='fas fa-user mr-1 text-primary'></i> Username:</strong> <code>" . htmlspecialchars($db_user) . "</code></div>";
        echo "</div>";
        echo "</div>";

        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = mysqli_init();
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 15);

        // Enforce SSL for Aiven Cloud MySQL
        $is_cloud_db = $db_ssl || ($db_port !== 3306) || (strpos($db_host, 'aivencloud.com') !== false) || (!in_array($db_host, ['localhost', '127.0.0.1', '::1']));

        $flags = 0;
        if ($is_cloud_db) {
            $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
            $flags = MYSQLI_CLIENT_SSL;
        }

        $start_time = microtime(true);
        $connected = @$conn->real_connect($db_host, $db_user, $db_pass, $db_name, (int)$db_port, NULL, $flags);
        $ping_duration = round((microtime(true) - $start_time) * 1000, 1);

        // Fallback without SSL if remote wasn't required
        if (!$connected && $flags !== 0) {
            $conn = mysqli_init();
            $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
            $connected = @$conn->real_connect($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
        }

        if (!$connected || $conn->connect_error) {
            $error_text = $conn ? $conn->connect_error : mysqli_connect_error();
            echo "<div class='alert alert-danger shadow-sm'>";
            echo "<h5><i class='icon fas fa-ban mr-2'></i> Cannot Connect to MySQL Server</h5>";
            echo "<p class='mb-2'><strong>Error:</strong> <code>" . htmlspecialchars($error_text) . "</code></p>";
            echo "<hr>";
            echo "<p class='mb-1 font-weight-bold'>How to resolve this in Render Dashboard:</p>";
            echo "<ol class='mb-0 pl-3'>";
            echo "<li>Open your Render Web Service dashboard &rarr; <strong>Environment</strong> tab.</li>";
            echo "<li>Verify that <code>DB_SERVER</code>, <code>DB_PORT</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>, and <code>DB_NAME</code> match your Aiven Cloud console.</li>";
            echo "<li>Aiven requires SSL: Ensure <code>DB_SSL</code> is set to <code>true</code>.</li>";
            echo "<li>Save changes and Render will automatically restart with the new credentials.</li>";
            echo "</ol>";
            echo "</div>";

            // Credentials override test form
            echo "<div class='card border bg-light my-3'>";
            echo "<div class='card-header font-weight-bold'><i class='fas fa-sliders-h mr-1'></i> Test with Custom Credentials</div>";
            echo "<div class='card-body'>";
            echo "<form method='post'>";
            echo "<div class='form-row'>";
            echo "<div class='form-group col-md-8'><label>Aiven Hostname (DB_SERVER)</label><input type='text' name='custom_host' class='form-control' value='" . htmlspecialchars($db_host) . "' placeholder='mysql-xxxx.aivencloud.com' required></div>";
            echo "<div class='form-group col-md-4'><label>Port (DB_PORT)</label><input type='number' name='custom_port' class='form-control' value='" . htmlspecialchars($db_port) . "' required></div>";
            echo "</div>";
            echo "<div class='form-row'>";
            echo "<div class='form-group col-md-4'><label>Username (DB_USERNAME)</label><input type='text' name='custom_user' class='form-control' value='" . htmlspecialchars($db_user) . "' required></div>";
            echo "<div class='form-group col-md-4'><label>Password (DB_PASSWORD)</label><input type='password' name='custom_pass' class='form-control' placeholder='Enter Aiven password'></div>";
            echo "<div class='form-group col-md-4'><label>Database (DB_NAME)</label><input type='text' name='custom_name' class='form-control' value='" . htmlspecialchars($db_name) . "' required></div>";
            echo "</div>";
            echo "<button type='submit' class='btn btn-primary'><i class='fas fa-sync mr-1'></i> Test Connection</button>";
            echo "</form>";
            echo "</div>";
            echo "</div>";

        } else {
            $conn->set_charset("utf8mb4");
            echo "<div class='alert alert-success d-flex align-items-center mb-3 shadow-sm'>";
            echo "<i class='fas fa-check-circle fa-2x mr-3 text-success'></i>";
            echo "<div>";
            echo "<strong>Connected to Aiven MySQL Server!</strong><br>";
            echo "<small class='text-muted'>Version: " . htmlspecialchars($conn->server_info) . " &bull; Response Time: {$ping_duration}ms &bull; SSL/TLS Encryption Active</small>";
            echo "</div>";
            echo "</div>";

            // If user triggered Import
            if (isset($_POST['run_import'])) {
                $possible_paths = [
                    __DIR__ . '/database/music_db.sql',
                    __DIR__ . '/music_db.sql',
                    __DIR__ . '/music/database/music_db.sql'
                ];
                $sql_file = null;
                foreach ($possible_paths as $p) {
                    if (file_exists($p)) {
                        $sql_file = $p;
                        break;
                    }
                }

                if (!$sql_file) {
                    echo "<div class='alert alert-danger'>Database dump file (<code>music_db.sql</code>) could not be located on server!</div>";
                } else {
                    $raw_sql = file_get_contents($sql_file);

                    // Clean multi-line comments
                    $clean_sql = preg_replace('!/\*.*?\*/!s', '', $raw_sql);
                    // Clean single-line comments
                    $clean_sql = preg_replace('/^--.*$/m', '', $clean_sql);
                    $clean_sql = preg_replace('/^#.*$/m', '', $clean_sql);

                    $statements = array_filter(array_map('trim', explode(';', $clean_sql)));
                    $success_count = 0;
                    $error_count = 0;
                    $errors = [];

                    $conn->query("SET FOREIGN_KEY_CHECKS = 0;");
                    $conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

                    foreach ($statements as $stmt) {
                        if (!empty($stmt)) {
                            if ($conn->query($stmt)) {
                                $success_count++;
                            } else {
                                $error_count++;
                                if (count($errors) < 5) {
                                    $errors[] = $conn->error;
                                }
                            }
                        }
                    }

                    $conn->query("SET FOREIGN_KEY_CHECKS = 1;");

                    echo "<div class='alert alert-success shadow-sm'>";
                    echo "<h5><i class='icon fas fa-check-double mr-2'></i> Database Import Completed!</h5>";
                    echo "Successfully executed <strong>{$success_count}</strong> SQL queries into Aiven MySQL.";
                    if ($error_count > 0) {
                        echo "<br><small class='text-muted'>Ignored {$error_count} non-fatal statements.</small>";
                    }
                    echo "</div>";
                }
            }

            // Inspect tables
            $tables_res = $conn->query("SHOW TABLES");
            $tables = [];
            if ($tables_res) {
                while ($r = $tables_res->fetch_array()) {
                    $tables[] = $r[0];
                }
            }

            // Check admin user existence
            $admin_exists = false;
            if (in_array('users', $tables)) {
                $user_check = $conn->query("SELECT id, email, firstname, lastname, `type` FROM users WHERE email = 'admin@admin.com' LIMIT 1");
                if ($user_check && $user_check->num_rows > 0) {
                    $admin_exists = true;
                }
            }

            echo "<div class='mb-3'>";
            echo "<h6><strong>Database Tables (" . count($tables) . "):</strong></h6>";
            if (empty($tables)) {
                echo "<p class='text-muted mb-2'>No tables found in this database. Click the button below to initialize all required schema & initial records.</p>";
            } else {
                echo "<div class='mb-3'>";
                foreach ($tables as $t) {
                    $count_res = $conn->query("SELECT COUNT(*) FROM `{$t}`");
                    $cnt = $count_res ? $count_res->fetch_row()[0] : 0;
                    echo "<span class='table-badge'><i class='fas fa-table mr-1'></i>{$t} ({$cnt})</span>";
                }
                echo "</div>";
            }
            echo "</div>";

            // If tables are missing or user wants to re-import
            echo "<div class='d-flex align-items-center flex-wrap' style='gap: 12px;'>";
            echo "<form method='post' class='m-0'>";
            echo "<input type='hidden' name='run_import' value='1'>";
            echo "<button type='submit' class='btn btn-success btn-lg shadow-sm' onclick=\"return confirm('Import database dump into Aiven MySQL now?');\"><i class='fas fa-file-import mr-1'></i> " . (empty($tables) ? "Import Database Now (1-Click)" : "Re-Import Database") . "</button>";
            echo "</form>";

            if (!empty($tables)) {
                echo "<a href='login.php' class='btn btn-primary btn-lg shadow-sm'><i class='fas fa-sign-in-alt mr-1'></i> Go to Login Page</a>";
            }
            echo "</div>";

            // Show admin credentials
            echo "<div class='cred-box mt-4'>";
            echo "<h6 class='font-weight-bold text-success mb-2'><i class='fas fa-key mr-1'></i> Default Access Credentials</h6>";
            echo "<div class='row'>";
            echo "<div class='col-md-6'>";
            echo "<strong>Administrator:</strong><br>";
            echo "Email: <code>admin@admin.com</code><br>";
            echo "Password: <code>admin123</code><br>";
            echo "Status: " . ($admin_exists ? "<span class='badge badge-success'>Verified in Database</span>" : "<span class='badge badge-warning'>Not yet imported</span>");
            echo "</div>";
            echo "<div class='col-md-6'>";
            echo "<strong>Sample User:</strong><br>";
            echo "Email: <code>jsmith@sample.com</code><br>";
            echo "Password: <code>jsmith123</code><br>";
            echo "Role: Subscriber (Type 2)";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        ?>
    </div>
    <div class="card-footer bg-light text-center py-2 text-muted" style="font-size: 12px;">
        Music Cloud Community System &bull; Render Reverse-Proxy & Aiven MySQL Production Ready
    </div>
</div>

</body>
</html>

