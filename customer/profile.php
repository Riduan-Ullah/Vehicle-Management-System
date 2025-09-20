<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profile</title>
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
        
        .profile-form input {
            background: #333;
            color: #fff;
            border: 2px solid transparent;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .profile-form input:focus {
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
            <img src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png" 
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
            <form method="POST" enctype="multipart/form-data" class="profile-form" id="profileForm">
                <input type="file" name="profile_pic" accept="image/jpeg, image/png, image/gif" 
                       id="profilePicInput" style="display:none;" onchange="validateImage(this)">
                
                <div class="form-row">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" value="demo_user" required>
                    <p id="usernameError" class="error-message"></p>
                </div>
                
                <div class="form-row">
                    <label for="fullname">Full Name:</label>
                    <input type="text" name="fullname" id="fullname" value="John Doe" required>
                    <p id="fullnameError" class="error-message"></p>
                </div>
                
                <div class="form-row">
                    <label for="address">Address:</label>
                    <input type="text" name="address" id="address" value="123 Street">
                </div>
                
                <div class="form-row">
                    <label for="contact">Contact No:</label>
                    <input type="tel" name="contact" id="contact" value="+880123456789" pattern="[+]{1}[0-9]{11,14}">
                    <p id="contactError" class="error-message"></p>
                </div>
                
                <div class="form-row">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" value="demo@example.com" required>
                    <p id="emailError" class="error-message"></p>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline">Cancel</button>
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
        
        // Validate username
        const username = document.getElementById('username');
        if (username.value.trim().length < 3) {
            document.getElementById('usernameError').textContent = 'Username must be at least 3 characters long.';
            document.getElementById('usernameError').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('usernameError').style.display = 'none';
        }
        
        // Validate fullname
        const fullname = document.getElementById('fullname');
        if (fullname.value.trim().length < 2) {
            document.getElementById('fullnameError').textContent = 'Please enter your full name.';
            document.getElementById('fullnameError').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('fullnameError').style.display = 'none';
        }
        
        // Validate email
        const email = document.getElementById('email');
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email.value)) {
            document.getElementById('emailError').textContent = 'Please enter a valid email address.';
            document.getElementById('emailError').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('emailError').style.display = 'none';
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>