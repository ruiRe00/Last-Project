<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReFlight - Your trusted platform for booking flights online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3A59D1;
            --secondary-color: #28a745;
            --accent-color: #6f42c1;
            --danger-color: #dc3545;
            --dark-color: #343a40;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
            margin-left: 2rem;
        }

        .navbar-brand:hover {
            color: var(--accent-color);
        }

        .nav-link {
            color: var(--dark-color);
            font-weight: 500;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
            text-align: center;
            color: white;
        }

        .main-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .main-content p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .btn-action {
            background: white;
            color: var(--primary-color);
            padding: 12px 30px;
            font-size: 1.1rem;
            border-radius: 30px;
            margin: 0 10px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            background: var(--accent-color);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-plane-departure me-2"></i>
                ReFlight
            </a>
        </div>
    </nav>

    <div class="main-content">
        <h1>Welcome to ReFlight</h1>
        <p>Your trusted platform for booking flights online</p>
        <div class="buttons">
            <a href="auth/login.php" class="btn-action">Login</a>
            <a href="auth/register.php" class="btn-action">Register</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
