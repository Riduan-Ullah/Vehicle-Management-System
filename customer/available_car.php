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
        
        if (!isset($_POST['car_id']) || empty($_POST['car_id'])) {
            throw new Exception('Invalid car selection');
        }
        
        $car_id = intval($_POST['car_id']);
        
        // Check if car exists and is available, and get its price
        $car_check_sql = "SELECT id, name, price_per_day FROM cars WHERE id = ? AND quantity > 0";
        $car_stmt = $conn->prepare($car_check_sql);
        if (!$car_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $car_stmt->bind_param("i", $car_id);
        $car_stmt->execute();
        $car_result = $car_stmt->get_result();
        
        if ($car_result->num_rows === 0) {
            throw new Exception('Car is no longer available');
        }
        
        $car_data = $car_result->fetch_assoc();
        $car_stmt->close();
        
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
        
        // Check if this car is already in the cart
        $check_existing_sql = "SELECT id FROM cart_items WHERE cart_id = ? AND item_type = 'car' AND item_id = ?";
        $check_stmt = $conn->prepare($check_existing_sql);
        if (!$check_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $check_stmt->bind_param("ii", $cart_id, $car_id);
        $check_stmt->execute();
        $existing_result = $check_stmt->get_result();
        
        if ($existing_result->num_rows > 0) {
            // Item already exists in cart, don't add duplicate
            $response['success'] = false;
            $response['message'] = '⚠️ ' . $car_data['name'] . ' is already in your cart!';
        } else {
            // Insert new item into cart_items table with NULL for rental dates and default quantity
            $insert_sql = "INSERT INTO cart_items (cart_id, item_type, item_id, unit_price, meta) VALUES (?, 'car', ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            if (!$insert_stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            // Prepare meta data (car-specific information)
            $meta_data = json_encode([
                'car_name' => $car_data['name'],
                'image_url' => isset($_POST['image_url']) ? $_POST['image_url'] : null,
                'added_at' => date('Y-m-d H:i:s')
            ]);
            
            $insert_stmt->bind_param("iids", $cart_id, $car_id, $car_data['price_per_day'], $meta_data);
            
            if ($insert_stmt->execute()) {
                $response['success'] = true;
                $response['message'] = '🚗 ' . $car_data['name'] . ' successfully added to your cart!';
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

// Get wishlist items (for AJAX requests)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_wishlist'])) {
    $response = array('success' => false, 'message' => '', 'wishlistItems' => []);
    
    try {
        if (!$user_id) {
            throw new Exception('Please login to view your wishlist');
        }
        
        $item_type = isset($_GET['item_type']) ? $_GET['item_type'] : 'car';
        
        // Get wishlist items
        $sql = "SELECT w.*, c.name, c.image_url, c.price_per_day, c.description, c.seats, c.transmission, c.fuel_type 
                FROM wishlist w 
                LEFT JOIN cars c ON w.item_id = c.id 
                WHERE w.user_id = ? AND w.item_type = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("is", $user_id, $item_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $wishlist_items = [];
        while ($item = $result->fetch_assoc()) {
            $wishlist_items[] = [
                'id' => $item['item_id'],
                'name' => $item['name'],
                'brand' => 'Car',
                'description' => $item['description'],
                'price' => $item['price_per_day'],
                'image' => $item['image_url'] ?: 'https://images.unsplash.com/photo-1549399542-7e82138d0dca?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
                'seats' => $item['seats'],
                'transmission' => $item['transmission'],
                'fuel' => $item['fuel_type']
            ];
        }
        
        $response['success'] = true;
        $response['wishlistItems'] = $wishlist_items;
        $stmt->close();
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Fetch cars from database
$sql = "SELECT * FROM cars WHERE quantity > 0 ORDER BY price_per_day ASC";
$result = $conn->query($sql);

// Fetch user's wishlist for initial page load
$user_wishlist = [];
if ($user_id) {
    $wishlist_sql = "SELECT item_id FROM wishlist WHERE user_id = ? AND item_type = 'car'";
    $wishlist_stmt = $conn->prepare($wishlist_sql);
    if ($wishlist_stmt) {
        $wishlist_stmt->bind_param("i", $user_id);
        $wishlist_stmt->execute();
        $wishlist_result = $wishlist_stmt->get_result();
        
        while ($item = $wishlist_result->fetch_assoc()) {
            $user_wishlist[] = $item['item_id'];
        }
        $wishlist_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Car Rentals</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .car-card {
            transition: all 0.3s ease;
        }
        .car-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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
        .car-card:hover .hover-details {
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
        
        .btn-book-now {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            font-weight: 500;
        }
        
        .btn-book-now:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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
            background-image: url('../resources/ac_image1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
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

<!-- Booking Modal -->
<div id="booking-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg w-11/12 md:w-2/3 lg:w-1/2 max-h-screen overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Book Your Car</h3>
                <button id="close-modal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modal-content" class="space-y-4">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<div id="app" v-cloak>
    <!-- NEW HERO SECTION -->
    <section class="relative h-96 bg-center hero-section mt-16">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="relative container mx-auto px-4 h-full flex flex-col justify-center items-center text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Find Your Dream Ride</h1>
            <p class="text-xl text-white mb-8">Discover the perfect car for your next adventure</p>
            <a href="#car-listings" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300">Browse Cars</a>
        </div>
    </section>

    <!-- Car Listings Section -->
    <section id="car-listings" class="container mx-auto px-4 py-16">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold mb-4 text-gray-800">Premium Collection</h2>
            <p class="text-gray-600 max-w-2xl mx-auto text-lg">Choose from our exclusive selection of luxury and performance vehicles</p>
            
            <!-- Filter Buttons -->
            <div class="flex flex-wrap justify-center gap-4 mt-8">
                <button 
                    v-for="filter in filters" 
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

        <!-- Loading State -->
        <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
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

        <!-- Cars Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
            <!-- Car Card -->
            <div 
                v-for="car in filteredCars" 
                :key="car.id"
                class="car-card bg-white rounded-2xl overflow-hidden shadow-lg border border-gray-100">
                <div class="relative">
                    <img :src="car.image" :alt="car.name" class="w-full h-56 object-cover">
                    <div class="absolute top-4 right-4 flex space-x-2">
                        <button 
                            @click="toggleWishlist(car)"
                            class="wishlist-btn bg-white p-3 rounded-full shadow-md transition duration-300"
                            :class="{ 'active': isInWishlist(car.id) }">
                            <i class="fas" :class="isInWishlist(car.id) ? 'fa-heart text-red-500' : 'fa-heart text-gray-400'"></i>
                        </button>
                    </div>
                    <div class="absolute bottom-4 left-4 bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                        ${{ car.price }}/day
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="text-xl font-bold text-gray-800">{{ car.name }}</h3>
                        <span class="text-blue-600 font-semibold">{{ car.brand }}</span>
                    </div>
                    
                    <p class="text-gray-600 mb-5">{{ car.description }}</p>
                    
                    <div class="flex justify-between items-center mb-5">
                        <div class="flex space-x-4 text-gray-500">
                            <div class="flex items-center">
                                <i class="fas fa-user mr-2 text-blue-500"></i>
                                <span>{{ car.seats }} Seats</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-cog mr-2 text-blue-500"></i>
                                <span>{{ car.transmission }}</span>
                            </div>
                        </div>
                        <div class="flex items-center text-gray-500">
                            <i class="fas mr-2" :class="car.fuel === 'Electric' ? 'fa-bolt text-blue-500' : 'fa-gas-pump text-blue-500'"></i>
                            <span>{{ car.fuel }}</span>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button 
                            @click="addToCart(car)"
                            class="flex-1 bg-gray-800 hover:bg-gray-900 text-white py-3 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-cart-plus mr-2"></i> Add to Cart
                        </button>
                        <button 
                            @click="toggleWishlist(car)"
                            class="px-4 py-3 rounded-lg transition duration-300 flex items-center justify-center"
                            :class="isInWishlist(car.id) ? 
                                   'bg-red-100 text-red-600 border border-red-200' : 
                                   'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-200'">
                            <i class="fas" :class="isInWishlist(car.id) ? 'fa-heart text-red-500' : 'fa-heart text-gray-500'"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!isLoading && filteredCars.length === 0" class="text-center py-16">
            <div class="bg-white rounded-2xl shadow-lg p-12 max-w-2xl mx-auto">
                <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-car text-blue-500 text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No cars found</h3>
                <p class="text-gray-600 mb-8 text-lg">We couldn't find any cars matching your current filter. Try selecting a different category.</p>
                <button 
                    @click="setActiveFilter('all')"
                    class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300">
                    Show All Cars
                </button>
            </div>
        </div>
    </section>
</div>

<?php
include __DIR__ . '/../footer.php';
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
                cars: [],
                wishlist: [],
                activeFilter: 'all',
                filters: [
                    { id: 'all', name: 'All Cars' },
                    { id: 'suv', name: 'SUV' },
                    { id: 'sports', name: 'Sports' },
                    { id: 'sedan', name: 'Sedan' },
                    { id: 'electric', name: 'Electric' }
                ],
                userId: <?php echo $user_id ? $user_id : 'null'; ?>,
                userWishlist: <?php echo json_encode($user_wishlist); ?>,
                isLoading: true
            },
            computed: {
                filteredCars() {
                    if (this.activeFilter === 'all') {
                        return this.cars;
                    }
                    // Simple filtering based on car characteristics
                    return this.cars.filter(car => {
                        if (this.activeFilter === 'suv') {
                            return car.name.toLowerCase().includes('suv') || car.name.toLowerCase().includes('x5') || car.name.toLowerCase().includes('q7') || car.name.toLowerCase().includes('wrangler');
                        } else if (this.activeFilter === 'sports') {
                            return car.name.toLowerCase().includes('porsche') || car.name.toLowerCase().includes('911');
                        } else if (this.activeFilter === 'sedan') {
                            return car.name.toLowerCase().includes('camry');
                        } else if (this.activeFilter === 'electric') {
                            return car.fuel === 'Electric';
                        }
                        return false;
                    });
                }
            },
            methods: {
                addToCart(car) {
                    if (!this.userId) {
                        showToast('Please login to add items to cart', 'error');
                        return;
                    }

                    const button = event.target;
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
                    button.disabled = true;
                    
                    const formData = new FormData();
                    formData.append('add_to_cart', '1');
                    formData.append('car_id', car.id);
                    formData.append('image_url', car.image);
                    
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
                async toggleWishlist(car) {
                    if (!this.userId) {
                        showToast('Please login to manage your wishlist', 'error');
                        return;
                    }
                    
                    const isInWishlist = this.isInWishlist(car.id);
                    const action = isInWishlist ? 'remove_from_wishlist' : 'add_to_wishlist';
                    
                    try {
                        const formData = new FormData();
                        formData.append(action, '1');
                        formData.append('item_id', car.id);
                        formData.append('item_type', 'car');
                        
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
                                // Remove from wishlist
                                this.wishlist = this.wishlist.filter(item => item.id !== car.id);
                                showToast(`${car.name} removed from wishlist`, 'success');
                            } else {
                                // Add to wishlist
                                this.wishlist.push(car);
                                showToast(`${car.name} added to wishlist`, 'success');
                            }
                        } else {
                            showToast(result.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showToast('Network error. Please try again.', 'error');
                    }
                },
                isInWishlist(carId) {
                    return this.wishlist.some(item => item.id === carId);
                },
                setActiveFilter(filterId) {
                    this.activeFilter = filterId;
                },
                async loadWishlist() {
                    if (!this.userId) return;
                    
                    try {
                        const response = await fetch(`?get_wishlist=1&user_id=${this.userId}&item_type=car`);
                        const result = await response.json();
                        
                        if (result.success) {
                            this.wishlist = result.wishlistItems;
                        } else {
                            console.error('Failed to load wishlist:', result.message);
                        }
                    } catch (error) {
                        console.error('Error loading wishlist:', error);
                    }
                },
                initializeCars() {
                    // Initialize cars from PHP data with a slight delay for better UX
                    const initialCars = [
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while($car = $result->fetch_assoc()) {
                                $image_url = $car['image_url'] ?: 'https://images.unsplash.com/photo-1549399542-7e82138d0dca?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80';
                                echo "{
                                    id: " . $car['id'] . ",
                                    name: '" . addslashes($car['name']) . "',
                                    brand: 'Car',
                                    description: '" . addslashes($car['description'] ?: 'Premium vehicle for your needs') . "',
                                    price: " . $car['price_per_day'] . ",
                                    image: '" . $image_url . "',
                                    seats: " . $car['seats'] . ",
                                    transmission: '" . $car['transmission'] . "',
                                    fuel: '" . $car['fuel_type'] . "',
                                    type: 'car'
                                },";
                            }
                        }
                        ?>
                    ];
                    
                    // Simulate loading for better UX
                    setTimeout(() => {
                        this.cars = initialCars;
                        this.isLoading = false;
                    }, 800);
                }
            },
            mounted() {
                this.initializeCars();
                
                // Initialize wishlist with server-side data
                if (this.userId && this.userWishlist.length > 0) {
                    // Map wishlist IDs to car objects
                    this.wishlist = this.userWishlist
                        .map(id => this.cars.find(car => car.id === parseInt(id)))
                        .filter(car => car !== undefined);
                }
                
                // Also load via AJAX to ensure consistency
                this.loadWishlist();
            }
        });
    });

    // Modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Close modal
        document.getElementById('close-modal').addEventListener('click', function() {
            document.getElementById('booking-modal').classList.add('hidden');
        });

        // Close modal when clicking outside
        document.getElementById('booking-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        // Add keyboard event listener for Escape key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('booking-modal').classList.add('hidden');
            }
        });
    });

    // Open booking modal
    function openBookingModal(carData) {
        const modalContent = document.getElementById('modal-content');
        modalContent.innerHTML = `
            <div class="flex flex-col md:flex-row gap-6">
                <div class="md:w-1/2">
                    <img src="${carData.image}" alt="${carData.name}" class="w-full h-64 object-cover rounded-lg">
                </div>
                <div class="md:w-1/2">
                    <h4 class="text-2xl font-bold mb-2">${carData.name}</h4>
                    <p class="text-blue-600 text-xl font-bold mb-4">$${carData.price}/day</p>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-user text-blue-500 mr-2"></i>
                            <span>${carData.seats} Seats</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-cog text-blue-500 mr-2"></i>
                            <span>${carData.transmission}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas ${carData.fuel === 'Electric' ? 'fa-bolt' : 'fa-gas-pump'} text-blue-500 mr-2"></i>
                            <span>${carData.fuel}</span>
                        </div>
                    </div>
                    
                    <form class="space-y-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Pickup Date</label>
                            <input type="date" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Return Date</label>
                            <input type="date" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button type="button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
                            Confirm Booking
                        </button>
                    </form>
                </div>
            </div>
        `;
        
        document.getElementById('booking-modal').classList.remove('hidden');
    }
</script>

</body>
</html>