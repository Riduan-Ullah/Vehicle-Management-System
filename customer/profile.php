<?php
session_start();
require_once '../config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Get form data and sanitize
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    // Validate required fields
    if (empty($full_name)) {
        $error_message = 'Full name is required.';
    } elseif (strlen($full_name) < 2) {
        $error_message = 'Full name must be at least 2 characters long.';
    } else {
        // Handle profile picture upload
        $profile_pic_path = null;
        
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_pic'];
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($file['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
            } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB max
                $error_message = 'File size must be less than 2MB.';
            } else {
                // Generate unique filename
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_dir = '../uploads/profiles/';
                
                // Create upload directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $profile_pic_path = $upload_dir . $filename;
                
                // Move uploaded file
                if (!move_uploaded_file($file['tmp_name'], $profile_pic_path)) {
                    $error_message = 'Failed to upload profile picture.';
                    $profile_pic_path = null;
                }
            }
        }

        if (empty($error_message)) {
            try {
                // Update customer data in database
                if ($profile_pic_path) {
                   $sql = "UPDATE customers SET 
            full_name = ?, 
            phone = ?, 
            address = ?, 
            city = ?, 
            state = ?, 
            zip_code = ?, 
            date_of_birth = ?, 
            gender = ?, 
            profile_image = ?
            WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssi", $full_name, $phone, $address, $city, $state, $zip_code, $date_of_birth, $gender, $profile_pic_path, $user_id);
} else {
    $sql = "UPDATE customers SET 
            full_name = ?, 
            phone = ?, 
            address = ?, 
            city = ?, 
            state = ?, 
            zip_code = ?, 
            date_of_birth = ?, 
            gender = ?
            WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", $full_name, $phone, $address, $city, $state, $zip_code, $date_of_birth, $gender, $user_id);
}
                
                if ($stmt->execute()) {
                    $success_message = 'Profile updated successfully!';
                    // Update session data if needed
                    $_SESSION['full_name'] = $full_name;
                } else {
                    $error_message = 'Failed to update profile. Please try again.';
                }
                
                $stmt->close();
                
            } catch (Exception $e) {
                $error_message = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch customer data from database (always fetch fresh data after update)
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM customers WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

// If customer not found, redirect to login
if (!$customer) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profile - <?php echo htmlspecialchars($customer['full_name'] ?? 'User'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center center/cover no-repeat fixed;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
       
        .profile-container {
            margin-top: 140px;
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 4rem;
            width: 100%;
            flex-wrap: wrap;
        }
        
        .profile-pic-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            width: 350px;
            background: rgba(30, 30, 30, 0.8);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }
        
        .profile-pic {
            width: 280px;
            height: 280px;
            object-fit: cover;
            border-radius: 50%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
            margin-bottom: 24px;
            background: #333;
            border: 4px solid #38bdf8;
        }
        
        .profile-form-box {
            width: 450px;
            background: rgba(30, 30, 30, 0.8);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }
        
        .profile-form label {
            color: #e0e0e0;
            font-weight: 500;
            margin-bottom: 6px;
            display: block;
        }
        
        .profile-form input, .profile-form select {
            background: #333;
            color: #fff;
            border: 2px solid transparent;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .profile-form input:focus, .profile-form select:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.3);
        }
        
        .profile-form .form-row {
            margin-bottom: 1.5rem;
        }
        
        .profile-form .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 12px 28px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-red { 
            background: #ef4444; 
            color: #fff; 
        }
        
        .btn-red:hover { 
            background: #dc2626; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .btn-blue { 
            background: #38bdf8; 
            color: #fff; 
        }
        
        .btn-blue:hover { 
            background: #0ea5e9; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: #e0e0e0;
            border: 2px solid #e0e0e0;
        }
        
        .btn-outline:hover {
            background: #e0e0e0;
            color: #333;
        }
        
        .footer {
            background: rgba(0,0,0,0.95);
            color: #fff;
            text-align: center;
            padding: 18px 0;
            font-size: 18px;
            position: fixed;
            bottom: 0; 
            left: 0; 
            width: 100%;
            z-index: 50;
        }
        
        .footer .team { 
            color: #ef4444; 
            font-weight: bold; 
        }
        
        .form-header {
            color: #fff;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .upload-btn {
            position: relative;
            overflow: hidden;
        }
        
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        .welcome-message {
            color: #fff;
            font-size: 1.2rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #10b981;
            color: white;
        }
        
        .alert-error {
            background: #ef4444;
            color: white;
        }
        
        @media (max-width: 900px) {
            .profile-container {
                gap: 2rem;
                margin-top: 120px;
            }
        }
        
        @media (max-width: 600px) {
            .profile-container {
                padding: 1rem;
            }
            
            .profile-pic-box,
            .profile-form-box {
                width: 100%;
                max-width: 100%;
            }
            
            .profile-form .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Success/Error Messages -->
    <?php if (!empty($success_message)): ?>
        <div class="fixed top-20 left-1/2 transform -translate-x-1/2 alert-success px-6 py-3 rounded-lg shadow-lg z-50">
            <?php echo $success_message; ?>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.fixed.alert-success').remove();
            }, 5000);
        </script>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="fixed top-20 left-1/2 transform -translate-x-1/2 alert-error px-6 py-3 rounded-lg shadow-lg z-50">
            <?php echo $error_message; ?>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.fixed.alert-error').remove();
            }, 5000);
        </script>
    <?php endif; ?>

    <nav class="fixed top-0 left-0 w-full z-50 bg-white shadow">
        <!-- navbar content -->
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        include __DIR__ . '/../navbar.php';
        ?>
    </nav>
    
    <!-- Profile Section -->
    <div class="profile-container">
        <!-- Profile Picture -->
        <div class="profile-pic-box">
            <div class="welcome-message">
                Welcome, <strong><?php echo htmlspecialchars($customer['full_name'] ?? 'User'); ?></strong>!
            </div>
            <img src="<?php echo !empty($customer['profile_image']) ? htmlspecialchars($customer['profile_image']) : 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png'; ?>" 
     alt="Profile Picture" class="profile-pic" id="profilePreview">
            <button type="button" class="btn btn-blue upload-btn w-full mb-3" onclick="document.getElementById('profilePicInput').click();">
                Change Profile Picture
            </button>
            <p class="text-gray-400 text-sm">Allowed formats: JPG, PNG, GIF. Max size: 2MB</p>
            <p id="imageError" class="error-message"></p>
        </div>
        
        <!-- Profile Form -->
        <div class="profile-form-box">
            <h2 class="form-header">Edit Your Profile</h2>
            <form method="POST" action="" enctype="multipart/form-data" class="profile-form" id="profileForm">
                <input type="file" name="profile_pic" accept="image/jpeg, image/png, image/gif" 
                       id="profilePicInput" style="display:none;" onchange="validateImage(this)">
                
                <div class="form-row">
                    <label for="full_name">Full Name:</label>
                    <input type="text" name="full_name" id="full_name" 
                           value="<?php echo htmlspecialchars($customer['full_name'] ?? ''); ?>" required>
                    <p id="fullnameError" class="error-message"></p>
                </div>
                
                <div class="form-row">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" 
                           value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" required readonly>
                    <p class="text-gray-400 text-sm mt-1">Email cannot be changed</p>
                    <p id="emailError" class="error-message"></p>
                </div>
                
                <div class="form-row">
                    <label for="phone">Contact No:</label>
                    <input type="tel" name="phone" id="phone" 
                           value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" 
                           placeholder="Enter your phone number">
                    <p id="phoneError" class="error-message"></p>
                </div>
                
                <div class="form-row">
                    <label for="address">Address:</label>
                    <input type="text" name="address" id="address" 
                           value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" 
                           placeholder="Enter your address">
                </div>
                
                <div class="form-row">
                    <label for="city">City:</label>
                    <input type="text" name="city" id="city" 
                           value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>" 
                           placeholder="Enter your city">
                </div>
                
                <div class="form-row">
                    <label for="state">State:</label>
                    <input type="text" name="state" id="state" 
                           value="<?php echo htmlspecialchars($customer['state'] ?? ''); ?>" 
                           placeholder="Enter your state">
                </div>
                
                <div class="form-row">
                    <label for="zip_code">Zip Code:</label>
                    <input type="text" name="zip_code" id="zip_code" 
                           value="<?php echo htmlspecialchars($customer['zip_code'] ?? ''); ?>" 
                           placeholder="Enter your zip code">
                </div>
                
                <div class="form-row">
                    <label for="date_of_birth">Date of Birth:</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" 
                           value="<?php echo htmlspecialchars($customer['date_of_birth'] ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <label for="gender">Gender:</label>
                    <select name="gender" id="gender">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo ($customer['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($customer['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-red">Update Profile</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        &copy; 2023 All rights reserved by <span class="team">Red</span> Team.
    </footer>

    <script>
    function validateImage(input) {
        const errorElement = document.getElementById('imageError');
        const file = input.files[0];
        
        // Reset error message
        errorElement.style.display = 'none';
        errorElement.textContent = '';
        
        if (!file) return;
        
        // Check file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            errorElement.textContent = 'Please select a valid image format (JPG, PNG, GIF).';
            errorElement.style.display = 'block';
            input.value = '';
            return;
        }
        
        // Check file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            errorElement.textContent = 'Image size must be less than 2MB.';
            errorElement.style.display = 'block';
            input.value = '';
            return;
        }
        
        // If validation passes, show preview
        showPreview(input);
    }
    
    function showPreview(input) {
        const preview = document.getElementById('profilePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) { 
                preview.src = e.target.result; 
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Form validation
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validate full name
        const fullname = document.getElementById('full_name');
        if (fullname.value.trim().length < 2) {
            document.getElementById('fullnameError').textContent = 'Please enter your full name.';
            document.getElementById('fullnameError').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('fullnameError').style.display = 'none';
        }
        
        // Validate phone (if provided)
        const phone = document.getElementById('phone');
        if (phone.value.trim() !== '' && !/^[\+]?[0-9\s\-\(\)]{10,}$/.test(phone.value)) {
            document.getElementById('phoneError').textContent = 'Please enter a valid phone number.';
            document.getElementById('phoneError').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('phoneError').style.display = 'none';
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>