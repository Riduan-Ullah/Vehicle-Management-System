<?php
// Start session if not already started and only if headers haven't been sent
if (function_exists('session_status')) {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_start();
        }
    }
} else {
    // Fallback for very old PHP versions without session_status()
    if (session_id() === '') {
        if (!headers_sent()) {
            session_start();
        }
    }
}

// Get customer data from session
$customer_name = $_SESSION['user_name'] ?? 'Guest';
$customer_email = $_SESSION['user_email'] ?? '';
$customer_initials = '';

// Generate initials from customer name
if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
    $name_parts = explode(' ', $_SESSION['user_name']);
    if (count($name_parts) >= 2) {
        $customer_initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
    } else {
        $customer_initials = strtoupper(substr($_SESSION['user_name'], 0, 2));
    }
} else {
    $customer_initials = 'GU';
}

// Get profile image if available
$profile_image = $_SESSION['profile_image'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weeldoc - Automotive Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            light: '#3b82f6',
                            DEFAULT: '#2563eb',
                            dark: '#1d4ed8'
                        },
                        secondary: {
                            light: '#94a3b8',
                            DEFAULT: '#64748b',
                            dark: '#475569'
                        },
                        accent: {
                            light: '#f97316',
                            DEFAULT: '#ea580c',
                            dark: '#c2410c'
                        },
                        dark: '#1e293b',
                        light: '#f8fafc'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        'nav': '0 4px 20px -2px rgba(0, 0, 0, 0.1)',
                        'dropdown': '0 10px 25px -5px rgba(0, 0, 0, 0.1)',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .logo-icon {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .logo-icon::before {
            content: "";
            position: absolute;
            width: 24px;
            height: 24px;
            border: 2px solid #ea580c;
            border-radius: 50%;
        }
        .logo-icon::after {
            content: "";
            position: absolute;
            width: 8px;
            height: 8px;
            background: #ea580c;
            border-radius: 50%;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Navigation Bar -->
    <nav class="bg-gradient-to-r from-slate-800 to-slate-900 shadow-nav fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo/Brand -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="#" class="flex items-center text-white font-bold text-xl transition-all duration-300 hover:scale-105">
                        <div class="logo-icon mr-2">
                            <i class="fas fa-circle text-transparent relative z-10"></i>
                        </div>
                        <span class="text-white">WheelsDoc</span>
                    </a>
                </div>

                <!-- Desktop Navigation Links (Center) -->
                <div class="hidden md:flex items-center justify-center space-x-1">
                    <a href="index.php" class="group px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-300 flex items-center relative">
                        <i class="fas fa-home mr-2 transition-transform duration-300 group-hover:scale-110"></i> 
                        Home
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-accent group-hover:w-full transition-all duration-300"></span>
                    </a>
                    
                    <!-- Rentals Button (No Dropdown) -->
                    <a href="available_car.php" class="group px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-300 flex items-center relative">
                        <i class="fas fa-car-side mr-2 transition-transform duration-300 group-hover:scale-110"></i> 
                        Rentals
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-accent group-hover:w-full transition-all duration-300"></span>
                    </a>
                    
                    <!-- Services Dropdown -->
                    <div class="relative group">
                        <button class="group px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-300 flex items-center relative">
                            <i class="fas fa-tools mr-2 transition-transform duration-300 group-hover:scale-110"></i> 
                            Services
                            <i class="fas fa-chevron-down ml-1 text-xs transition-transform duration-300 group-hover:rotate-180"></i>
                            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-accent group-hover:w-full transition-all duration-300"></span>
                        </button>
                        <div class="absolute left-0 mt-2 w-56 rounded-lg shadow-dropdown bg-slate-800 ring-1 ring-slate-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform -translate-y-2 group-hover:translate-y-0">
                            <div class="py-2">
                                <a href="maintenance.php" class="flex items-center px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                                    <i class="fas fa-calendar-check mr-3 text-accent"></i> 
                                    Maintenance
                                </a>
                                <a href="car_wash.php" class="flex items-center px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                                    <i class="fas fa-hand-sparkles mr-3 text-accent"></i> 
                                    Car Wash
                                </a>
                                <a href="customization.php" class="flex items-center px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                                    <i class="fas fa-palette mr-3 text-accent"></i> 
                                    Customization
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <a href="parts.php" class="group px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-300 flex items-center relative">
                        <i class="fas fa-cog mr-2 transition-transform duration-300 group-hover:scale-110"></i> 
                        Parts Store
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-accent group-hover:w-full transition-all duration-300"></span>
                    </a>
                    
                    <a href="courses.php" class="group px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-300 flex items-center relative">
                        <i class="fas fa-road mr-2 transition-transform duration-300 group-hover:scale-110"></i> 
                        Courses
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-accent group-hover:w-full transition-all duration-300"></span>
                    </a>
                </div>

                <!-- User Actions (Right) -->
                <div class="hidden md:flex items-center space-x-2">
                    <!-- Wishlist Button -->
                    <a href="wishlist.php" class="group relative p-2 rounded-full text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-300">
                        <i class="fas fa-heart transition-transform duration-300 group-hover:scale-110"></i>
                    </a>
                    
                    <!-- Cart Button -->
                    <a href="cart.php" class="group relative p-2 rounded-full text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-300">
                        <i class="fas fa-shopping-cart transition-transform duration-300 group-hover:scale-110"></i>
                    </a>
                    
                    <!-- User Profile Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center text-sm text-gray-300 hover:text-white transition-all duration-300">
                            <?php if (!empty($profile_image)): ?>
                                <img src="<?php echo htmlspecialchars($profile_image); ?>" 
                                     alt="Profile" 
                                     class="h-8 w-8 rounded-full object-cover border-2 border-primary">
                            <?php else: ?>
                                <div class="h-8 w-8 rounded-full bg-gradient-to-r from-accent to-accent-dark flex items-center justify-center text-white font-semibold shadow-md">
                                    <?php echo $customer_initials; ?>
                                </div>
                            <?php endif; ?>
                            <span class="ml-2"><?php echo htmlspecialchars($customer_name); ?></span>
                            <i class="fas fa-chevron-down ml-1 text-xs transition-transform duration-300 group-hover:rotate-180"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-56 rounded-lg shadow-dropdown bg-slate-800 ring-1 ring-slate-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform -translate-y-2 group-hover:translate-y-0">
                            <div class="py-2">
                                <div class="px-4 py-2 border-b border-slate-700">
                                    <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($customer_name); ?></div>
                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($customer_email); ?></div>
                                </div>
                                <a href="profile.php" class="flex items-center px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                                    <i class="fas fa-user mr-3 text-accent"></i> 
                                    Profile
                                </a>
                                
                                <a href="logout.php" class="flex items-center px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                                    <i class="fas fa-sign-out-alt mr-3 text-accent"></i> 
                                    Sign out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-300 hover:text-white hover:bg-slate-700 focus:outline-none transition-all duration-300">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu (Hidden by default) -->
        <div id="mobile-menu" class="md:hidden hidden bg-slate-800 shadow-lg">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 border-t border-slate-700">
                <a href="index.php" class="flex items-center px-3 py-3 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                    <i class="fas fa-home mr-3 text-accent"></i> 
                    Home
                </a>
                
                <!-- Rentals Button (No Dropdown) -->
                <a href="available_car.php" class="flex items-center px-3 py-3 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                    <i class="fas fa-car-side mr-3 text-accent"></i> 
                    Rentals
                </a>
                
                <!-- Mobile Services Dropdown -->
                <div class="relative">
                    <button onclick="toggleDropdown('services-dropdown')" class="flex justify-between items-center w-full px-3 py-3 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent">
                        <span class="flex items-center">
                            <i class="fas fa-tools mr-3 text-accent"></i> 
                            Services
                        </span>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-300" id="services-chevron"></i>
                    </button>
                    <div id="services-dropdown" class="hidden pl-4 mt-1">
                        <a href="maintenance.php" class="flex items-center px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                            <i class="fas fa-calendar-check mr-3 text-accent"></i> 
                            Maintenance
                        </a>
                        <a href="car_wash.php" class="flex items-center px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                            <i class="fas fa-hand-sparkles mr-3 text-accent"></i> 
                            Car Wash
                        </a>
                        <a href="customization.php" class="flex items-center px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                            <i class="fas fa-palette mr-3 text-accent"></i> 
                            Customization
                        </a>
                    </div>
                </div>
                
                <a href="parts.php" class="flex items-center px-3 py-3 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                    <i class="fas fa-cog mr-3 text-accent"></i> 
                    Parts Store
                </a>
                
                <a href="courses.php" class="flex items-center px-3 py-3 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                    <i class="fas fa-road mr-3 text-accent"></i> 
                    Courses
                </a>
                
                <a href="wishlist.php" class="flex items-center px-3 py-3 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                    <i class="fas fa-heart mr-3 text-accent"></i> 
                    Wishlist
                </a>
                
                <a href="cart.php" class="flex items-center px-3 py-3 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                    <i class="fas fa-shopping-cart mr-3 text-accent"></i> 
                    Cart
                </a>
                
                <div class="pt-4 pb-3 border-t border-slate-700">
                    <div class="flex items-center px-3">
                        <?php if (!empty($profile_image)): ?>
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" 
                                 alt="Profile" 
                                 class="h-10 w-10 rounded-full object-cover border-2 border-primary">
                        <?php else: ?>
                            <div class="h-10 w-10 rounded-full bg-gradient-to-r from-accent to-accent-dark flex items-center justify-center text-white font-semibold shadow-md">
                                <?php echo $customer_initials; ?>
                            </div>
                        <?php endif; ?>
                        <div class="ml-3">
                            <div class="text-base font-medium text-white"><?php echo htmlspecialchars($customer_name); ?></div>
                            <div class="text-sm font-medium text-gray-400"><?php echo htmlspecialchars($customer_email); ?></div>
                        </div>
                    </div>
                    <div class="mt-3 px-2 space-y-1">
                        <a href="profile.php" class="flex items-center px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                            <i class="fas fa-user mr-3 text-accent"></i> 
                            Profile
                        </a>
                    
                        <a href="logout.php" class="flex items-center px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-all duration-200 border-l-2 border-transparent hover:border-accent">
                            <i class="fas fa-sign-out-alt mr-3 text-accent"></i> 
                            Sign out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Spacer for the fixed navbar -->
    <div class="h-16"></div>

    
    <script>
        // Toggle mobile menu
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Function to toggle dropdowns in mobile view
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const chevron = document.getElementById(id.replace('-dropdown', '-chevron'));
            
            dropdown.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
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