<?php
// Start session and include database connection
session_start();
require_once __DIR__ . '/../config/db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Handle Add to Cart functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $response = array('success' => false, 'message' => '');
    
    try {
        if (!$user_id) {
            throw new Exception('Please login first to add items to cart');
        }
        
        if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
            throw new Exception('Invalid service selection');
        }
        
        $service_id = intval($_POST['service_id']);
        
        // Check if service exists and get its price
        $service_check_sql = "SELECT sid, name, price FROM services WHERE sid = ? AND service_type = 'maintenance'";
        $service_stmt = $conn->prepare($service_check_sql);
        if (!$service_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $service_stmt->bind_param("i", $service_id);
        $service_stmt->execute();
        $service_result = $service_stmt->get_result();
        
        if ($service_result->num_rows === 0) {
            throw new Exception('Service is no longer available');
        }
        
        $service_data = $service_result->fetch_assoc();
        $service_stmt->close();
        
        // Get or create cart for user
        $cart_sql = "SELECT id FROM carts WHERE user_id = ?";
        $cart_stmt = $conn->prepare($cart_sql);
        if (!$cart_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $cart_result = $cart_stmt->get_result();
        
        if ($cart_result->num_rows > 0) {
            $cart = $cart_result->fetch_assoc();
            $cart_id = $cart['id'];
        } else {
            // Create new cart for user
            $insert_cart_sql = "INSERT INTO carts (user_id) VALUES (?)";
            $insert_cart_stmt = $conn->prepare($insert_cart_sql);
            if (!$insert_cart_stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            $insert_cart_stmt->bind_param("i", $user_id);
            if (!$insert_cart_stmt->execute()) {
                throw new Exception('Failed to create cart: ' . $insert_cart_stmt->error);
            }
            $cart_id = $conn->insert_id;
            $insert_cart_stmt->close();
        }
        $cart_stmt->close();
        
        // Check if this service is already in the cart
        $check_existing_sql = "SELECT id FROM cart_items WHERE cart_id = ? AND item_type = 'service' AND item_id = ?";
        $check_stmt = $conn->prepare($check_existing_sql);
        if (!$check_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $check_stmt->bind_param("ii", $cart_id, $service_id);
        $check_stmt->execute();
        $existing_result = $check_stmt->get_result();
        
        if ($existing_result->num_rows > 0) {
            // Item already exists in cart, don't add duplicate
            $response['success'] = false;
            $response['message'] = '⚠️ ' . $service_data['name'] . ' is already in your cart!';
        } else {
            // Insert new item into cart_items table
            $insert_sql = "INSERT INTO cart_items (cart_id, item_type, item_id, unit_price, meta) VALUES (?, 'service', ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            if (!$insert_stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            // Prepare meta data (service-specific information)
            $meta_data = json_encode([
                'service_name' => $service_data['name'],
                'service_type' => 'maintenance',
                'added_at' => date('Y-m-d H:i:s')
            ]);
            
            $insert_stmt->bind_param("iids", $cart_id, $service_id, $service_data['price'], $meta_data);
            
            if ($insert_stmt->execute()) {
                $response['success'] = true;
                $response['message'] = '🔧 ' . $service_data['name'] . ' successfully added to your cart!';
            } else {
                throw new Exception('Failed to add item to cart: ' . $insert_stmt->error);
            }
            
            $insert_stmt->close();
        }
        
        $check_stmt->close();
        
    } catch (Exception $e) {
        $response['message'] = '❌ ' . $e->getMessage();
    }
    
    // Return JSON response for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Handle Wishlist functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_to_wishlist']) || isset($_POST['remove_from_wishlist']))) {
    $response = array('success' => false, 'message' => '');
    
    try {
        if (!$user_id) {
            throw new Exception('Please login first to manage your wishlist');
        }
        
        if (!isset($_POST['item_id']) || empty($_POST['item_id']) || !isset($_POST['item_type']) || empty($_POST['item_type'])) {
            throw new Exception('Invalid item data');
        }
        
        $item_id = intval($_POST['item_id']);
        $item_type = $_POST['item_type'];
        
        // Validate item_type
        $allowed_types = ['car', 'service', 'driving_course', 'part'];
        if (!in_array($item_type, $allowed_types)) {
            throw new Exception('Invalid item type');
        }
        
        if (isset($_POST['add_to_wishlist'])) {
            // Add to wishlist
            // Check if item already exists in wishlist
            $check_sql = "SELECT id FROM wishlist WHERE user_id = ? AND item_type = ? AND item_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            $check_stmt->bind_param("isi", $user_id, $item_type, $item_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                throw new Exception('Item is already in your wishlist');
            }
            
            $check_stmt->close();
            
            // Insert into wishlist
            $insert_sql = "INSERT INTO wishlist (user_id, item_type, item_id) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            if (!$insert_stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            $insert_stmt->bind_param("isi", $user_id, $item_type, $item_id);
            
            if ($insert_stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Item successfully added to wishlist';
            } else {
                throw new Exception('Failed to add item to wishlist: ' . $insert_stmt->error);
            }
            
            $insert_stmt->close();
            
        } elseif (isset($_POST['remove_from_wishlist'])) {
            // Remove from wishlist
            $delete_sql = "DELETE FROM wishlist WHERE user_id = ? AND item_type = ? AND item_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            if (!$delete_stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            $delete_stmt->bind_param("isi", $user_id, $item_type, $item_id);
            
            if ($delete_stmt->execute()) {
                if ($delete_stmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Item successfully removed from wishlist';
                } else {
                    throw new Exception('Item not found in wishlist');
                }
            } else {
                throw new Exception('Failed to remove item from wishlist: ' . $delete_stmt->error);
            }
            
            $delete_stmt->close();
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    // Return JSON response for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Fetch maintenance services from database using the new services table
$sql = "SELECT * FROM services WHERE service_type = 'maintenance' ORDER BY name ASC";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Prepare services data for Vue.js
$services_data = [];
if ($result && $result->num_rows > 0) {
    while($service = $result->fetch_assoc()) {
        $image_url = !empty($service['picture']) ? $service['picture'] : 'https://images.unsplash.com/photo-1621905252507-b35492cc74b4?ixlib=rb-4.0.3&auto=format&fit=crop&w=2069&q=80';
        
        $services_data[] = [
            'id' => $service['sid'],
            'name' => $service['name'] ?? 'Unnamed Service',
            'description' => $service['description'] ?? 'Professional maintenance service',
            'price' => $service['price'] ?? 0,
            'image' => $image_url,
            'vehicle_type' => $service['vehicle_type'] ?? 'Car',
            'duration' => $service['duration'] ?? '1-2 hours',
            'type' => 'service'
        ];
    }
}

// Get unique vehicle types for filters
$vehicle_sql = "SELECT DISTINCT vehicle_type FROM services WHERE service_type = 'maintenance' AND vehicle_type IS NOT NULL AND vehicle_type != ''";
$vehicle_result = $conn->query($vehicle_sql);
$vehicle_types = [];
if ($vehicle_result && $vehicle_result->num_rows > 0) {
    while($row = $vehicle_result->fetch_assoc()) {
        $vehicle_types[] = $row['vehicle_type'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Services - AutoManager</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Hide Vue.js content until it's loaded */
        [v-cloak] {
            display: none;
        }

        .service-card {
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .service-card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .service-card-buttons {
            margin-top: auto;
        }
        .toast {
            transition: all 0.5s ease;
        }
        .fade-enter-active, .fade-leave-active {
            transition: opacity 0.5s;
        }
        .fade-enter, .fade-leave-to {
            opacity: 0;
        }
        .wishlist-btn.active {
            color: #ef4444;
            transform: scale(1.1);
        }
        body {
            background-color: #f8fafc;
        }
        
        /* Enhanced Toast Styles */
        .toast-container {
            position: fixed;
            top: 90px;
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
        
        .hover-details {
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .service-card:hover .hover-details {
            opacity: 1;
            max-height: 100px;
        }
        
        /* Button Styles */
        .btn-compact {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-add-cart {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            font-weight: 500;
        }
        
        .btn-add-cart:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        /* Loading animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        /* Hero Section Styles */
        .hero-section {
            background-image: url('../resources/login_bg2x.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        /* Service-specific styles */
        .service-feature {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .service-feature i {
            color: #3b82f6;
            width: 16px;
        }
        
        /* Text truncation */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Vehicle type badge */
        .vehicle-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(59, 130, 246, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Loading Skeleton */
        .skeleton-loader {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
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
            
            .button-group {
                flex-direction: column;
                gap: 8px;
            }
            
            .btn-compact {
                width: 100%;
                justify-content: center;
            }
            
            .service-card {
                margin-bottom: 1.5rem;
            }
        }
        
        /* Ensure consistent card heights */
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-radius: 50%;
            border-top: 3px solid #3498db;
            animation: spin 1s linear infinite;
        }

        /* Smooth transitions for Vue */
        [v-cloak] {
            display: none;
        }
    </style>
</head>
<body class="font-sans">

<nav class="fixed top-0 left-0 w-full z-50 bg-white shadow">
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    include __DIR__ . '/../navbar.php';
    ?>
</nav>

<!-- Enhanced Toast Notification Container -->
<div id="toast-container" class="toast-container"></div>

<div id="app" v-cloak>
    <!-- HERO SECTION -->
    <section class="relative h-96 bg-center hero-section mt-16">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="relative container mx-auto px-4 h-full flex flex-col justify-center items-center text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                <i class="fas fa-tools mr-3"></i>Maintenance Services
            </h1>
            <p class="text-xl text-white mb-8">Keep your vehicle running smoothly with professional maintenance</p>
            <a href="#service-listings" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300">
                <i class="fas fa-wrench mr-2"></i>View Services
            </a>
        </div>
    </section>

    <!-- Service Listings Section -->
    <section id="service-listings" class="container mx-auto px-4 py-16">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold mb-4 text-gray-800">Premium Maintenance Services</h2>
            <p class="text-gray-600 max-w-2xl mx-auto text-lg">Professional maintenance services to keep your vehicle in top condition</p>
            
            <!-- Filter Buttons -->
            <div class="flex flex-wrap justify-center gap-4 mt-8">
                <button 
                    v-for="filter in dynamicFilters" 
                    :key="filter.id"
                    @click="setActiveFilter(filter.id)"
                    class="px-6 py-2 rounded-full transition duration-300"
                    :class="activeFilter === filter.id ? 
                           'bg-blue-600 text-white' : 
                           'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'">
                    {{ filter.name }}
                </button>
            </div>
        </div>

        <!-- Loading State with Skeleton -->
        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
            <!-- Skeleton Loaders -->
            <div v-for="n in 6" :key="n" class="bg-white rounded-2xl overflow-hidden shadow-lg border border-gray-100">
                <div class="skeleton-loader w-full h-56"></div>
                <div class="p-6">
                    <div class="skeleton-loader h-6 w-3/4 mb-3 rounded"></div>
                    <div class="skeleton-loader h-4 w-full mb-2 rounded"></div>
                    <div class="skeleton-loader h-4 w-2/3 mb-4 rounded"></div>
                    <div class="flex justify-between mb-5">
                        <div class="skeleton-loader h-4 w-20 rounded"></div>
                        <div class="skeleton-loader h-4 w-20 rounded"></div>
                    </div>
                    <div class="skeleton-loader h-12 w-full rounded-lg"></div>
                </div>
            </div>
        </div>

        <!-- Services Grid -->
        <div v-else-if="services.length > 0" class="service-grid">
            <!-- Service Card -->
            <div 
                v-for="service in filteredServices" 
                :key="service.id"
                class="service-card bg-white rounded-2xl overflow-hidden shadow-lg border border-gray-100">
                <div class="relative">
                    <img :src="service.image" :alt="service.name" class="w-full h-56 object-cover">
                    <div class="vehicle-badge">{{ service.vehicle_type }}</div>
                    <div class="absolute top-4 right-4 flex space-x-2">
                        <button 
                            @click="toggleWishlist(service)"
                            class="wishlist-btn bg-white p-3 rounded-full shadow-md transition duration-300"
                            :class="{ 'active': isInWishlist(service.id) }">
                            <i class="fas" :class="isInWishlist(service.id) ? 'fa-heart text-red-500' : 'fa-heart text-gray-400'"></i>
                        </button>
                    </div>
                    <div class="absolute bottom-4 left-4 bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                        ৳{{ formatPrice(service.price) }}
                    </div>
                </div>
                
                <div class="service-card-content p-6">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="text-xl font-bold text-gray-800">{{ service.name }}</h3>
                        <span class="text-green-600 font-semibold">Maintenance</span>
                    </div>
                    
                    <p class="text-gray-600 mb-5 line-clamp-2">{{ service.description }}</p>
                    
                    <div class="flex justify-between items-center mb-5">
                        <div class="flex space-x-4 text-gray-500">
                            <div class="flex items-center">
                                <i class="fas fa-car mr-2 text-blue-500"></i>
                                <span>{{ service.vehicle_type }}</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2 text-blue-500"></i>
                                <span>{{ service.duration }}</span>
                            </div>
                        </div>
                        <div class="flex items-center text-gray-500">
                            <i class="fas fa-tools mr-2 text-blue-500"></i>
                            <span>Service</span>
                        </div>
                    </div>
                    
                    <div class="service-card-buttons flex space-x-3">
                        <button 
                            @click="addToCart(service)"
                            class="flex-1 bg-gray-800 hover:bg-gray-900 text-white py-3 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-cart-plus mr-2"></i> Add to Cart
                        </button>
                        <button 
                            @click="toggleWishlist(service)"
                            class="px-4 py-3 rounded-lg transition duration-300 flex items-center justify-center"
                            :class="isInWishlist(service.id) ? 
                                   'bg-red-100 text-red-600 border border-red-200' : 
                                   'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-200'">
                            <i class="fas" :class="isInWishlist(service.id) ? 'fa-heart text-red-500' : 'fa-heart text-gray-500'"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && services.length === 0" class="text-center py-16">
            <div class="bg-white rounded-2xl shadow-lg p-12 max-w-2xl mx-auto">
                <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-tools text-blue-500 text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No services available</h3>
                <p class="text-gray-600 mb-8 text-lg">We couldn't find any maintenance services at the moment. Please check back later.</p>
                <button 
                    @click="loadServices"
                    class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300">
                    Refresh Services
                </button>
            </div>
        </div>

        <!-- No Filter Results Message -->
        <div v-if="!loading && services.length > 0 && filteredServices.length === 0" class="text-center py-12">
            <div class="max-w-md mx-auto">
                <i class="fas fa-search text-6xl text-gray-300 mb-6"></i>
                <h3 class="text-2xl font-semibold text-gray-600 mb-4">No Services Found</h3>
                <p class="text-gray-500 text-lg mb-6">No services match your current filter selection. Try a different filter.</p>
                <button @click="setActiveFilter('all')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                    <i class="fas fa-times mr-2"></i>Clear Filter
                </button>
            </div>
        </div>
    </section>
</div>

<?php
include __DIR__ . '/../footer.php';
// Close connection
$conn->close();
?>

<script>
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

    // Initialize Vue app
    document.addEventListener('DOMContentLoaded', function() {
        new Vue({
            el: '#app',
            data: {
                services: [],
                wishlist: [],
                activeFilter: 'all',
                loading: true,
                userId: <?php echo $user_id ? $user_id : 'null'; ?>,
                vehicleTypes: <?php echo json_encode($vehicle_types); ?>
            },
            computed: {
                dynamicFilters() {
                    // Create filters based on actual vehicle types in the database
                    const baseFilters = [
                        { id: 'all', name: 'All Services' }
                    ];
                    
                    // Add vehicle type filters from database
                    if (this.vehicleTypes && this.vehicleTypes.length > 0) {
                        this.vehicleTypes.forEach(vehicleType => {
                            if (vehicleType && vehicleType.trim() !== '') {
                                baseFilters.push({
                                    id: vehicleType.toLowerCase().replace(/\s+/g, '_'),
                                    name: vehicleType
                                });
                            }
                        });
                    } else {
                        // Fallback filters if no vehicle types in database
                        baseFilters.push(
                            { id: 'car', name: 'Car' },
                            { id: 'suv', name: 'SUV' },
                            { id: 'truck', name: 'Truck' },
                            { id: 'motorcycle', name: 'Motorcycle' }
                        );
                    }
                    
                    return baseFilters;
                },
                filteredServices() {
                    if (this.activeFilter === 'all') {
                        return this.services;
                    }
                    
                    // Filter services based on actual vehicle type from database
                    return this.services.filter(service => {
                        if (!service.vehicle_type) return false;
                        
                        const serviceVehicleType = service.vehicle_type.toLowerCase().replace(/\s+/g, '_');
                        const activeFilter = this.activeFilter.toLowerCase();
                        
                        // Exact match for vehicle type
                        return serviceVehicleType === activeFilter;
                    });
                }
            },
            methods: {
                formatPrice(price) {
                    // Format price with commas
                    return parseFloat(price).toLocaleString('en-BD', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },
                addToCart(service) {
                    if (!this.userId) {
                        showToast('Please login to add items to cart', 'error');
                        return;
                    }

                    const button = event.currentTarget;
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
                    button.disabled = true;
                    
                    const formData = new FormData();
                    formData.append('add_to_cart', '1');
                    formData.append('service_id', service.id);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            button.innerHTML = '<i class="fas fa-check mr-2"></i>Added!';
                            button.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                            
                            setTimeout(() => {
                                button.innerHTML = originalHTML;
                                button.style.background = '';
                                button.disabled = false;
                            }, 2000);
                        } else {
                            showToast(data.message, 'error');
                            button.innerHTML = originalHTML;
                            button.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('❌ Network error. Please try again.', 'error');
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                    });
                },
                async toggleWishlist(service) {
                    if (!this.userId) {
                        showToast('Please login to manage your wishlist', 'error');
                        return;
                    }
                    
                    const isInWishlist = this.isInWishlist(service.id);
                    const action = isInWishlist ? 'remove_from_wishlist' : 'add_to_wishlist';
                    
                    try {
                        const formData = new FormData();
                        formData.append(action, '1');
                        formData.append('item_id', service.id);
                        formData.append('item_type', 'service');
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            if (isInWishlist) {
                                // Remove from wishlist (only update local state)
                                this.wishlist = this.wishlist.filter(item => item.id !== service.id);
                                showToast(`${service.name} removed from wishlist`, 'success');
                            } else {
                                // Add to wishlist (only update local state)
                                this.wishlist.push(service);
                                showToast(`${service.name} added to wishlist`, 'success');
                            }
                        } else {
                            showToast(result.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showToast('Network error. Please try again.', 'error');
                    }
                },
                isInWishlist(serviceId) {
                    return this.wishlist.some(item => item.id === serviceId);
                },
                setActiveFilter(filterId) {
                    this.activeFilter = filterId;
                    // Scroll to services section when filter changes
                    setTimeout(() => {
                        document.getElementById('service-listings').scrollIntoView({ 
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                },
                loadServices() {
                    // Reload the page to refresh services
                    window.location.reload();
                },
                initializeServices() {
                    // Initialize services from PHP data with a slight delay for better UX
                    const initialServices = <?php echo json_encode($services_data); ?>;
                    
                    // Simulate loading for better UX
                    setTimeout(() => {
                        this.services = initialServices;
                        this.loading = false;
                    }, 800);
                }
            },
            mounted() {
                this.initializeServices();
                
                // Fix for any cards that might have missing data
                this.services = this.services.map(service => {
                    return {
                        id: service.id || 0,
                        name: service.name || 'Unnamed Service',
                        description: service.description || 'Professional maintenance service',
                        price: service.price || 0,
                        image: service.image || 'https://images.unsplash.com/photo-1621905252507-b35492cc74b4?ixlib=rb-4.0.3&auto=format&fit=crop&w=2069&q=80',
                        vehicle_type: service.vehicle_type || 'Car',
                        duration: service.duration || '1-2 hours',
                        type: service.type || 'service'
                    };
                });
            }
        });
    });
</script>

</body>
</html>