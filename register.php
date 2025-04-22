<?php
session_start();
require_once __DIR__ . '/includes/Auth.php';

$auth = new Auth();

if (isset($_SESSION['api_key'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
        try {
            $result = $auth->register($username, $email, $password);
            if ($result) {
                $success = 'Registration successful! Please login.';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Book Archive</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-lg">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900">Create Account</h1>
                <p class="mt-2 text-gray-600">Join Book Archive to start building your collection</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="ri-error-warning-line"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="ri-check-line"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label for="username" class="form-label">Username</label>
                        <div class="relative">
                            <i class="ri-user-line absolute left-2.5 top-2.5 text-gray-500"></i>
                            <input type="text" id="username" name="username" required class="input pl-9" placeholder="Enter your username">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="form-label">Email</label>
                        <div class="relative">
                            <i class="ri-mail-line absolute left-2.5 top-2.5 text-gray-500"></i>
                            <input type="email" id="email" name="email" required class="input pl-9" placeholder="Enter your email">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="form-label">Password</label>
                        <div class="relative">
                            <i class="ri-lock-line absolute left-2.5 top-2.5 text-gray-500"></i>
                            <input type="password" id="password" name="password" required class="input pl-9" placeholder="Enter your password">
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="relative">
                            <i class="ri-lock-line absolute left-2.5 top-2.5 text-gray-500"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required class="input pl-9" placeholder="Confirm your password">
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="button button-primary w-full">
                        <i class="ri-user-add-line"></i>
                        Create Account
                    </button>
                </div>
            </form>

            <div class="text-center">
                <p class="text-gray-600">
                    Already have an account?
                    <a href="index.php" class="text-primary hover:text-primary-dark">Login here</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html> 