<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Handle Add to Cart from Wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart_from_wishlist'])) {
    $response = array('success' => false, 'message' => '');
    
    try {
        if (!$user_id) {
            throw new Exception('Please login first to add items to cart');
        }
        
        if (!isset($_POST['item_id']) || empty($_POST['item_id']) || !isset($_POST['item_type']) || empty($_POST['item_type'])) {
            throw new Exception('Invalid item selection');
        }
        
        $item_id = intval($_POST['item_id']);
        $item_type = $_POST['item_type'];
        
        // Validate item_type
        $allowed_types = ['car', 'service', 'driving_course', 'part'];
        if (!in_array($item_type, $allowed_types)) {
            throw new Exception('Invalid item type');
        }
        
        // Get item details based on type
        $item_data = null;
        switch($item_type) {
            case 'car':
                $sql = "SELECT id, name, price_per_day as price, quantity FROM cars WHERE id = ?";
                break;
            case 'service':
                $sql = "SELECT sid as id, name, price, 1 as quantity FROM services WHERE sid = ?";
                break;
            case 'driving_course':
                $sql = "SELECT dcid as id, name, price, 1 as quantity FROM driving_course WHERE dcid = ?";
                break;
            case 'part':
                $sql = "SELECT id, name, price, stock_quantity as quantity FROM parts WHERE id = ?";
                break;
            default:
                throw new Exception('Unsupported item type');
        }
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Item not found');
        }
        
        $item_data = $result->fetch_assoc();
        $stmt->close();
        
        // Check if item is available
        if ($item_data['quantity'] <= 0) {
            throw new Exception('This item is currently unavailable');
        }
        
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
        
        // Check if this item is already in the cart
        $check_existing_sql = "SELECT id FROM cart_items WHERE cart_id = ? AND item_type = ? AND item_id = ?";
        $check_stmt = $conn->prepare($check_existing_sql);
        if (!$check_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $check_stmt->bind_param("isi", $cart_id, $item_type, $item_id);
        $check_stmt->execute();
        $existing_result = $check_stmt->get_result();
        
        if ($existing_result->num_rows > 0) {
            // Item already exists in cart
            $response['success'] = false;
            $response['message'] = '⚠️ ' . $item_data['name'] . ' is already in your cart!';
        } else {
            // Insert new item into cart_items table
            $insert_sql = "INSERT INTO cart_items (cart_id, item_type, item_id, unit_price, quantity, meta) VALUES (?, ?, ?, ?, 1, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            if (!$insert_stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            // Prepare meta data
            $meta_data = json_encode([
                'item_name' => $item_data['name'],
                'added_at' => date('Y-m-d H:i:s')
            ]);
            
            $insert_stmt->bind_param("isids", $cart_id, $item_type, $item_id, $item_data['price'], $meta_data);
            
            if ($insert_stmt->execute()) {
                $response['success'] = true;
                $response['message'] = '✅ ' . $item_data['name'] . ' successfully added to your cart!';
                
                // Remove from wishlist after adding to cart
                $delete_sql = "DELETE FROM wishlist WHERE user_id = ? AND item_type = ? AND item_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                if ($delete_stmt) {
                    $delete_stmt->bind_param("isi", $user_id, $item_type, $item_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                }
            } else {
                throw new Exception('Failed to add item to cart: ' . $insert_stmt->error);
            }
            
            $insert_stmt->close();
        }
        
        $check_stmt->close();
        
    } catch (Exception $e) {
        $response['message'] = '❌ ' . $e->getMessage();
        error_log("Add to cart error: " . $e->getMessage());
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle Remove from Wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_wishlist'])) {
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
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log("Remove from wishlist error: " . $e->getMessage());
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle Clear All Wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all_wishlist'])) {
    $response = array('success' => false, 'message' => '');
    
    try {
        if (!$user_id) {
            throw new Exception('Please login first to manage your wishlist');
        }
        
        // Remove all items from wishlist for this user
        $delete_sql = "DELETE FROM wishlist WHERE user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        if (!$delete_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'All items removed from wishlist';
        } else {
            throw new Exception('Failed to clear wishlist: ' . $delete_stmt->error);
        }
        
        $delete_stmt->close();
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log("Clear wishlist error: " . $e->getMessage());
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get wishlist items for the page - ALL ITEM TYPES
$wishlist_items = [];
if ($user_id) {
    try {
        // Get wishlist items with details from respective tables
        $sql = "SELECT 
                    w.id as wishlist_id,
                    w.user_id,
                    w.item_type,
                    w.item_id,
                    w.added_at,
                    COALESCE(c.name, s.name, p.name, dc.name) as name,
                    COALESCE(c.description, s.description, p.description, dc.description) as description,
                    COALESCE(c.price_per_day, s.price, p.price, dc.price) as price,
                    COALESCE(c.image_url, s.picture, p.image_url, dc.picture) as image,
                    COALESCE(c.quantity, p.stock_quantity, 1) as quantity,
                    c.seats,
                    c.transmission,
                    c.fuel_type,
                    c.brand,
                    c.model_year,
                    s.service_type,
                    p.sku,
                    p.category,
                    dc.level,
                    dc.duration,
                    dc.vehicle_type
                FROM wishlist w 
                LEFT JOIN cars c ON w.item_type = 'car' AND w.item_id = c.id
                LEFT JOIN services s ON w.item_type = 'service' AND w.item_id = s.sid
                LEFT JOIN parts p ON w.item_type = 'part' AND w.item_id = p.id
                LEFT JOIN driving_course dc ON w.item_type = 'driving_course' AND w.item_id = dc.dcid
                WHERE w.user_id = ?
                ORDER BY w.added_at DESC";
        
        error_log("Executing wishlist query for user: " . $user_id);
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute query: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $wishlist_count = $result->num_rows;
        
        error_log("Found " . $wishlist_count . " wishlist items for user " . $user_id);
        
        while ($item = $result->fetch_assoc()) {
            $wishlist_item = [
                'wishlist_id' => $item['wishlist_id'],
                'item_id' => $item['item_id'],
                'item_type' => $item['item_type'],
                'name' => $item['name'],
                'description' => $item['description'],
                'price' => $item['price'],
                'image' => $item['image'] ?: getDefaultImage($item['item_type']),
                'quantity' => $item['quantity'] ?? 1,
                'added_at' => $item['added_at']
            ];
            
            // Add type-specific fields
            switch($item['item_type']) {
                case 'car':
                    $wishlist_item['seats'] = $item['seats'];
                    $wishlist_item['transmission'] = $item['transmission'];
                    $wishlist_item['fuel'] = $item['fuel_type'];
                    $wishlist_item['brand'] = $item['brand'];
                    $wishlist_item['model_year'] = $item['model_year'];
                    break;
                case 'service':
                    $wishlist_item['service_type'] = $item['service_type'];
                    break;
                case 'part':
                    $wishlist_item['sku'] = $item['sku'];
                    $wishlist_item['category'] = $item['category'];
                    break;
                case 'driving_course':
                    $wishlist_item['level'] = $item['level'];
                    $wishlist_item['duration'] = $item['duration'];
                    $wishlist_item['vehicle_type'] = $item['vehicle_type'];
                    break;
            }
            
            $wishlist_items[] = $wishlist_item;
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Error fetching wishlist: " . $e->getMessage());
        // Continue with empty wishlist items
    }
} else {
    error_log("User not logged in, cannot fetch wishlist");
}

// Helper function to get default image based on item type
function getDefaultImage($item_type) {
    switch($item_type) {
        case 'car':
            return 'https://images.unsplash.com/photo-1549399542-7e82138d0dca?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80';
        case 'service':
            return 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80';
        case 'part':
            return 'https://images.unsplash.com/photo-1603712607225-eeb0233846c9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80';
        case 'driving_course':
            return 'https://images.unsplash.com/photo-1565896314091-4d4bf5bf4a70?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80';
        default:
            return 'https://images.unsplash.com/photo-1549399542-7e82138d0dca?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Vehicle Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [v-cloak] {
            display: none;
        }

        .toast-container {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }

        .toast {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 10px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #10B981;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.5s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.hiding {
            opacity: 0;
            transform: translateX(100%);
        }

        .toast.error {
            border-left-color: #EF4444;
        }

        .toast.warning {
            border-left-color: #F59E0B;
        }

        .toast .toast-icon {
            font-size: 24px;
            margin-right: 12px;
        }

        .toast.success .toast-icon {
            color: #10B981;
        }

        .toast.error .toast-icon {
            color: #EF4444;
        }

        .toast.warning .toast-icon {
            color: #F59E0B;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .toast-message {
            font-size: 14px;
            color: #6B7280;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 16px;
            color: #9CA3AF;
            cursor: pointer;
            margin-left: 12px;
        }

        .loading-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .wishlist-item.removing {
            opacity: 0;
            transform: scale(0.9);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-1 {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .availability-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .availability-available {
            background-color: #10B981;
        }

        .availability-low {
            background-color: #F59E0B;
        }

        .availability-unavailable {
            background-color: #EF4444;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .empty-state {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        button:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 w-full z-50 bg-white shadow-lg">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        include __DIR__ . '/../navbar.php';
        ?>
    </nav>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Main Content -->
    <div id="app" class="pt-16 min-h-screen" v-cloak>
        <!-- Header Section -->
        <section class="gradient-bg text-white py-12">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-4xl font-bold mb-4">My Wishlist</h1>
                <p class="text-xl opacity-90">Your favorite items saved for later</p>
                <div class="mt-6 flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-6">
                    <div class="flex items-center bg-white bg-opacity-20 rounded-full px-4 py-2">
                        <i class="fas fa-heart mr-2"></i>
                        <span class="font-semibold">{{ wishlistItems.length }} {{ wishlistItems.length === 1 ? 'item' : 'items' }}</span>
                    </div>
                    <button 
                        v-if="wishlistItems.length > 0"
                        @click="clearAllWishlist" 
                        :disabled="isLoading"
                        class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-medium py-2 px-4 rounded-full transition duration-300 disabled:opacity-50 flex items-center">
                        <i class="fas fa-trash-alt mr-2"></i>
                        Clear All
                    </button>
                </div>
            </div>
        </section>

        <!-- Wishlist Content -->
        <section class="container mx-auto px-4 py-12">
            <!-- Empty State -->
            <div v-if="wishlistItems.length === 0 && !isLoading" class="empty-state bg-white rounded-2xl shadow-lg p-8 sm:p-12 text-center max-w-2xl mx-auto">
                <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gradient-to-br from-pink-100 to-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-heart text-red-400 text-3xl sm:text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">Your wishlist is empty</h3>
                <p class="text-gray-600 mb-8 text-lg">Start adding items you love to your wishlist for easy access later</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="index.php" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-3 px-6 sm:px-8 rounded-lg transition duration-300 transform hover:-translate-y-1 flex items-center justify-center">
                        <i class="fas fa-car mr-2"></i>Browse Vehicles
                    </a>
                    <a href="index.php" class="bg-white hover:bg-gray-100 text-gray-800 font-medium py-3 px-6 sm:px-8 border border-gray-300 rounded-lg transition duration-300 flex items-center justify-center">
                        <i class="fas fa-home mr-2"></i>Go Home
                    </a>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading && wishlistItems.length === 0" class="text-center py-16">
                <div class="inline-flex items-center justify-center p-4 bg-white rounded-2xl shadow-lg">
                    <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mr-3"></i>
                    <span class="text-gray-700 font-medium">Loading your wishlist...</span>
                </div>
            </div>

            <!-- Wishlist Items Grid -->
            <div v-if="wishlistItems.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Wishlist Item -->
                <div 
                    v-for="item in wishlistItems" 
                    :key="item.wishlist_id + '-' + item.item_type"
                    class="wishlist-item card-hover bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 flex flex-col h-full">
                    <div class="relative flex-shrink-0">
                        <img 
                            :src="item.image" 
                            :alt="item.name" 
                            class="w-full h-48 object-cover"
                            @error="handleImageError($event, item.item_type)"
                        >
                        <div class="availability-badge" :class="getAvailabilityClass(item.quantity)">
                            {{ getAvailabilityText(item.quantity) }}
                        </div>
                        <div class="absolute top-3 left-3 flex space-x-2">
                            <div class="bg-blue-600 text-white px-2 py-1 rounded-full text-xs font-bold">
                                {{ getItemTypeLabel(item.item_type) }}
                            </div>
                            <button 
                                @click="removeFromWishlist(item)"
                                :disabled="isLoading"
                                class="bg-white hover:bg-red-500 text-gray-800 hover:text-white p-2 rounded-full shadow-lg transition duration-300 disabled:opacity-50 flex items-center justify-center w-8 h-8">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                        <div class="absolute bottom-3 left-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg">
                            ${{ formatPrice(item.price) }}<span v-if="item.item_type === 'car'">/day</span>
                        </div>
                    </div>
                    
                    <div class="p-6 flex-grow flex flex-col">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-grow">
                                <h3 class="text-lg font-bold text-gray-800 mb-1 line-clamp-1">{{ item.name }}</h3>
                                <div class="flex items-center text-gray-600">
                                    <span class="text-sm line-clamp-1">{{ getItemDetails(item) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2 flex-grow">{{ item.description || 'No description available' }}</p>
                        
                        <!-- Item Specifications -->
                        <div class="grid grid-cols-3 gap-2 mb-4 text-center" v-if="item.item_type === 'car'">
                            <div class="bg-gray-50 rounded-lg p-2">
                                <i class="fas fa-user text-blue-500 text-sm mb-1"></i>
                                <div class="text-xs font-medium text-gray-700">{{ item.seats }} Seats</div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-2">
                                <i class="fas fa-cog text-blue-500 text-sm mb-1"></i>
                                <div class="text-xs font-medium text-gray-700">{{ item.transmission }}</div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-2">
                                <i class="fas text-sm mb-1" :class="getFuelIcon(item.fuel)"></i>
                                <div class="text-xs font-medium text-gray-700">{{ item.fuel }}</div>
                            </div>
                        </div>
                        
                        <div class="mb-4" v-if="item.item_type !== 'car'">
                            <div class="flex flex-wrap gap-1">
                                <span v-if="item.service_type" class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                    {{ item.service_type }}
                                </span>
                                <span v-if="item.level" class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                                    {{ item.level }}
                                </span>
                                <span v-if="item.duration" class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded">
                                    {{ item.duration }}
                                </span>
                                <span v-if="item.category" class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded">
                                    {{ item.category }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center pt-4 border-t border-gray-100 mt-auto">
                            <span class="text-xs text-gray-500">Added {{ formatDate(item.added_at) }}</span>
                            <button 
                                @click="addToCart(item)"
                                :disabled="isLoading || item.quantity === 0"
                                class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 disabled:opacity-50 flex items-center text-sm">
                                <i class="fas fa-shopping-cart mr-2"></i>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div v-if="wishlistItems.length > 0" class="mt-12 flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                <a href="index.php" class="bg-white hover:bg-gray-50 text-gray-800 font-medium py-3 px-6 sm:px-8 border border-gray-300 rounded-lg transition duration-300 text-center shadow-sm flex items-center justify-center">
                    <i class="fas fa-arrow-left mr-2"></i> Continue Shopping
                </a>
                <button 
                    @click="addAllToCart"
                    :disabled="isLoading"
                    class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium py-3 px-6 sm:px-8 rounded-lg transition duration-300 disabled:opacity-50 text-center shadow-lg flex items-center justify-center">
                    <i class="fas fa-cart-plus mr-2"></i> Add All to Cart
                </button>
            </div>
        </section>
    </div>

    <?php include __DIR__ . '/../footer.php'; ?>

    <script>
        // Enhanced toast notification system
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toast-container');
            const toastId = 'toast-' + Date.now();
            
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `toast ${type}`;
            
            const icon = type === 'success' ? 'fa-check-circle' : 
                        type === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle';
            const title = type === 'success' ? 'Success!' : 
                         type === 'error' ? 'Error!' : 'Warning!';
            
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
                    wishlistItems: <?php echo json_encode($wishlist_items); ?>,
                    isLoading: false,
                    userId: <?php echo $user_id ? $user_id : 'null'; ?>,
                },
                methods: {
                    async addToCart(item) {
                        if (!this.userId) {
                            showToast('Please login to add items to cart', 'error');
                            return;
                        }

                        if (item.quantity === 0) {
                            showToast('This item is currently unavailable', 'warning');
                            return;
                        }

                        this.isLoading = true;
                        
                        try {
                            const formData = new FormData();
                            formData.append('add_to_cart_from_wishlist', '1');
                            formData.append('item_id', item.item_id);
                            formData.append('item_type', item.item_type);
                            
                            const response = await fetch('', {
                                method: 'POST',
                                body: formData
                            });
                            
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                showToast(result.message, 'success');
                                // Remove from wishlist immediately after adding to cart
                                this.removeItemFromList(item);
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            showToast('Network error. Please try again.', 'error');
                        } finally {
                            this.isLoading = false;
                        }
                    },
                    
                    async removeFromWishlist(item) {
                        if (!this.userId) {
                            showToast('Please login to manage your wishlist', 'error');
                            return;
                        }
                        
                        this.isLoading = true;
                        
                        try {
                            const formData = new FormData();
                            formData.append('remove_from_wishlist', '1');
                            formData.append('item_id', item.item_id);
                            formData.append('item_type', item.item_type);
                            
                            const response = await fetch('', {
                                method: 'POST',
                                body: formData
                            });
                            
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                this.removeItemFromList(item);
                                showToast(`${item.name} removed from wishlist`, 'success');
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            showToast('Network error. Please try again.', 'error');
                        } finally {
                            this.isLoading = false;
                        }
                    },
                    
                    removeItemFromList(item) {
                        // Immediately remove item from the list without animation
                        const index = this.wishlistItems.findIndex(i => 
                            i.wishlist_id === item.wishlist_id && i.item_type === item.item_type
                        );
                        if (index !== -1) {
                            this.wishlistItems.splice(index, 1);
                        }
                    },
                    
                    async clearAllWishlist() {
                        if (!this.userId) {
                            showToast('Please login to manage your wishlist', 'error');
                            return;
                        }
                        
                        if (this.wishlistItems.length === 0) {
                            showToast('Wishlist is already empty', 'warning');
                            return;
                        }
                        
                        if (!confirm('Are you sure you want to remove all items from your wishlist? This action cannot be undone.')) {
                            return;
                        }
                        
                        this.isLoading = true;
                        
                        try {
                            const formData = new FormData();
                            formData.append('clear_all_wishlist', '1');
                            
                            const response = await fetch('', {
                                method: 'POST',
                                body: formData
                            });
                            
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                // Clear all items immediately
                                this.wishlistItems = [];
                                showToast('All items removed from wishlist', 'success');
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            showToast('Network error. Please try again.', 'error');
                        } finally {
                            this.isLoading = false;
                        }
                    },
                    
                    async addAllToCart() {
                        if (!this.userId) {
                            showToast('Please login to add items to cart', 'error');
                            return;
                        }
                        
                        if (this.wishlistItems.length === 0) {
                            showToast('No items to add to cart', 'warning');
                            return;
                        }
                        
                        this.isLoading = true;
                        let successCount = 0;
                        let errorCount = 0;
                        
                        try {
                            // Create a copy of items to process
                            const itemsToProcess = [...this.wishlistItems];
                            
                            for (const item of itemsToProcess) {
                                if (item.quantity > 0) {
                                    const formData = new FormData();
                                    formData.append('add_to_cart_from_wishlist', '1');
                                    formData.append('item_id', item.item_id);
                                    formData.append('item_type', item.item_type);
                                    
                                    const response = await fetch('', {
                                        method: 'POST',
                                        body: formData
                                    });
                                    
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    
                                    const result = await response.json();
                                    
                                    if (result.success) {
                                        successCount++;
                                        // Remove from current list immediately
                                        this.removeItemFromList(item);
                                    } else {
                                        errorCount++;
                                    }
                                } else {
                                    errorCount++;
                                }
                            }
                            
                            if (successCount > 0) {
                                showToast(`${successCount} items added to cart${errorCount > 0 ? `, ${errorCount} unavailable items skipped` : ''}`, 'success');
                            } else {
                                showToast('No available items could be added to cart', 'warning');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            showToast('Network error. Please try again.', 'error');
                        } finally {
                            this.isLoading = false;
                        }
                    },
                    
                    formatDate(dateString) {
                        const date = new Date(dateString);
                        const now = new Date();
                        const diffTime = Math.abs(now - date);
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        
                        if (diffDays === 1) {
                            return 'yesterday';
                        } else if (diffDays < 7) {
                            return `${diffDays} days ago`;
                        } else if (diffDays < 30) {
                            const weeks = Math.floor(diffDays / 7);
                            return `${weeks} ${weeks === 1 ? 'week' : 'weeks'} ago`;
                        } else {
                            return date.toLocaleDateString();
                        }
                    },
                    
                    formatPrice(price) {
                        return parseFloat(price).toFixed(2);
                    },
                    
                    getAvailabilityClass(quantity) {
                        if (quantity === 0) {
                            return 'availability-unavailable';
                        } else if (quantity <= 2) {
                            return 'availability-low';
                        } else {
                            return 'availability-available';
                        }
                    },
                    
                    getAvailabilityText(quantity) {
                        if (quantity === 0) {
                            return 'Unavailable';
                        } else if (quantity <= 2) {
                            return 'Low Stock';
                        } else {
                            return 'Available';
                        }
                    },
                    
                    getFuelIcon(fuelType) {
                        if (!fuelType) return 'fa-gas-pump text-gray-500';
                        
                        switch(fuelType.toLowerCase()) {
                            case 'electric':
                                return 'fa-bolt text-green-500';
                            case 'hybrid':
                                return 'fa-leaf text-green-400';
                            case 'diesel':
                                return 'fa-oil-can text-gray-600';
                            case 'petrol':
                                return 'fa-gas-pump text-orange-500';
                            case 'cng':
                                return 'fa-fire text-blue-500';
                            default:
                                return 'fa-gas-pump text-gray-500';
                        }
                    },
                    
                    getItemTypeLabel(itemType) {
                        switch(itemType) {
                            case 'car':
                                return 'Car';
                            case 'service':
                                return 'Service';
                            case 'part':
                                return 'Part';
                            case 'driving_course':
                                return 'Course';
                            default:
                                return 'Item';
                        }
                    },
                    
                    getItemDetails(item) {
                        switch(item.item_type) {
                            case 'car':
                                return `${item.brand || 'Car'} • ${item.model_year || ''}`;
                            case 'service':
                                return `Service • ${item.service_type || ''}`;
                            case 'part':
                                return `Part • ${item.category || ''}`;
                            case 'driving_course':
                                return `Course • ${item.level || ''}`;
                            default:
                                return '';
                        }
                    },
                    
                    handleImageError(event, itemType) {
                        const defaultImages = {
                            'car': 'https://images.unsplash.com/photo-1549399542-7e82138d0dca?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
                            'service': 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                            'part': 'https://images.unsplash.com/photo-1603712607225-eeb0233846c9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                            'driving_course': 'https://images.unsplash.com/photo-1565896314091-4d4bf5bf4a70?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'
                        };
                        
                        event.target.src = defaultImages[itemType] || defaultImages.car;
                    }
                },
                
                mounted() {
                    if (!this.userId) {
                        showToast('Please login to view your wishlist', 'error');
                    }
                    
                    console.log('Wishlist items loaded:', this.wishlistItems.length);
                }
            });
        });
    </script>
</body>
</html>