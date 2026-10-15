<?php
require_once 'auth.php';

// Enforce admin-only access
authorise(['admin']);
?>


<?php
// src/errorlog.php - Displays all records from the error_log table
$host = getenv('DB_HOST') ?: '10.0.0.100';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'iot_telemetry';
$user = getenv('DB_USER') ?: 'iot_user';
$pass = getenv('DB_PASSWORD') ?: 'iot_password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Pagination Settings
$itemsPerPage = 15;
$currentPage  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $itemsPerPage;

$flashMessage = '';
$flashType = 'success';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Optional: Action to clear all error logs from error_log
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
        $pdo->exec("TRUNCATE TABLE error_log");
        $flashMessage = "All records in error_log have been cleared successfully.";
        $flashType = "success";
        $currentPage = 1;
        $offset = 0;
    }

    // Get Total Error Count for Pagination from error_log
    $countStmt = $pdo->query("SELECT COUNT(*) FROM `error_log`");
    $totalErrors = (int)$countStmt->fetchColumn();

    // Fetch Paginated Records using explicit schema columns
    $stmt = $pdo->prepare("SELECT `id`, `topic`, `raw_payload`, `error_message`, `logged_at` FROM `error_log` ORDER BY `logged_at` DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $errorLogs = $stmt->fetchAll();

    $totalPages = ceil($totalErrors / $itemsPerPage) ?: 1;
} catch (\PDOException $e) {
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}

// Helper function to keep pagination parameters intact
function buildPageUrl($page)
{
    $params = $_GET;
    $params['page'] = $page;
    return 'errorlog.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en-AU">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Error Logs - IoT Central</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        h1 {
            margin: 0;
            color: #8b0000;
            font-size: 1.8rem;
        }

        .nav-btn {
            display: inline-block;
            background: #0056b3;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.2s ease;
        }

        .nav-btn:hover {
            background: #004085;
        }

        /* Flash Alerts */
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

        /* Card Frame */
        .card {
            background: #ffffff;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #333;
        }

        /* Buttons */
        .btn-clear {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-clear:hover {
            background: #bd2130;
        }

        /* Table Styling */
        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border-radius: 6px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }

        th {
            background: #8b0000;
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        .empty-row {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 24px;
        }

        code.topic-tag {
            background: #eef2f7;
            color: #0056b3;
            padding: 3px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }

        code.payload-tag {
            background: #f8f9fa;
            color: #333;
            padding: 3px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85em;
            display: block;
            max-height: 100px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        .error-text {
            color: #721c24;
            font-weight: 500;
        }

        /* Pagination */
        .pagination {
            display: flex;
            gap: 6px;
            align-items: center;
            justify-content: flex-end;
            margin-top: 1rem;
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

        .pagination a:hover {
            background: #eee;
        }

        .pagination .active {
            background: #8b0000;
            color: white;
            border-color: #8b0000;
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
        <div class="header-container">
            <div>
                <h1>System Error Logs</h1>
                <p style="color: #666; margin: 0.25rem 0 0 0;">Audit trail from table <code>error_log</code></p>
            </div>
            <a href="index.php" class="nav-btn">&larr; Return to System Overview</a>
        </div>

        <?php if (!empty($flashMessage)): ?>
            <div class="alert alert-<?= $flashType ?>">
                <?= $flashMessage ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Recorded Exceptions (Total: <?= $totalErrors ?>)</h2>
                <?php if ($totalErrors > 0): ?>
                    <form method="POST" action="errorlog.php" onsubmit="return confirm('Are you sure you want to permanently clear all error logs?');">
                        <input type="hidden" name="action" value="clear_logs">
                        <button type="submit" class="btn-clear">Clear All Logs</button>
                    </form>
                <?php endif; ?>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 6%;">ID</th>
                        <th style="width: 22%;">MQTT Topic</th>
                        <th style="width: 28%;">Raw Payload</th>
                        <th style="width: 26%;">Error Message</th>
                        <th style="width: 18%;">Logged At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($errorLogs)): ?>
                        <tr>
                            <td colspan="5" class="empty-row">No records found in error_log table.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($errorLogs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['id']) ?></td>
                                <td><code class="topic-tag"><?= htmlspecialchars($log['topic'] ?? 'N/A') ?></code></td>
                                <td>
                                    <?php if (!empty($log['raw_payload'])): ?>
                                        <code class="payload-tag"><?= htmlspecialchars($log['raw_payload']) ?></code>
                                    <?php else: ?>
                                        <span style="color: #999; font-style: italic;">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="error-text"><?= htmlspecialchars($log['error_message'] ?? 'No message specified') ?></td>
                                <td><?= htmlspecialchars($log['logged_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination Bar -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <span class="page-meta">Showing <?= min($offset + 1, $totalErrors) ?>–<?= min($offset + $itemsPerPage, $totalErrors) ?> of <?= $totalErrors ?></span>

                    <?php if ($currentPage > 1): ?>
                        <a href="<?= buildPageUrl($currentPage - 1) ?>">&laquo; Prev</a>
                    <?php else: ?>
                        <span class="disabled">&laquo; Prev</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $currentPage): ?>
                            <span class="active"><?= $i ?></span>
                        <?php elseif ($i == 1 || $i == $totalPages || ($i >= $currentPage - 2 && $i <= $currentPage + 2)): ?>
                            <a href="<?= buildPageUrl($i) ?>"><?= $i ?></a>
                        <?php elseif ($i == 2 || $i == $totalPages - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= buildPageUrl($currentPage + 1) ?>">Next &raquo;</a>
                    <?php else: ?>
                        <span class="disabled">Next &raquo;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>