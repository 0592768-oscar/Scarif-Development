<?php
require_once 'auth.php';

// Enforce admin-only access
// authorise(['admin']);
?>

// Page code continues below...

<?php
// src/auth.php - Session Management & Role-Based Access Control (RBAC)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Enforces access control based on user authentication status and allowed roles.
 *
 * @param array $allowedRoles List of roles permitted to view the page (e.g., ['admin', 'staff'])
 * @return void
 */
function authorise(array $allowedRoles = []): void 
{
    // // 1. Check if user is logged in
    // if (!isset($_SESSION['user_id'])) {
    //     header('Location: login.php?error=unauthenticated');
    //     exit();
    // }

    // 2. Fetch current user role from session (default to 'guest' if not set)
    $userRole = $_SESSION['access_level'] ?? 'unauth';

    // 3. If allowedRoles is specified, verify user has access
    if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles, true)) {
        renderUnauthorisedPage($userRole);
        exit();
    }
}

/**
 * Helper function to output an HTTP 403 response and display an unauthorised error page.
 *
 * @param string $userRole The role of the currently logged-in user
 * @return void
 */
function renderUnauthorisedPage(string $userRole): void 
{
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en-AU">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Access Forbidden</title>
        <style>
            body {
                font-family: system-ui, -apple-system, sans-serif;
                background: #f4f6f9;
                color: #2c3e50;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
            }
            .error-card {
                background: #ffffff;
                padding: 2.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                max-width: 500px;
                width: 90%;
                text-align: center;
                border-top: 6px solid #dc3545;
            }
            h1 { color: #dc3545; margin-top: 0; font-size: 2rem; }
            p { font-size: 1rem; color: #555; line-height: 1.5; }
            .role-badge {
                display: inline-block;
                background: #eef2f7;
                color: #0056b3;
                padding: 4px 10px;
                border-radius: 4px;
                font-family: monospace;
                font-weight: bold;
            }
            .btn {
                display: inline-block;
                margin-top: 1.5rem;
                background: #0056b3;
                color: #fff;
                text-decoration: none;
                padding: 10px 20px;
                border-radius: 5px;
                font-weight: 600;
            }
            .btn:hover { background: #004085; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <h1>403 - Access Forbidden</h1>
            <p>You do not have permission to access this page.</p>
            <p>Your current role is <span class="role-badge"><?= htmlspecialchars($userRole) ?></span>.</p>
            <a href="index.php" class="btn">&larr; Return to Central Hub</a>
        </div>
    </body>
    </html>
    <?php
}
