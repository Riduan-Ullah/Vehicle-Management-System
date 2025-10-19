<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Navigation Bar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#64748b',
                        dark: '#1e293b',
                        light: '#f8fafc'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo/Brand -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="dashboard.php" class="flex items-center text-primary font-bold text-xl">
                        <i class="fas fa-car mr-2"></i>
                        <span>Admin Panel</span>
                    </a>
                </div>

                <!-- Desktop Navigation Links (Center) -->
                <div class="hidden md:flex items-center justify-center space-x-1">
                    <a href="dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:text-primary hover:bg-blue-50 transition duration-200">
                        <i class="fas fa-home mr-1"></i> Dashboard
                    </a>
                    <a href="manage_cars.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:text-primary hover:bg-blue-50 transition duration-200">
                        <i class="fas fa-car-side mr-1"></i> Cars
                    </a>
                    <a href="manage_parts.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:text-primary hover:bg-blue-50 transition duration-200">
                        <i class="fas fa-cog mr-1"></i> Parts
                    </a>
                    <a href="manage_services.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:text-primary hover:bg-blue-50 transition duration-200">
                        <i class="fas fa-tools mr-1"></i> Services
                    </a>
                    <a href="manage_courses.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:text-primary hover:bg-blue-50 transition duration-200">
                        <i class="fas fa-road mr-1"></i> Courses
                    </a>
                    <a href="manage_users.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:text-primary hover:bg-blue-50 transition duration-200">
                        <i class="fas fa-users mr-1"></i> Users
                    </a>
                </div>

                <!-- User Actions (Right) -->
                <div class="hidden md:flex items-center space-x-3">
                    <button class="p-2 rounded-full text-secondary hover:bg-blue-50 hover:text-primary transition duration-200">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="p-2 rounded-full text-secondary hover:bg-blue-50 hover:text-primary transition duration-200">
                        <i class="fas fa-cog"></i>
                    </button>
                    <div class="relative group">
                        <button class="flex items-center text-sm text-secondary hover:text-primary transition duration-200">
                            <div class="h-8 w-8 rounded-full bg-red-600 flex items-center justify-center text-white font-semibold">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <span class="ml-2">Administrator</span>
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                            <div class="py-1">
                                <a href="settings.php" class="block px-4 py-2 text-sm text-secondary hover:bg-blue-50 hover:text-primary">
                                    <i class="fas fa-cog mr-2"></i> Chat
                                </a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-secondary hover:bg-blue-50 hover:text-primary">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-secondary hover:text-primary hover:bg-blue-50 focus:outline-none transition duration-200">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu (Hidden by default) -->
        <div id="mobile-menu" class="md:hidden hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t">
                <a href="dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:text-primary hover:bg-blue-50">
                    <i class="fas fa-home mr-2"></i> Dashboard
                </a>
                <a href="manage_cars.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:text-primary hover:bg-blue-50">
                    <i class="fas fa-car-side mr-2"></i> Cars
                </a>
                <a href="manage_parts.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:text-primary hover:bg-blue-50">
                    <i class="fas fa-cog mr-2"></i> Parts
                </a>
                <a href="manage_services.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:text-primary hover:bg-blue-50">
                    <i class="fas fa-tools mr-2"></i> Services
                </a>
                <a href="manage_courses.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:text-primary hover:bg-blue-50">
                    <i class="fas fa-road mr-2"></i> Courses
                </a>
                <a href="manage_users.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:text-primary hover:bg-blue-50">
                    <i class="fas fa-users mr-2"></i> Users
                </a>
                
                <div class="pt-4 pb-3 border-t border-gray-200">
                    <div class="flex items-center px-3">
                        <div class="h-10 w-10 rounded-full bg-red-600 flex items-center justify-center text-white font-semibold">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="ml-3">
                            <div class="text-base font-medium text-dark">Administrator</div>
                            <div class="text-sm font-medium text-secondary">Admin Panel</div>
                        </div>
                    </div>
                    <div class="mt-3 px-2 space-y-1">
                        <a href="settings.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:text-primary hover:bg-blue-50">
                            <i class="fas fa-cog mr-2"></i> Chat
                        </a>
                        <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:text-primary hover:bg-blue-50">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <script>
        // Toggle mobile menu
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Function to toggle dropdowns in mobile view
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.classList.toggle('hidden');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            
            if (!mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>