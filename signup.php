<?php
include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    // Hash the password before saving!
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $user_type = $_POST['user_type']; // 'customer' or 'worker'

    // Choose the appropriate table based on user type
    if ($user_type === 'customer') {
        $sql = "INSERT INTO customer (username, fullname, email, password) VALUES (?, ?, ?, ?)";
    } else if ($user_type === 'worker') {
        $sql = "INSERT INTO worker (username, fullname, email, password) VALUES (?, ?, ?, ?)";
    } else {
        echo "<script>alert('Invalid user type.');</script>";
        exit;
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $username, $fullname, $email, $password);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Registration successful!'); window.location.href='login.php';</script>";
        } else {
            $error = mysqli_error($conn);
            echo "<script>alert('Error: Could not register. " . addslashes($error) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Database error: " . addslashes(mysqli_error($conn)) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .form-panel {
            transition: opacity 0.5s ease, transform 0.5s ease;
            position: absolute;
            top: 0;
            height: 100%;
            width: 50%;
        }
        .panel-left {
            left: 0;
        }
        .panel-right {
            right: 0;
        }
        .visible {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(0);
            z-index: 10;
        }
        .invisible-left {
            opacity: 0;
            pointer-events: none;
            transform: translateX(-100%);
            z-index: 0;
        }
        .invisible-right {
            opacity: 0;
            pointer-events: none;
            transform: translateX(100%);
            z-index: 0;
        }
    </style>
</head>
<body class="min-h-screen bg-cover bg-center" style="background-image: url('resources/login_bg6.jpg');">
    <div class="flex items-center justify-center min-h-screen bg-black bg-opacity-60 relative">
        <div class="flex w-full max-w-4xl mx-auto rounded-lg overflow-hidden shadow-lg relative" style="min-height: 500px;">
            <!-- Customer Sign Up Form (Left) -->
            <div id="customerForm" class="form-panel panel-left bg-black p-8 flex flex-col justify-center visible">
                <div class="flex flex-col items-center mb-4">
                    <img src="resources/logo1.png" alt="Logo" class="w-16 h-16 object-contain mb-2 bg-white rounded-full shadow-lg border-2 border-red-600" />
                    <h2 class="text-3xl font-bold text-white text-center">Customer Sign Up</h2>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="user_type" value="customer" />
                    <div class="mb-4">
                        <input type="text" name="username" placeholder="Username" class="w-full px-4 py-2 bg-gray-800 text-white rounded-none focus:outline-none" required />
                    </div>
                    <div class="mb-4">
                        <input type="text" name="fullname" placeholder="Full Name" class="w-full px-4 py-2 bg-gray-800 text-white rounded-none focus:outline-none" required />
                    </div>
                    <div class="mb-4">
                        <input type="email" name="email" placeholder="Email" class="w-full px-4 py-2 bg-gray-800 text-white rounded-none focus:outline-none" required />
                    </div>
                    <div class="mb-6">
                        <input type="password" name="password" placeholder="Password" class="w-full px-4 py-2 bg-gray-800 text-white rounded-none focus:outline-none" required />
                    </div>
                    <button type="submit" class="w-full py-2 bg-red-600 text-white font-bold rounded-none mb-2">Sign Up</button>
                    <button type="button" id="showWorker" class="w-full py-2 bg-gray-700 text-white font-bold rounded-none mb-2">Sign Up as Worker</button>
                    <button type="button" onclick="window.location.href='login.php'" class="w-full py-2 bg-blue-600 text-white font-bold rounded-none mb-2">Log In</button>
                </form>
            </div>
            <!-- Worker Sign Up Form (Right) -->
            <div id="workerForm" class="form-panel panel-right bg-black p-8 flex flex-col justify-center invisible-right">
                <div class="flex flex-col items-center mb-4">
                    <img src="resources/logo1.png" alt="Logo" class="w-16 h-16 object-contain mb-2 bg-white rounded-full shadow-lg border-2 border-red-600" />
                    <h2 class="text-3xl font-bold text-white text-center">Worker Sign Up</h2>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="user_type" value="worker" />
                    <div class="mb-4">
                        <input type="text" name="username" placeholder="Username" class="w-full px-4 py-2 bg-gray-800 text-white rounded-none focus:outline-none" required />
                    </div>
                    <div class="mb-4">
                        <input type="text" name="fullname" placeholder="Full Name" class="w-full px-4 py-2 bg-gray-800 text-white rounded-none focus:outline-none" required />
                    </div>
                    <div class="mb-4">
                        <input type="email" name="email" placeholder="Email" class="w-full px-4 py-2 bg-gray-800 text-white rounded-none focus:outline-none" required />
                    </div>
                    <div class="mb-6">
                        <input type="password" name="password" placeholder="Password" class="w-full px-4 py-2 bg-gray-800 text-white rounded-none focus:outline-none" required />
                    </div>
                    <button type="submit" class="w-full py-2 bg-red-600 text-white font-bold rounded-none mb-2">Sign Up</button>
                    <button type="button" id="showCustomer" class="w-full py-2 bg-gray-700 text-white font-bold rounded-none mb-2">Sign Up as Customer</button>
                    <button type="button" onclick="window.location.href='login.php'" class="w-full py-2 bg-blue-600 text-white font-bold rounded-none mb-2">Log In</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Slide + fade transition between forms
        const customerForm = document.getElementById('customerForm');
        const workerForm = document.getElementById('workerForm');
        document.getElementById('showWorker').onclick = function() {
            customerForm.classList.remove('visible');
            customerForm.classList.add('invisible-left');
            workerForm.classList.remove('invisible-right');
            workerForm.classList.add('visible');
        };
        document.getElementById('showCustomer').onclick = function() {
            workerForm.classList.remove('visible');
            workerForm.classList.add('invisible-right');
            customerForm.classList.remove('invisible-left');
            customerForm.classList.add('visible');
        };
    </script>
</body>
</html>