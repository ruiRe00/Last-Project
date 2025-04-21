<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // First try to find user by username
    $stmt = $pdo->prepare("SELECT u.*, a.airline_name FROM users u 
                          LEFT JOIN airlines a ON u.id = a.user_id 
                          WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // If not found and input matches an airline name, try to find by airline name
    if (!$user) {
        $stmt = $pdo->prepare("SELECT u.*, a.airline_name FROM users u 
                              JOIN airlines a ON u.id = a.user_id 
                              WHERE a.airline_name = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    }

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // For airlines, store the airline name and get airline ID
        if ($user['role'] === 'airline') {
            // Get airline details
            $stmt = $pdo->prepare("SELECT id, airline_name FROM airlines WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $airline = $stmt->fetch();
            
            if ($airline) {
                $_SESSION['airline_id'] = $airline['id'];
                $_SESSION['airline_name'] = $airline['airline_name'];
            } else {
                $_SESSION['error'] = "Airline account not properly configured. Please contact administrator.";
                header('Location: login.php');
                exit();
            }
        }

        switch($user['role']) {
            case 'admin':
                header('Location: ../admin/dashboard.php');
                break;
            case 'airline':
                header('Location: ../airline/dashboard.php');
                break;
            case 'user':
                header('Location: ../user/dashboard.php');
                break;
        }
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ReFlight</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a73e8;
            --accent-color: #6f42c1;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .login-card {
            background: rgba(18, 18, 18, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            color: white;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            padding: 12px;
            margin-bottom: 20px;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
            box-shadow: none;
        }

        .form-label {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
        }

        .btn-login {
            background: var(--primary-color);
            color: white;
            padding: 12px;
            border-radius: 8px;
            border: none;
            width: 100%;
            font-size: 1rem;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
        }

        a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: var(--accent-color);
        }

        .text-center {
            text-align: center;
        }

        .mt-3 {
            margin-top: 1rem;
        }

        .alert {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="text-center mb-4">Login</h2>
        <?php if ($error): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username / Airline Name</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
            <p class="text-center mt-3" style="color: rgba(255, 255, 255, 0.8);">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
            <p class="text-center">
                <a href="../index.php">Back to Home</a>
            </p>
        </form>
    </div>
</body>
</html>
