<?php
session_start();
require_once 'config/db_connection.php';  // corrected path


$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);       // login via email
    $password = trim($_POST['password']);
    
    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Plain text check (for now, as in your table)
            if ($password === $user['password']) {
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                header("Location: customer/index.php");
                exit();
            } else {
                $login_error = "Invalid password!";
            }
        } else {
            $login_error = "User not found!";
        }
        
        $stmt->close();
    } else {
        $login_error = "Please fill all fields!";
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - VMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary-red: #7f1d1d;
            --primary-red-light: #ef4444;
        }
        
        body {
            background-image: url('https://images.unsplash.com/photo-1519681393784-d120267933ba?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Animated background overlay */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(0, 0, 0, 0.7) 0%, rgba(127, 29, 29, 0.4) 100%);
            z-index: -1;
            animation: gradientShift 15s ease infinite;
        }
        
        /* Floating particles animation */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.3);
            animation: float 15s infinite ease-in-out;
        }
        
        /* Input focus effects */
        .input-field {
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
        }
        
        /* Button animations */
        .btn-login {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: -100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .btn-login:hover::after {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        
        /* Logo animation */
        .logo {
            animation: fadeIn 1s ease-out;
            transition: transform 0.5s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        /* Form container animation */
        .form-container {
            animation: slideUp 0.8s ease-out;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-container:hover {
            transform: translateY(-5px);
        }
        
        /* Keyframe animations */
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0) rotate(0deg); }
            25% { transform: translateY(-20px) translateX(10px) rotate(5deg); }
            50% { transform: translateY(0) translateX(20px) rotate(0deg); }
            75% { transform: translateY(20px) translateX(10px) rotate(-5deg); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .form-container {
                width: 90% !important;
                margin: 0 1rem;
                padding: 1.5rem;
                max-height: 95vh;
            }
            
            .logo {
                width: 36vw;
                height: 36vw;
                max-width: 160px;
                max-height: 160px;
            }
        }
        
        @media (max-height: 700px) {
            .logo {
                width: 120px;
                height: 120px;
            }
            
            .form-container {
                padding-top: 1.5rem;
                padding-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <!-- Animated particles -->
    <div class="particles" id="particles"></div>
    
    <div class="flex flex-col items-center justify-center w-full">
        <div class="form-container bg-black bg-opacity-90 rounded-xl px-8 py-8 flex flex-col items-center w-full max-w-md">
            <img src="https://via.placeholder.com/192x192/7f1d1d/ffffff?text=VMS" alt="VMS Logo" class="logo w-48 h-48 object-contain mb-4">
            
            <?php if (!empty($login_error)): ?>
                <div class="text-red-500 font-bold mb-4 text-center"><?php echo $login_error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="" class="w-full flex flex-col gap-5">
    <div class="flex flex-col gap-2">
        <label for="email" class="text-white font-semibold">Email</label>
        <input type="text" id="email" name="email" required 
               class="input-field bg-gray-800 text-white px-4 py-3 rounded focus:outline-none focus:ring-2 focus:ring-red-600" 
               autocomplete="username" 
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
    </div>
    <div class="flex flex-col gap-2">
        <label for="password" class="text-white font-semibold">Password</label>
        <input type="password" id="password" name="password" required 
               class="input-field bg-gray-800 text-white px-4 py-3 rounded focus:outline-none focus:ring-2 focus:ring-red-600" 
               autocomplete="current-password">
    </div>
    <div class="flex flex-row gap-4 mt-4">
        <button type="submit" class="btn-login bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded w-full transition-colors">Login</button>
        <button type="button" class="btn-login bg-gray-700 hover:bg-gray-800 text-white font-bold py-3 px-8 rounded w-full transition-colors" onclick="window.location.href='signup.php'">Sign Up</button>
    </div>
</form>

    </div>

   

    <script>
        // Create animated particles
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 20;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random properties
                const size = Math.random() * 20 + 5;
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                const delay = Math.random() * 10;
                const duration = 10 + Math.random() * 20;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${duration}s`;
                
                particlesContainer.appendChild(particle);
            }
        });
    </script>
</body>
</html>