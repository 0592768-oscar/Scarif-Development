<?php
// Start output buffering and session management
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Environment & Database Configuration
$host    = getenv('DB_HOST')     ?: 'localhost';
$port    = getenv('DB_PORT')     ?: '3306';
$dbName  = getenv('DB_NAME')     ?: 'telemetry_db';
$user    = getenv('DB_USER')     ?: 'student_user';
$pass    = getenv('DB_PASSWORD') ?: 'Password123!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=$charset";

// Fallback for PHP 8.4+ vs legacy multi-statement constant
$multiStmtAttr = class_exists('\Pdo\Mysql')
    ? \Pdo\Mysql::ATTR_MULTI_STATEMENTS
    : PDO::MYSQL_ATTR_MULTI_STATEMENTS;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true, // Enabled so PDO handles string-concatenated SQL queries correctly
    $multiStmtAttr               => true,
];

try {
    $db = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login | Access Your Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #eee;
        }

        .card-login {
            border-radius: 25px;
        }

        .form-icon {
            color: #aaa;
            margin-right: 10px;
        }

        .vh-100 {
            min-height: 100vh;
        }
    </style>
</head>

<body>

    <section class="vh-100 py-5">
        <div class="container h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-lg-12 col-xl-11">
                    <div class="card text-black card-login shadow-lg">
                        <div class="card-body p-md-5">
                            <div class="row justify-content-center">

                                <div class="col-md-10 col-lg-6 col-xl-7 d-flex align-items-center order-2 order-lg-1">
                                    <img src="https://mdbcdn.b-cdn.net/img/Photos/new-templates/bootstrap-login-form/draw2.webp"
                                        class="img-fluid" alt="Login illustration">
                                </div>

                                <div class="col-md-10 col-lg-6 col-xl-5 order-1 order-lg-2">
                                    <p class="text-center h1 fw-bold mb-5 mx-1 mx-md-4 mt-4">Login</p>

                                    <form class="mx-1 mx-md-4" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

                                        <div class="d-flex flex-row align-items-center mb-4">
                                            <i class="fas fa-envelope fa-lg me-3 fa-fw form-icon"></i>
                                            <div class="form-outline flex-fill mb-0">
                                                <label class="form-label" for="username">Email Address</label>
                                                <input type="text" id="username" name="username" class="form-control form-control-lg" required />
                                            </div>
                                        </div>

                                        <div class="d-flex flex-row align-items-center mb-4">
                                            <i class="fas fa-lock fa-lg me-3 fa-fw form-icon"></i>
                                            <div class="form-outline flex-fill mb-0">
                                                <label class="form-label" for="password">Password</label>
                                                <input type="password" id="password" name="password" class="form-control form-control-lg" required />
                                            </div>
                                        </div>

                                        <div class="text-center text-lg-start mt-4 pt-2">
                                            <button type="submit" name="login" class="btn btn-primary btn-lg px-5 shadow w-100">Login</button>
                                            <p class="small fw-bold mt-3 pt-1 mb-0">Don't have an account?
                                                <a href="register.php" class="link-danger text-decoration-none">Register</a>
                                            </p>
                                        </div>

                                    </form>

                                    <div class="mt-4">
                                        <?php
                                        if (isset($_POST['login'])) {
                                            $v_input_user = trim($_POST['username']);
                                            $v_input_pass = $_POST['password'];

                                            // VULNERABLE QUERY: Concatenated string variable
                                            $query = "SELECT user_id, username, password_hash, first_name, access_level FROM users WHERE username = '$v_input_user'";

                                            // Classroom Debug Log
                                            echo '<div class="alert alert-warning small font-monospace">
                                                <strong>Executed Query:</strong><br>' . htmlspecialchars($query) . '
                                              </div>';

                                            try {
                                                $stmt = $db->query($query);
                                                $user = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;

                                                // Authenticate valid credentials
                                                if ($user && isset($user['password_hash']) && password_verify($v_input_pass, $user['password_hash'])) {
                                                    $_SESSION['user_id']      = $user['user_id'];
                                                    $_SESSION['username']     = $user['username'];
                                                    $_SESSION['first_name']   = $user['first_name'];
                                                    $_SESSION['access_level'] = $user['access_level'];

                                                    echo '<div class="alert alert-success d-flex align-items-center">
                                                        <i class="fas fa-check-circle me-2"></i>
                                                        <div>Welcome back, ' . htmlspecialchars($user['first_name']) . '! You logged in successfully.</div>
                                                      </div>';
                                                } else {
                                                    echo '<div class="alert alert-danger d-flex align-items-center">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        <div>Incorrect Username or password</div>
                                                      </div>';
                                                }
                                            } catch (PDOException $e) {
                                                echo '<div class="alert alert-danger small font-monospace">
                                                    <strong>SQL Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
                                                  </div>';
                                            }
                                        }
                                        ?>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</body>

</html>
<?php
ob_end_flush();
