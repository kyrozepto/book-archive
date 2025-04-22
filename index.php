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
                $success = "Registration successful! Your API key is: " . $api_key;
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
    <title>Book Archive</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Left side - Book illustration -->
        <div class="hidden lg:flex lg:w-1/2 bg-primary items-center justify-center">
            <div class="text-center text-white p-8">
                <i class="ri-book-2-line text-6xl mb-4"></i>
                <h1 class="text-4xl font-bold mb-4">Book Archive</h1>
                <p class="text-xl">Your personal library in the cloud</p>
            </div>
        </div>

        <!-- Right side - Login/Register form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold mb-2">Welcome Back</h1>
                    <p class="text-gray-600">Sign in to access your book collection</p>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-lg mb-4">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <div class="tabs mb-6">
                    <div class="tab active" onclick="showTab('login')">Login</div>
                    <div class="tab" onclick="showTab('register')">Register</div>
                </div>

                <div id="login-form">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="login">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <div class="relative">
                                <i class="ri-user-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="username" required class="input pl-10 w-full">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <i class="ri-lock-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="password" name="password" required class="input pl-10 w-full">
                            </div>
                        </div>
                        <button type="submit" class="button button-primary w-full">
                            Sign In
                        </button>
                    </form>
                </div>

                <div id="register-form" style="display: none;">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="register">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <div class="relative">
                                <i class="ri-user-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="username" required class="input pl-10 w-full">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <i class="ri-lock-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="password" name="password" required class="input pl-10 w-full">
                            </div>
                        </div>
                        <button type="submit" class="button button-primary w-full">
                            Create Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.tab[onclick="showTab('${tabName}')"]`).classList.add('active');
            
            document.getElementById('login-form').style.display = tabName === 'login' ? 'block' : 'none';
            document.getElementById('register-form').style.display = tabName === 'register' ? 'block' : 'none';
        }
    </script>
</body>
</html> 