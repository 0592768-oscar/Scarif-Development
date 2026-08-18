<?php
// Extrapolate environment configurations assigned via Docker Compose
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'telemetry_db';
$user = getenv('DB_USER') ?: 'student_user';
$pass = getenv('DB_PASSWORD') ?: 'Password123!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$connected = false;
$errorMsg = "";
$readings = [];

$selectedDevice = isset($_GET['device_id']) ? trim($_GET['device_id']) : 'ALL';
$itemsPerPage = 10;


try {
    // Attempt PDO connection configuration
    $pdo = new PDO($dsn, $user, $pass, $options);
    $connected = true;

    // Fetch the 10 most recent telemetry records
    $stmt = $pdo->query("SELECT * FROM sensor_readings WHERE device_id = $selectedDevice ORDER BY recorded_at DESC LIMIT $itemsPerPage");
    $readings = $stmt->fetchAll();

} catch (\PDOException $e) {
    $errorMsg = $e->getMessage();
}

$deviceStatesStmt = $pdo->query("SELECT DISTINCT device_id FROM sensor_readings");
$availableDevices = $deviceStatesStmt->fetchALL(PDO::FETCH_COLUMN);
print_r($availableDevices);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IoT Live Telemetry Dashboard</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f4f6f9; color: #333; margin: 40px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 15px; }
        .status { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .status.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; color: #2c3e50; }
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>
<div class="container">
    <h1>Live Telemetry Dashboard</h1>
    
    <!-- Connectivity Diagnostics Display -->
    <?php if ($connected): ?>
        <div class="status success">
            ✓ Successfully connected to Centralised Database on host: <?= htmlspecialchars($host) ?>
        </div>
    <?php else: ?>
        <div class="status danger">
            ✗ Database Connection Failed!<br>
            <small>Error: <?= htmlspecialchars($errorMsg) ?></small>
        </div>
    <?php endif; ?>

    <h2>Recent Sensor Readings</h2>
    <?php if (empty($readings)): ?>
        <p>No telemetry data found in the database. Ensure the ESP32 is actively publishing data.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Device ID</th>
                    <th>Sensor Value</th>
                    <th>Rec At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($readings as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['device_id']) ?></td>
                        <td><?= htmlspecialchars($row['sensor_value']) ?></td>
                        <td><?= htmlspecialchars($row['recorded_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>