<?php
// src/index.php - System Landing Page & Navigation Hub
require_once 'auth.php';

authorise(['admin', 'guest', 'student', 'staff', 'unauth']);


session_start();



$isLoggedIn = isset($_SESSION['user_id']);
$firstName  = $isLoggedIn ? ($_SESSION['first_name'] ?? 'User') : '';
?>
<!DOCTYPE html>
<html lang="en-AU">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IoT Central System - Overview</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 2rem;
            background: #f4f6f9;
            color: #2c3e50;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        header {
            background: #ffffff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
            border-left: 6px solid #0056b3;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-title h1 {
            margin: 0 0 0.5rem 0;
            color: #1a252f;
            font-size: 2rem;
        }

        .header-title .lead {
            font-size: 1.1rem;
            color: #555;
            margin: 0;
        }

        .user-greeting {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .welcome-text {
            font-weight: 600;
            color: #1a252f;
        }

        .auth-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: #ffffff;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            margin: 0 0 0.75rem 0;
            font-size: 1.3rem;
            color: #0056b3;
        }

        .card p {
            margin: 0 0 1.25rem 0;
            color: #666;
            font-size: 0.95rem;
            flex-grow: 1;
        }

        .btn {
            display: inline-block;
            background: #0056b3;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 5px;
            font-weight: 600;
            text-align: center;
            transition: background 0.2s ease;
        }

        .btn:hover {
            background: #004085;
        }

        .btn-outline {
            background: transparent;
            color: #0056b3;
            border: 2px solid #0056b3;
        }

        .btn-outline:hover {
            background: #0056b3;
            color: #ffffff;
        }

        .btn-danger {
            background: #8b0000;
            color: #ffffff;
        }

        .btn-danger:hover {
            background: #a00000;
        }

        .btn-secondary {
            background: #6c757d;
            color: #ffffff;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .info-panel {
            background: #ffffff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .info-panel h3 {
            margin-top: 0;
            color: #1a252f;
        }

        .info-panel ul {
            margin: 0;
            padding-left: 1.2rem;
            color: #555;
        }

        .info-panel li {
            margin-bottom: 0.5rem;
        }

        code {
            background: #eef2f7;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            color: #0056b3;
            font-size: 0.9em;
        }
    </style>
</head>

<body>

    <div class="container">
        <header>
            <div class="header-title">
                <h1>IoT Central System Hub</h1>
                <p class="lead">Central management and monitoring platform for student ESP32 microcontroller telemetry.</p>
            </div>

            <!-- Conditional Header Navigation -->
            <?php if ($isLoggedIn): ?>
                <div class="user-greeting">
                    <span class="welcome-text">Welcome, <?= htmlspecialchars($firstName) ?></span>
                    <a href="logout.php" class="btn btn-secondary">Log Out</a>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-outline">Log In</a>
                    <a href="register.php" class="btn">Register</a>
                </div>
            <?php endif; ?>
        </header>

        <div class="grid">
            <!-- Telemetry Data Link Card -->
            <div class="card">
                <div>
                    <h2>Telemetry & Device Controller</h2>
                    <p>View real-time sensor readings, event logs, device activity filters, and update active state values (0 or 1).</p>
                </div>
                <a href="data.php" class="btn">View Telemetry Data &rarr;</a>
            </div>

            <!-- User Account Card (Dynamic State) -->
            <div class="card">
                <div>
                    <h2>Account Access</h2>
                    <?php if ($isLoggedIn): ?>
                        <p>You are logged in as <strong><?= htmlspecialchars($firstName) ?></strong>. Manage your account settings or log out when finished.</p>
                    <?php else: ?>
                        <p>Log in to access administrative privileges or create a new user account to get started with device tracking.</p>
                    <?php endif; ?>
                </div>

                <?php if ($isLoggedIn): ?>
                    <a href="logout.php" class="btn btn-secondary">Log Out</a>
                <?php else: ?>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="login.php" class="btn btn-outline" style="flex: 1;">Log In</a>
                        <a href="register.php" class="btn" style="flex: 1;">Register</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Error Logs Card -->
            <div class="card">
                <div>
                    <h2>System Error Logs</h2>
                    <p>Inspect captured system exceptions, database connection errors, and telemetry transport logs stored in <code>error_log</code>.</p>
                </div>
                <a href="errorlog.php" class="btn btn-danger">View Error Logs &rarr;</a>
            </div>
        </div>

        <!-- Infrastructure Architecture Summary -->
        <div class="info-panel">
            <h3>System Architecture Overview</h3>
            <ul>
                <li><strong>Scarif Development:</strong> Houses edge IoT hardware (ESP32) and the client Web Portal interface.</li>
                <li><strong>Scarif Production Server:</strong> Hosts the MQTT Broker, <code>bridge.py</code> sync daemon, and MySQL Database.</li>
            </ul>
        </div>
    </div>

</body>

</html>