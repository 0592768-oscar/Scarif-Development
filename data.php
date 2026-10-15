<?php
// Extrapolate environment configurations assigned via Docker Compose
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'telemetry_db';
$user = getenv('DB_USER') ?: 'student_user';
$pass = getenv('DB_PASSWORD') ?: 'Password123!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
// Check if the modern Pdo\Mysql class exists (PHP 8.4+), otherwise use legacy constant
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,

    // Modern PHP syntax for enabling multi-statements
    \Pdo\Mysql::ATTR_MULTI_STATEMENTS => true,
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


    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_state') {
        $inputDeviceId = isset($_POST['target_device_id']) ? trim($_POST['target_device_id']) : '';
        $inputState    = isset($_POST['state_value']) ? trim($_POST['state_value']) : '';

        if (empty($inputDeviceId)) {
            $flashMessage = 'Device ID cannot be empty.';
            $flashType = 'error';
        } elseif ($inputState !== '0' && $inputState !== '1') {
            $flashMessage = 'Invalid state value. Must be 0 or 1.';
            $flashType = 'error';
        } else {
            $updateStmt = $pdo->prepare("
                INSERT INTO devices (device_id, state_value)
                VALUES (:dev, :state)
                ON DUPLICATE KEY UPDATE state_value = VALUES(state_value)
            ");
            $updateStmt->execute([
                ':dev'   => $inputDeviceId,
                ':state' => (int)$inputState
            ]);
            $flashMessage = "Successfully updated state for <code>" . htmlspecialchars($inputDeviceId) . "</code> to <strong>" . $inputState . "</strong>.";
            $flashType = 'success';
        }
    }

    // 1. Fetch the 10 most recent telemetry records
    if ($selectedDevice !== 'ALL' && !empty($selectedDevice)) {
        $stmt = $pdo->query("SELECT * FROM sensor_readings WHERE device_id = '$selectedDevice' ORDER BY recorded_at DESC LIMIT $itemsPerPage");
    } else {
        $stmt = $pdo->query("SELECT * FROM sensor_readings ORDER BY recorded_at DESC LIMIT $itemsPerPage");
    }
    $readings = $stmt->fetchAll();
    while ($stmt->nextRowset()) {
        // Clears secondary result sets (like the status from INSERT/DELETE)
    }

    // 2. Fetch the 10 most recent event logs using the same device filter
    if ($selectedDevice !== 'ALL' && !empty($selectedDevice)) {
        $eventStmt = $pdo->query("SELECT * FROM event_logs WHERE device_id = '$selectedDevice' ORDER BY logged_at DESC LIMIT $itemsPerPage");
    } else {
        $eventStmt = $pdo->query("SELECT * FROM event_logs ORDER BY logged_at DESC LIMIT $itemsPerPage");
    }
    $logs = $eventStmt->fetchAll();
} catch (\PDOException $e) {
    $errorMsg = $e->getMessage();
    print_r($errorMsg);
}


// print_r($availableDevices);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>IoT Live Telemetry Dashboard</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 2rem;
            background: #f4f4f9;
            color: #333;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        h1,
        h2 {
            color: #333;
            margin: 0 0 0.5rem 0;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .card {
            background: #fff;
            padding: 1.25rem 1.5rem;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            margin-top: 0.75rem;
        }

        .form-row input[type="text"],
        .form-row select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }

        .btn-submit {
            background: #0056b3;
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-submit:hover {
            background: #004085;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .badge-on {
            background: #28a745;
            color: white;
        }

        .badge-off {
            background: #6c757d;
            color: white;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        th,
        td {
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            text-align: left;
        }

        th {
            background: #0056b3;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        code {
            background: #eef2f7;
            padding: 3px 6px;
            border-radius: 4px;
            font-family: monospace;
            color: #0056b3;
        }

        .empty-row {
            text-align: center;
            color: #666;
            font-style: italic;
        }

        .pagination {
            display: flex;
            gap: 6px;
            align-items: center;
            justify-content: flex-end;
            margin-top: 0.75rem;
        }

        .pagination a,
        .pagination span {
            padding: 6px 12px;
            border: 1px solid #ccc;
            background: #fff;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .pagination .active {
            background: #0056b3;
            color: white;
            font-weight: bold;
        }

        .pagination .disabled {
            color: #aaa;
            pointer-events: none;
            background: #f0f0f0;
        }

        .page-meta {
            font-size: 0.85rem;
            color: #666;
            margin-right: auto;
        }
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
                        <th>Recorded At</th>
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
                <!-- AFTER -->
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