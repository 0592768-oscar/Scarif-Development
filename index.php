<?php
require_once 'auth.php';

// Pass an array of all roles allowed to view this page
authorise(['admin', 'staff']);
?>

// Page code continues below...

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
$logs = [];

$selectedDevice = isset($_GET['device_id']) ? trim($_GET['device_id']) : 'ALL';
$itemsPerPage = 10;


try {
    // Attempt PDO connection configuration
    $pdo = new PDO($dsn, $user, $pass, $options);
    $connected = true;

    // 1. Fetch the 10 most recent telemetry records
    if ($selectedDevice !== 'ALL' && !empty($selectedDevice)) {
    	$stmt = $pdo->query("SELECT * FROM sensor_readings WHERE device_id = $selectedDevice ORDER BY recorded_at DESC LIMIT $itemsPerPage");
    } else {
    	$stmt = $pdo->query("SELECT * FROM sensor_readings ORDER BY recorded_at DESC LIMIT $itemsPerPage");
    }
    $readings = $stmt->fetchAll();

    // 2. Fetch the 10 most recent event logs using the same device filter
    if ($selectedDevice !== 'ALL' && !empty($selectedDevice)) {
    	$eventStmt = $pdo->query("SELECT * FROM event_logs WHERE device_id = '$selectedDevice' ORDER BY logged_at DESC LIMIT $itemsPerPage");
    } else {
    	$eventStmt = $pdo->query("SELECT * FROM event_logs ORDER BY logged_at DESC LIMIT $itemsPerPage");
    }
    $logs = $eventStmt->fetchAll();
} catch (\PDOException $e) {
    $errorMsg = $e->getMessage();
}

$deviceStatesStmt = $pdo->query("
    SELECT DISTINCT device_id FROM (
        SELECT device_id FROM sensor_readings
        UNION
        SELECT device_id FROM event_logs
        UNION
        SELECT device_id FROM devices
    ) AS combined_devices ORDER BY device_id ASC
");
$availableDevices = $deviceStatesStmt->fetchAll(PDO::FETCH_COLUMN);
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
    <!-- Recent Event Logs Table -->
    <h2>Recent Event Logs</h2>
    <?php if (empty($logs)): ?>
        <p>No event logs found for the selected criteria.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">ID</th>
                    <th style="width: 25%;">Device ID</th>
                    <th style="width: 40%;">Event Message</th>
                    <th style="width: 25%;">Logged At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['id']) ?></td>
                        <td><code><?= htmlspecialchars($log['device_id']) ?></code></td>
                        <td><?= htmlspecialchars($log['event_message']) ?></td>
                        <td><?= htmlspecialchars($log['logged_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>

<!-- Device State Control Form -->
<div class="card">
	<h2>Device State Controller</h2>
	<form method="POST" action="index.php">
		<input type="hidden" name="action" value="update_state">
		<div class="form-row">
			<div>
				<label for="target_device_id" style="font-weight: bold; display: block;">Device ID:</label>
				<input type="text" name="target_device_id" id="target_device_id" placeholder="e.g. ESP32-01" required list="device-list">
				<datalist id="device-list">
					<?php foreach ($availableDevices as $dev): ?>
						<option value="<?= htmlspecialchars($dev) ?>">
					<?php endforeach; ?>
				</datalist>
			</div>
			<div>
				<label for="state_value" style="font-weight: bold; display: block;">State Value:</label>
				<select name="state_value" id="state_value">
					<option value="1">1 (ON / Active)</option>
					<option value="0">0 (OFF / Inactive)</option>
				</select>
			</div>
			<div>
				<button type="submit" class="btn-submit">Update State</button>
			</div>
		</div>
	</form>
</div>

 <!-- Filter Control -->
    <div class="card filter-card">
        <label for="deviceFilter">Filter Telemetry by Device:</label>
        <form method="GET" action="index.php" id="filterForm">
            <select name="device_id" id="deviceFilter" onchange="document.getElementById('filterForm').submit();">
                <option value="ALL" <?= $selectedDevice === 'ALL' ? 'selected' : '' ?>>-- All Devices --</option>
                <?php foreach ($availableDevices as $dev): ?>
                    <option value="<?= htmlspecialchars($dev) ?>" <?= $selectedDevice === $dev ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dev) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($selectedDevice !== 'ALL'): ?>
            <a href="index.php" class="reset-link">&times; Clear Filter</a>
        <?php endif; ?>
    </div>