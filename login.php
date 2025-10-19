<?php
session_start();
require_once 'config/db_connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = isset($_POST['role']) ? $_POST['role'] : 'customer'; // Get selected role

    if (empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all fields";
    } else {
        if ($role === 'admin') {
            // Hardcoded admin credentials
            if ($email === 'admin@gmail.com' && $password === 'admin') {
                $_SESSION['admin'] = true;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = 'Admin';
                $success = "Admin login successful! Redirecting...";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'admin/index.php';
                    }, 1500);
                </script>";
            } else {
                $error = "Invalid admin email or password";
            }
        } else {
            // Customer login (as before)
            $sql = "SELECT user_id, email, password, full_name FROM customers WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if ($password === $user['password']) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $success = "Login successful! Redirecting...";
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'customer/index.php';
                        }, 1500);
                    </script>";
                } else {
                    $error = "Invalid email or password";
                }
            } else {
                $error = "Invalid email or password";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vehicle Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
        }
        
        .login-container {
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            border-radius: 20px;
            overflow: hidden;
            width: 95%;
            max-width: 1000px;
            height: 560px;
            display: flex;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-right: 12px;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.2rem;
        }
        
        .floating-label {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            background: white;
            padding: 0 6px;
            color: #6b7280;
            transition: all 0.3s ease;
            pointer-events: none;
            font-size: 14px;
        }
        
        .input-group input:focus + .floating-label,
        .input-group input:not(:placeholder-shown) + .floating-label {
            top: 0;
            font-size: 0.75rem;
            color: #3b82f6;
        }
        
        .social-btn {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .slide-in {
            animation: slideIn 0.8s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .compact-feature {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }
        
        .compact-feature:hover {
            transform: translateX(5px);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .vehicle-bg {
            background-image: url('https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        
        .vehicle-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to right, rgba(30, 60, 114, 0.85), rgba(42, 82, 152, 0.7));
        }
        
        .vehicle-bg > * {
            position: relative;
            z-index: 1;
        }
        
        .form-section {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }
        
        /* Enhanced Toast Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 380px;
        }
        
        .toast {
            padding: 14px 18px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            max-width: 380px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .toast.hiding {
            transform: translateX(400px);
            opacity: 0;
        }
        
        .toast.success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-left: 4px solid #047857;
        }
        
        .toast.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-left: 4px solid #b91c1c;
        }
        
        .toast-icon {
            font-size: 20px;
            flex-shrink: 0;
            width: 24px;
            text-align: center;
        }
        
        .toast-content {
            flex: 1;
            min-width: 0;
        }
        
        .toast-title {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 2px;
            line-height: 1.2;
        }
        
        .toast-message {
            font-size: 13px;
            opacity: 0.95;
            line-height: 1.3;
            word-wrap: break-word;
        }
        
        .toast-close {
            background: none;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            opacity: 0.7;
            transition: all 0.3s;
            padding: 4px;
            border-radius: 4px;
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toast-close:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.1);
        }
        
        @media (max-height: 700px) {
            .login-container {
                height: 520px;
            }
            
            .form-section {
                padding: 20px;
            }
        }
        
        @media (max-height: 600px) {
            .login-container {
                height: 480px;
            }
            
            .form-section {
                padding: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                height: auto;
                max-height: 95vh;
            }
            
            .vehicle-bg {
                min-height: 200px;
            }
            
            .form-section {
                padding: 20px;
            }
            
            .toast-container {
                top: 80px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
            
            .toast {
                max-width: none;
                padding: 12px 16px;
            }
        }
        
        /* Custom Radio Buttons */
        .custom-radio {
            display: flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
            position: relative;
        }

        .custom-radio input[type="radio"] {
            display: none;
        }

        .radio-square {
            width: 22px;
            height: 22px;
            border-radius: 4px;
            border: 2px solid #b91c1c; /* deep red */
            background: #b91c1c;
            display: inline-block;
            position: relative;
            transition: background 0.2s, border-color 0.2s;
            box-sizing: border-box;
        }

        .custom-radio input[type="radio"]:checked + .radio-square {
            background: #ef4444; /* bright red */
            border-color: #ef4444;
        }

        .radio-square::after {
            content: '';
            display: block;
            width: 12px;
            height: 12px;
            margin: 3px auto;
            border-radius: 2px;
            background: white;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .custom-radio input[type="radio"]:checked + .radio-square::after {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

    <div class="login-container bg-white slide-in">
        <!-- Left Side - Brand & Features with Vehicle Background -->
        <div class="w-3/5 vehicle-bg text-white p-12 flex flex-col">
            <div class="mb-4">
                <div class="flex items-center mb-2">
                    <i class="fas fa-car-side text-2xl mr-2"></i>
                    <h1 class="text-xl font-bold">Vehicle Management System</h1>
                </div>
                <p class="text-sm mt-1 opacity-90">Car Rentals • Parts Store • Service Center</p>
            </div>
            
            <div class="flex-grow">
                <h2 class="text-lg font-semibold mb-4">All-in-One Vehicle Solution</h2>
                
                <div class="space-y-2">
                    <div class="compact-feature flex items-center">
                        <div class="feature-icon bg-blue-500">
                            <i class="fas fa-key text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium">Car Rentals</h3>
                            <p class="text-xs opacity-80">Premium vehicles for every need</p>
                        </div>
                    </div>
                    
                    <div class="compact-feature flex items-center">
                        <div class="feature-icon bg-green-500">
                            <i class="fas fa-cogs text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium">Parts Store</h3>
                            <p class="text-xs opacity-80">Genuine parts & accessories</p>
                        </div>
                    </div>
                    
                    <div class="compact-feature flex items-center">
                        <div class="feature-icon bg-purple-500">
                            <i class="fas fa-tools text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium">Service Center</h3>
                            <p class="text-xs opacity-80">Expert maintenance & repairs</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <div class="flex justify-center items-center text-xs">
                    <i class="fas fa-star text-yellow-300 mr-1"></i>
                    <i class="fas fa-star text-yellow-300 mr-1"></i>
                    <i class="fas fa-star text-yellow-300 mr-1"></i>
                    <i class="fas fa-star text-yellow-300 mr-1"></i>
                    <i class="fas fa-star text-yellow-300 mr-2"></i>
                    <span>4.8/5 from 1,200+ reviews</span>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="w-3/5 form-section">
            <div class="w-full max-w-sm">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Welcome Back</h2>
                    <p class="text-gray-600 text-sm mt-1">Sign in to your account</p>
                </div>
                
                <!-- PHP Error/Success Messages -->
                <?php if ($error): ?>
                    <div class="mb-4">
                        <div class="border-l-4 border-red-500 p-3 rounded text-sm bg-red-50 text-red-700">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-400"></i>
                                </div>
                                <div class="ml-2">
                                    <p><?php echo $error; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="mb-4">
                        <div class="border-l-4 border-green-500 p-3 rounded text-sm bg-green-50 text-green-700">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-400"></i>
                                </div>
                                <div class="ml-2">
                                    <p><?php echo $success; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form id="login-form" method="POST" action="" class="space-y-4">
                    <!-- Radio Button Group for Role -->
                    <div class="flex items-center justify-center mb-2 gap-4">
                        <label class="custom-radio">
                            <input type="radio" name="role" value="customer"
                                <?php echo (!isset($_POST['role']) || $_POST['role'] === 'customer') ? 'checked' : ''; ?>>
                            <span class="radio-square"></span>
                            <span class="ml-2 text-sm font-medium text-gray-700">Customer</span>
                        </label>
                        <label class="custom-radio">
                            <input type="radio" name="role" value="admin"
                                <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'checked' : ''; ?>>
                            <span class="radio-square"></span>
                            <span class="ml-2 text-sm font-medium text-gray-700">Admin</span>
                        </label>
                    </div>
                    
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder=" " 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <label for="email" class="floating-label">Email Address</label>
                        <div class="absolute right-3 top-2.5 text-gray-400">
                            <i class="fas fa-envelope text-sm"></i>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder=" " 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                        <label for="password" class="floating-label">Password</label>
                        <div class="absolute right-3 top-2.5 text-gray-400">
                            <i class="fas fa-lock text-sm"></i>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox" 
                                   class="h-3.5 w-3.5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember-me" class="ml-2 text-gray-700">Remember me</label>
                        </div>
                        
                        <a href="#" class="text-blue-600 hover:text-blue-500">Forgot password?</a>
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition duration-300 pulse text-sm">
                            Sign In
                        </button>
                    </div>
                </form>
                
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-xs">
                            <span class="px-2 bg-white text-gray-500">Or continue with</span>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex space-x-3">
                        <a href="#" class="social-btn w-1/2 inline-flex justify-center items-center py-2 px-3 rounded-md bg-white text-sm font-medium text-gray-700">
                            <i class="fab fa-google text-red-500 text-sm mr-2"></i>
                            <span>Google</span>
                        </a>
                        
                        <a href="#" class="social-btn w-1/2 inline-flex justify-center items-center py-2 px-3 rounded-md bg-white text-sm font-medium text-gray-700">
                            <i class="fab fa-facebook-f text-blue-600 text-sm mr-2"></i>
                            <span>Facebook</span>
                        </a>
                    </div>
                </div>
                
                <div class="mt-6 text-center text-sm">
                    <p class="text-gray-600">
                        Don't have an account?
                        <a href="signup.php" class="font-medium text-blue-600 hover:text-blue-500">Sign up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effects to inputs
            const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('border-blue-500');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('border-blue-500');
                });
            });
            
            // Add loading state to submit button
            const form = document.getElementById('login-form');
            const submitButton = form.querySelector('button[type="submit"]');
            
            form.addEventListener('submit', function(e) {
                // Get form values
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                
                // Simple client-side validation
                if (!email || !password) {
                    e.preventDefault();
                    showToast('Please fill in all fields', 'error');
                    return;
                }
                
                // Show loading state
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Signing In...';
                submitButton.disabled = true;
                submitButton.classList.remove('pulse');
            });
            
            // Enhanced toast notification system
            function showToast(message, type = 'success') {
                const toastContainer = document.getElementById('toast-container');
                const toastId = 'toast-' + Date.now();
                
                const toast = document.createElement('div');
                toast.id = toastId;
                toast.className = `toast ${type}`;
                
                const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
                const title = type === 'success' ? 'Success!' : 'Error!';
                
                toast.innerHTML = `
                    <div class="toast-icon">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button class="toast-close" onclick="removeToast('${toastId}')">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                toastContainer.appendChild(toast);
                
                // Trigger animation
                setTimeout(() => {
                    toast.classList.add('show');
                }, 10);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    removeToast(toastId);
                }, 5000);
                
                return toastId;
            }
            
            function removeToast(toastId) {
                const toast = document.getElementById(toastId);
                if (toast) {
                    toast.classList.remove('show');
                    toast.classList.add('hiding');
                    
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 500);
                }
            }
            
            // Auto-focus on email field
            document.getElementById('email').focus();
            
            // Show PHP messages as toasts if they exist
            <?php if ($error): ?>
                setTimeout(() => {
                    showToast('<?php echo addslashes($error); ?>', 'error');
                }, 500);
            <?php endif; ?>
            
            <?php if ($success): ?>
                setTimeout(() => {
                    showToast('<?php echo addslashes($success); ?>', 'success');
                }, 500);
            <?php endif; ?>
        });
        
        // Make removeToast function globally available
        window.removeToast = function(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.remove('show');
                toast.classList.add('hiding');
                
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 500);
            }
        };
    </script>
</body>
</html>