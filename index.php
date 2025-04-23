<?php
session_start();
require_once __DIR__ . '/includes/Auth.php';

$auth = new Auth();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'register') {
            $api_key = $auth->register($_POST['username'], $_POST['password']);
            if ($api_key) {
                $success = "Registration successful!";
            } else {
                $error = "Registration failed. Username might be taken.";
            }
        } elseif ($_POST['action'] === 'login') {
            $api_key = $auth->login($_POST['username'], $_POST['password']);
            if ($api_key) {
                $_SESSION['api_key'] = $api_key;
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookArchive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
            font-family: "DM Sans", sans-serif;
        }
        .auth-container {
            min-height: 100vh;
            display: flex;
            background: #f8fafc;
        }
        .auth-illustration {
            flex: 1;
            background: url('assets/cosmiccliffs.jpg') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: white;
            text-align: center;
            position: relative;
        }
        .auth-illustration::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        .auth-illustration > div {
            position: relative;
            z-index: 2;
        }
        .auth-form-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .auth-form {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .auth-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .auth-tab {
            flex: 1;
            padding: 0.75rem;
            text-align: center;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        .auth-tab.active {
            background: #e2e8f0;
            color: #1e293b;
        }
        .auth-tab:not(.active) {
            background: #f1f5f9;
            color: #64748b;
        }
        .auth-tab:hover:not(.active) {
            background: #e2e8f0;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #1e293b;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #f8fafc;
            transition: all 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #22c55e;
            background: white;
        }
        .form-input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }
        .input-wrapper {
            position: relative;
        }
        .auth-button {
            width: 100%;
            padding: 0.75rem;
            background: #000000;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .auth-button:hover {
            background: #333333;
        }
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #dc2626;
        }
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #16a34a;
        }
        .alert-icon {
            font-size: 1.25rem;
        }
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
            }
            .auth-illustration {
                padding: 4rem 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-illustration">
            <div>
                <h1 style="font-size: 2.5rem; font-weight: 600;">Book Archive</h1>
                <p style="font-size: 1.1rem; opacity: 0.7;">A way to find your references</p>
            </div>
        </div>
        <div class="auth-form-container">
            <div class="auth-form">
                <div class="auth-tabs">
                    <div class="auth-tab active" onclick="showTab('login')">Login</div>
                    <div class="auth-tab" onclick="showTab('register')">Register</div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="ri-error-warning-line alert-icon"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="ri-check-line alert-icon"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <div id="login-form">
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <div class="input-wrapper">
                                <i class="ri-user-line form-input-icon"></i>
                                <input type="text" name="username" required class="form-input" placeholder="Enter your username">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <div class="input-wrapper">
                                <i class="ri-lock-line form-input-icon"></i>
                                <input type="password" name="password" required class="form-input" placeholder="Enter your password">
                            </div>
                        </div>
                        <button type="submit" class="auth-button">
                            Sign In
                        </button>
                    </form>
                </div>

                <div id="register-form" style="display: none;">
                    <form method="POST">
                        <input type="hidden" name="action" value="register">
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <div class="input-wrapper">
                                <i class="ri-user-line form-input-icon"></i>
                                <input type="text" name="username" required class="form-input" placeholder="Choose a username">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <div class="input-wrapper">
                                <i class="ri-lock-line form-input-icon"></i>
                                <input type="password" name="password" required class="form-input" placeholder="Create a password">
                            </div>
                        </div>
                        <button type="submit" class="auth-button">
                            Create Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.auth-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.auth-tab[onclick="showTab('${tabName}')"]`).classList.add('active');
            
            document.getElementById('login-form').style.display = tabName === 'login' ? 'block' : 'none';
            document.getElementById('register-form').style.display = tabName === 'register' ? 'block' : 'none';
        }
    </script>
</body>
</html> 