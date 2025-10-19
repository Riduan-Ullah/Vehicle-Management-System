<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

// DEV: show errors while debugging (remove or disable on production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$error = '';
$success = '';

// Initialize cart data
$cartItems = [];
$cartSummary = [
    'total_items' => 0,
    'subtotal' => 0.0,
    'grand_total' => 0.0
];

// Service locations
$serviceLocations = [
    "Dhaka Service Center",
    "Chittagong Service Center",
    "Sylhet Service Center",
    "Khulna Service Center"
];

try {
    // Get or create cart for user
    $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create new cart for user
        $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_id = $conn->insert_id;
    } else {
        $cart = $result->fetch_assoc();
        $cart_id = $cart['id'];
    }
    $stmt->close();

    // Handle AJAX requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');

        // Update cart item
        if (isset($_POST['update_cart_item'])) {
            $item_id = intval($_POST['item_id']);
            $quantity = intval($_POST['quantity']) ?: 1;
            $rental_start = !empty($_POST['rental_start']) ? $_POST['rental_start'] : null;
            $rental_end = !empty($_POST['rental_end']) ? $_POST['rental_end'] : null;
            $service_location = !empty($_POST['service_location']) ? $_POST['service_location'] : null;

            $response = ['success' => false, 'message' => '', 'new_total' => 0];

            try {
                // Prepare meta data if service location is provided
                $meta_data = null;
                if ($service_location) {
                    $meta_data = json_encode(['service_location' => $service_location]);
                }

                // Update cart item - include rental dates if provided
                if ($rental_start !== null && $rental_end !== null) {
                    $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, rental_start = ?, rental_end = ?, meta = ? WHERE id = ? AND cart_id = ?");
                    $update_stmt->bind_param("isssii", $quantity, $rental_start, $rental_end, $meta_data, $item_id, $cart_id);
                } else {
                    $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, meta = ? WHERE id = ? AND cart_id = ?");
                    $update_stmt->bind_param("isii", $quantity, $meta_data, $item_id, $cart_id);
                }

                if (!$update_stmt->execute()) {
                    $response['message'] = "Error updating item: " . $update_stmt->error;
                    echo json_encode($response);
                    exit;
                }

                // Get fresh item row to calculate new total
                $calc_stmt = $conn->prepare("SELECT unit_price, item_type, rental_start, rental_end, quantity FROM cart_items WHERE id = ?");
                $calc_stmt->bind_param("i", $item_id);
                $calc_stmt->execute();
                $calc_result = $calc_stmt->get_result();

                if ($calc_row = $calc_result->fetch_assoc()) {
                    $item_total = floatval($calc_row['unit_price']) * intval($quantity);

                    // For cars, calculate based on rental days (if rental dates present)
                    if ($calc_row['item_type'] === 'car' && $calc_row['rental_start'] && $calc_row['rental_end']) {
                        $start = new DateTime($calc_row['rental_start']);
                        $end = new DateTime($calc_row['rental_end']);
                        $days = $end->diff($start)->days;
                        if ($days == 0) $days = 1;
                        $item_total = floatval($calc_row['unit_price']) * $days * intval($quantity);
                    }

                    $response['success'] = true;
                    $response['message'] = "Item updated successfully";
                    $response['new_total'] = $item_total;
                } else {
                    $response['message'] = "Item not found after update";
                }

                $calc_stmt->close();
                $update_stmt->close();
            } catch (Exception $e) {
                $response['message'] = "Database error: " . $e->getMessage();
            }

            echo json_encode($response);
            exit;
        }

        // AJAX request to calculate total
        if (isset($_POST['calculate_total'])) {
            $response = ['subtotal' => 0.0, 'total_items' => 0];

            $calc_stmt = $conn->prepare("
                SELECT ci.unit_price, ci.quantity, ci.item_type, ci.rental_start, ci.rental_end 
                FROM cart_items ci 
                WHERE ci.cart_id = ?
            ");
            $calc_stmt->bind_param("i", $cart_id);
            $calc_stmt->execute();
            $calc_result = $calc_stmt->get_result();

            while ($item = $calc_result->fetch_assoc()) {
                $item_total = floatval($item['unit_price']) * intval($item['quantity']);

                // For cars, calculate based on rental days
                if ($item['item_type'] === 'car' && $item['rental_start'] && $item['rental_end']) {
                    $start = new DateTime($item['rental_start']);
                    $end = new DateTime($item['rental_end']);
                    $days = $end->diff($start)->days;
                    if ($days == 0) $days = 1;
                    $item_total = floatval($item['unit_price']) * $days * intval($item['quantity']);
                }

                $response['subtotal'] += $item_total;
                $response['total_items'] += intval($item['quantity']);
            }
            $calc_stmt->close();

            echo json_encode($response);
            exit;
        }

        // Unknown AJAX action
        echo json_encode(['success' => false, 'message' => 'Unknown AJAX action']);
        exit;
    }

    // Handle regular form submissions (non-AJAX)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
        if (isset($_POST['remove_item'])) {
            $item_id = intval($_POST['item_id']);

            $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
            $stmt->bind_param("ii", $item_id, $cart_id);
            if ($stmt->execute()) {
                $success = "Item removed from cart";
            } else {
                $error = "Error removing item: " . $stmt->error;
            }
            $stmt->close();
        }
        elseif (isset($_POST['clear_cart'])) {
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->bind_param("i", $cart_id);
            if ($stmt->execute()) {
                $success = "Cart cleared successfully";
            } else {
                $error = "Error clearing cart: " . $stmt->error;
            }
            $stmt->close();
        }
        // ---------- CHECKOUT 
        elseif (isset($_POST['checkout'])) {
            $customer_name = trim($_POST['customer_name'] ?? '');
            $customer_email = trim($_POST['customer_email'] ?? '');
            $customer_phone = trim($_POST['customer_phone'] ?? '');
            $customer_address = trim($_POST['customer_address'] ?? '');

            if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($customer_address)) {
                $error = "All customer information fields are required.";
            } else {
                $conn->begin_transaction();
                try {
                    // 1) Recalculate fresh subtotal & gather cart rows
                    $calc_stmt = $conn->prepare("
                        SELECT id, item_id, item_type, unit_price, quantity, rental_start, rental_end, meta
                        FROM cart_items
                        WHERE cart_id = ?
                    ");
                    $calc_stmt->bind_param("i", $cart_id);
                    $calc_stmt->execute();
                    $calc_res = $calc_stmt->get_result();

                    $freshSubtotal = 0.0;
                    $cartRows = [];
                    while ($crow = $calc_res->fetch_assoc()) {
                        $cartRows[] = $crow;

                        $item_total = floatval($crow['unit_price']) * intval($crow['quantity']);

                        if ($crow['item_type'] === 'car' && $crow['rental_start'] && $crow['rental_end']) {
                            $start = new DateTime($crow['rental_start']);
                            $end = new DateTime($crow['rental_end']);
                            $days = $end->diff($start)->days;
                            if ($days == 0) $days = 1;
                            $item_total = floatval($crow['unit_price']) * $days * intval($crow['quantity']);
                        }

                        $freshSubtotal += $item_total;
                    }
                    $calc_stmt->close();

                    // Only add delivery charge if there are items that need delivery
                    $delivery_charge = 0;
                    $has_physical_items = false;
                    foreach ($cartRows as $row) {
                        if ($row['item_type'] === 'car' || $row['item_type'] === 'part') {
                            $has_physical_items = true;
                            break;
                        }
                    }
                    
                    if ($has_physical_items) {
                        $delivery_charge = 60;
                    }
                    
                    $freshGrandTotal = $freshSubtotal + $delivery_charge;

                    // 2) Determine payment column name in orders table
                    $colCheck = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'paid_status'");
                    if ($colCheck && $colCheck->num_rows > 0) {
                        $statusCol = 'paid_status';
                    } else {
                        $statusCol = 'payment_status';
                    }

                    // 3) Insert order with status = 'paid'
                    $order_number = 'ORD-' . date('Ymd-His') . '-' . rand(100, 999);
                    $order_sql = "INSERT INTO orders (order_number, subtotal, total_amount, {$statusCol}, customer_name, customer_email, customer_phone, customer_address, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $order_stmt = $conn->prepare($order_sql);
                    if (!$order_stmt) throw new Exception("Prepare order failed: " . $conn->error);

                    $paidValue = 'paid';
                    $order_stmt->bind_param("sddsssssi", $order_number, $freshSubtotal, $freshGrandTotal, $paidValue, $customer_name, $customer_email, $customer_phone, $customer_address, $user_id);

                    if (!$order_stmt->execute()) throw new Exception("Failed to create order: " . $order_stmt->error);
                    $order_id = $conn->insert_id;
                    $order_stmt->close();

                    // 4) Insert each cart row into order_items (snapshot)
                    $order_item_stmt = $conn->prepare("
                        INSERT INTO order_items 
                        (order_id, item_type, item_id, item_name, quantity, unit_price, total_price, rental_start, rental_end, location, specifications)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    if (!$order_item_stmt) throw new Exception("Prepare order_items failed: " . $conn->error);

                    foreach ($cartRows as $item) {
                        $item_type = $item['item_type'];
                        $orig_item_id = intval($item['item_id']);
                        $item_name = '';
                        $item_desc = '';
                        $item_spec = '';

                        // Lookup name/spec from source table depending on type
                        if ($item_type === 'car') {
                            $q = $conn->prepare("SELECT name, description, seats, transmission, fuel_type FROM cars WHERE id = ?");
                            $q->bind_param("i", $orig_item_id);
                            $q->execute();
                            $r = $q->get_result()->fetch_assoc();
                            if ($r) {
                                $item_name = $r['name'] ?? '';
                                $item_desc = $r['description'] ?? '';
                                $item_spec = ((isset($r['seats']) && $r['seats']) ? ($r['seats'] . ' Seats • ') : '') . ($r['transmission'] ?? '') . (isset($r['fuel_type']) && $r['fuel_type'] ? ' • ' . $r['fuel_type'] : '');
                            }
                            if (isset($q)) $q->close();
                        } elseif ($item_type === 'service') {
                            $q = $conn->prepare("SELECT name, description, service_type FROM services WHERE sid = ?");
                            $q->bind_param("i", $orig_item_id);
                            $q->execute();
                            $r = $q->get_result()->fetch_assoc();
                            if ($r) {
                                $item_name = $r['name'] ?? '';
                                $item_desc = $r['description'] ?? '';
                                $item_spec = $r['service_type'] ?? '';
                            }
                            if (isset($q)) $q->close();
                        } elseif ($item_type === 'part') {
                            $q = $conn->prepare("SELECT name, description, category, sku FROM parts WHERE id = ?");
                            $q->bind_param("i", $orig_item_id);
                            $q->execute();
                            $r = $q->get_result()->fetch_assoc();
                            if ($r) {
                                $item_name = $r['name'] ?? '';
                                $item_desc = $r['description'] ?? '';
                                $item_spec = (isset($r['category']) ? $r['category'] . ' • ' : '') . (isset($r['sku']) ? 'SKU: ' . $r['sku'] : '');
                            }
                            if (isset($q)) $q->close();
                        } elseif ($item_type === 'course') {
                            $q = $conn->prepare("SELECT name, description, level, duration, vehicle_type FROM driving_course WHERE dcid = ?");
                            $q->bind_param("i", $orig_item_id);
                            $q->execute();
                            $r = $q->get_result()->fetch_assoc();
                            if ($r) {
                                $item_name = $r['name'] ?? '';
                                $item_desc = $r['description'] ?? '';
                                $item_spec = (($r['level'] ?? '') . ' • ' . ($r['duration'] ?? '') . ' • ' . ($r['vehicle_type'] ?? ''));
                            }
                            if (isset($q)) $q->close();
                        }

                        $qty = intval($item['quantity']);
                        $unit_price = floatval($item['unit_price']);
                        $item_total_price = $unit_price * $qty;

                        $rental_start = !empty($item['rental_start']) ? $item['rental_start'] : null;
                        $rental_end = !empty($item['rental_end']) ? $item['rental_end'] : null;
                        $location = null;

                        if (!empty($item['meta'])) {
                            $metaArr = json_decode($item['meta'], true);
                            if (is_array($metaArr)) {
                                $location = $metaArr['service_location'] ?? null;
                            }
                        }

                        if ($item_type === 'car' && $rental_start && $rental_end) {
                            $start = new DateTime($rental_start);
                            $end = new DateTime($rental_end);
                            $days = $end->diff($start)->days;
                            if ($days == 0) $days = 1;
                            $item_total_price = $unit_price * $days * $qty;
                        }

                        $specifications = json_encode([
                            'description' => $item_desc,
                            'spec' => $item_spec,
                            'original_item_id' => $orig_item_id,
                            'meta' => $item['meta'] ? json_decode($item['meta'], true) : new stdClass(),
                            'captured_at' => date('Y-m-d H:i:s')
                        ]);

                        // Bind & execute insert into order_items
                        $order_item_stmt->bind_param(
                            "isisiddssss",
                            $order_id,
                            $item_type,
                            $orig_item_id,
                            $item_name,
                            $qty,
                            $unit_price,
                            $item_total_price,
                            $rental_start,
                            $rental_end,
                            $location,
                            $specifications
                        );

                        if (!$order_item_stmt->execute()) {
                            throw new Exception("Failed to insert order item (item_id: {$orig_item_id}): " . $order_item_stmt->error);
                        }
                    }

                    $order_item_stmt->close();

                    // 5) Clear cart_items
                    $clear_cart_stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
                    $clear_cart_stmt->bind_param("i", $cart_id);
                    if (!$clear_cart_stmt->execute()) {
                        throw new Exception("Failed to clear cart: " . $clear_cart_stmt->error);
                    }
                    $clear_cart_stmt->close();

                    $conn->commit();

                    // Set simple success message and redirect to avoid form resubmission
                    $_SESSION['order_success'] = "Order placed successfully! Thank you for your purchase.";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Checkout failed: " . $e->getMessage();
                }
            }
        }
    }

    // Fetch cart items with joined details for display
    $query = "
        SELECT 
            ci.id,
            ci.item_type,
            ci.item_id,
            ci.unit_price,
            ci.quantity,
            ci.rental_start,
            ci.rental_end,
            ci.meta,
            ci.created_at,
            CASE 
                WHEN ci.item_type = 'car' THEN c.name
                WHEN ci.item_type = 'service' THEN s.name
                WHEN ci.item_type = 'part' THEN p.name
                WHEN ci.item_type = 'course' THEN dc.name
            END as item_name,
            CASE 
                WHEN ci.item_type = 'car' THEN c.description
                WHEN ci.item_type = 'service' THEN s.description
                WHEN ci.item_type = 'part' THEN p.description
                WHEN ci.item_type = 'course' THEN dc.description
            END as item_description,
            CASE 
                WHEN ci.item_type = 'car' THEN CONCAT(c.seats, ' Seats • ', c.transmission, ' • ', c.fuel_type)
                WHEN ci.item_type = 'service' THEN s.service_type
                WHEN ci.item_type = 'part' THEN CONCAT(p.category, ' • SKU: ', p.sku)
                WHEN ci.item_type = 'course' THEN CONCAT(dc.level, ' • ', dc.duration, ' • ', dc.vehicle_type)
            END as item_spec
        FROM cart_items ci
        LEFT JOIN cars c ON ci.item_type = 'car' AND ci.item_id = c.id
        LEFT JOIN services s ON ci.item_type = 'service' AND ci.item_id = s.sid
        LEFT JOIN parts p ON ci.item_type = 'part' AND ci.item_id = p.id
        LEFT JOIN driving_course dc ON ci.item_type = 'course' AND ci.item_id = dc.dcid
        WHERE ci.cart_id = ?
        ORDER BY ci.item_type, ci.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $meta = $row['meta'] ? json_decode($row['meta'], true) : [];

        // Calculate days for car rentals
        $days = 0;
        if ($row['item_type'] === 'car' && $row['rental_start'] && $row['rental_end']) {
            $start = new DateTime($row['rental_start']);
            $end = new DateTime($row['rental_end']);
            $days = $end->diff($start)->days;
            if ($days == 0) $days = 1;
        }

        $subtotal = floatval($row['unit_price']) * intval($row['quantity']);
        if ($row['item_type'] === 'car' && $days > 0) {
            $subtotal = floatval($row['unit_price']) * $days * intval($row['quantity']);
        }

        $cartItems[] = [
            'id' => $row['id'],
            'item_id' => $row['item_id'],
            'type' => $row['item_type'],
            'name' => $row['item_name'] ?? '',
            'description' => $row['item_description'] ?? '',
            'spec' => $row['item_spec'] ?? '',
            'unit_price' => floatval($row['unit_price']),
            'quantity' => intval($row['quantity']),
            'rental_start' => $row['rental_start'],
            'rental_end' => $row['rental_end'],
            'days' => $days,
            'meta' => $meta,
            'subtotal' => $subtotal
        ];
    }
    $stmt->close();

    // Calculate totals and check if delivery charge is needed
    $has_physical_items = false;
    foreach ($cartItems as $item) {
        $cartSummary['subtotal'] += $item['subtotal'];
        $cartSummary['total_items'] += $item['quantity'];
        
        // Check if item requires delivery (cars or parts)
        if ($item['type'] === 'car' || $item['type'] === 'part') {
            $has_physical_items = true;
        }
    }
    
    // Only add delivery charge if there are physical items
    $delivery_charge = $has_physical_items ? 60 : 0;
    $cartSummary['grand_total'] = $cartSummary['subtotal'] + $delivery_charge;

} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Check for order success message from session
if (isset($_SESSION['order_success'])) {
    $success = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
}

// Group items by type
$groupedItems = [
    'car' => [],
    'service' => [],
    'part' => [],
    'course' => []
];

foreach ($cartItems as $item) {
    $groupedItems[$item['type']][] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Vehicle Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        
        .glass-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }
        
        .item-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        
        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-car { background: #dbeafe; color: #1e40af; }
        .badge-service { background: #dcfce7; color: #166534; }
        .badge-part { background: #fef3c7; color: #92400e; }
        .badge-course { background: #f3e8ff; color: #7e22ce; }
        
        .quantity-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .quantity-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .input-compact {
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            width: 100%;
            transition: all 0.2s;
        }
        
        .input-compact:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .btn-danger:hover {
            background: #dc2626;
            transform: scale(1.05);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .date-input-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .total-calculation {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Checkout Modal Styles */
        #checkout-modal .modal-content {
            max-width: 500px;
            width: 95%;
        }

        .shake-animation {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .success-message {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            margin: 1rem 0;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">
    <nav class="fixed top-0 left-0 w-full z-50 bg-white shadow">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        include __DIR__ . '/../navbar.php';
        ?>
    </nav>

    <!-- Checkout Modal -->
    <div id="checkout-modal" class="modal-overlay hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-xl shadow-lg w-full max-w-md max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-600 p-4 text-white">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold">Checkout</h3>
                    <button onclick="closeCheckoutModal()" class="text-white hover:text-blue-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Form Content -->
            <div class="p-4 overflow-y-auto max-h-[70vh]">
                <form id="checkout-form" method="POST">
                    <div class="space-y-4">
                        <!-- Contact Info -->
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3 text-sm">Contact Information</h4>
                            <div class="space-y-3">
                                <div>
                                    <input type="text" name="customer_name" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                           placeholder="Full Name" 
                                           value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>">
                                    <div class="text-xs text-red-500 mt-1 hidden" id="name-error"></div>
                                </div>
                                
                                <div>
                                    <input type="email" name="customer_email" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                           placeholder="Email" 
                                           value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>">
                                    <div class="text-xs text-red-500 mt-1 hidden" id="email-error"></div>
                                </div>

                                <div>
                                    <input type="tel" name="customer_phone" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                           placeholder="Phone (01XXXXXXXXX)"
                                           maxlength="11">
                                    <div class="text-xs text-red-500 mt-1 hidden" id="phone-error"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Address -->
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3 text-sm">Delivery Address</h4>
                            <textarea name="customer_address" required rows="2" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"
                                      placeholder="Full address including area and city"></textarea>
                            <div class="text-xs text-red-500 mt-1 hidden" id="address-error"></div>
                        </div>

                        <!-- Order Summary -->
                        <div class="bg-gray-50 rounded-lg p-3 border">
                            <h4 class="font-medium text-gray-900 mb-2 text-sm">Order Summary</h4>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-medium" id="modal-subtotal">৳0.00</span>
                                </div>
                                <div class="flex justify-between" id="delivery-row">
                                    <span class="text-gray-600">Delivery:</span>
                                    <span class="font-medium">৳0.00</span>
                                </div>
                                <div class="flex justify-between border-t pt-1 mt-1">
                                    <span class="font-bold">Total:</span>
                                    <span class="font-bold text-blue-600" id="modal-total">৳0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex gap-2 mt-6">
                        <button type="button" onclick="closeCheckoutModal()" 
                                class="flex-1 py-2 px-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                            Cancel
                        </button>
                        <button type="submit" name="checkout" id="submit-order-btn"
                                class="flex-1 py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                            Confirm Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="success-toast" class="fixed top-4 right-4 z-50 px-6 py-3 rounded-lg text-white font-medium bg-green-500 hidden transition-all duration-300 transform translate-x-full">
        <i class="fas fa-check-circle mr-2"></i>
        <span id="success-message"></span>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 pt-24">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Shopping Cart</h1>
            <p class="text-gray-600">Review and manage your selected items</p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message mb-6 flex items-center justify-center">
                <i class="fas fa-check-circle mr-2 text-xl"></i>
                <span class="text-lg font-semibold"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2 space-y-6">
                <?php if (empty($cartItems)): ?>
                    <!-- Empty Cart -->
                    <div class="glass-card empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3 class="text-xl font-semibold mb-2">Your cart is empty</h3>
                        <p class="text-gray-500 mb-6">Start adding vehicles, services, or parts to your cart</p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="available_car.php" class="btn-primary">
                                <i class="fas fa-car mr-2"></i>Browse Vehicles
                            </a>
                            <a href="services.php" class="btn-secondary">
                                <i class="fas fa-tools mr-2"></i>View Services
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Cart Actions -->
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            <span id="total-items-count"><?php echo $cartSummary['total_items']; ?></span> item(s) in cart
                        </div>
                        <form method="POST">
                            <button type="submit" name="clear_cart" class="btn-danger" onclick="return confirm('Are you sure you want to clear your entire cart?')">
                                <i class="fas fa-trash mr-1"></i>Clear Cart
                            </button>
                        </form>
                    </div>

                    <!-- Cars Section -->
                    <?php if (!empty($groupedItems['car'])): ?>
                        <div class="glass-card p-6 fade-in">
                            <div class="section-title flex items-center">
                                <i class="fas fa-car mr-3 text-blue-500"></i>
                                Vehicle Rentals
                                <span class="ml-2 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                    <?php echo count($groupedItems['car']); ?>
                                </span>
                            </div>
                            <div class="space-y-4">
                                <?php foreach ($groupedItems['car'] as $item): ?>
                                    <div class="item-card p-4" id="cart-item-<?php echo $item['id']; ?>">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <h4 class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($item['name']); ?></h4>
                                                    <span class="badge badge-car">Vehicle</span>
                                                </div>
                                                <?php if ($item['description']): ?>
                                                    <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                                <?php endif; ?>
                                                <div class="text-sm text-gray-500 mb-2">
                                                    <?php echo htmlspecialchars($item['spec']); ?>
                                                </div>
                                                <div class="text-green-600 font-semibold">
                                                    <i class="fas fa-tag mr-1"></i>৳<?php echo number_format($item['unit_price'], 2); ?>/day
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xl font-bold text-green-600 mb-1" id="item-total-<?php echo $item['id']; ?>">
                                                    ৳<?php echo number_format($item['subtotal'], 2); ?>
                                                </div>
                                                <div class="text-sm text-gray-500 mb-2" id="item-days-<?php echo $item['id']; ?>">
                                                    <?php if ($item['days'] > 0): ?>
                                                        <?php echo $item['days']; ?> day<?php echo $item['days'] > 1 ? 's' : ''; ?>
                                                    <?php else: ?>
                                                        Set rental dates
                                                    <?php endif; ?>
                                                </div>
                                                <form method="POST" class="inline-block">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="remove_item" class="btn-danger" onclick="return confirm('Remove this vehicle from cart?')">
                                                        <i class="fas fa-trash mr-1"></i>Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Car Controls -->
                                        <div class="flex items-end gap-4">
                                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-2">RENTAL START</label>
                                                    <input type="date" 
                                                           class="input-compact rental-date" 
                                                           data-item-id="<?php echo $item['id']; ?>"
                                                           data-item-type="car"
                                                           value="<?php echo $item['rental_start'] ?: ''; ?>"
                                                           min="<?php echo date('Y-m-d'); ?>">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-2">RENTAL END</label>
                                                    <input type="date" 
                                                           class="input-compact rental-date" 
                                                           data-item-id="<?php echo $item['id']; ?>"
                                                           data-item-type="car"
                                                           value="<?php echo $item['rental_end'] ?: ''; ?>"
                                                           min="<?php echo date('Y-m-d'); ?>">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-2">QUANTITY</label>
                                                    <div class="flex items-center gap-2">
                                                        <button type="button" class="quantity-btn decrease" data-item-id="<?php echo $item['id']; ?>" data-item-type="car">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <input type="number" 
                                                               class="input-compact text-center quantity-input" 
                                                               data-item-id="<?php echo $item['id']; ?>"
                                                               data-item-type="car"
                                                               value="<?php echo $item['quantity']; ?>" 
                                                               min="1" 
                                                               style="width: 80px;">
                                                        <button type="button" class="quantity-btn increase" data-item-id="<?php echo $item['id']; ?>" data-item-type="car">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="total-calculation" id="calculation-<?php echo $item['id']; ?>">
                                            <?php if ($item['days'] > 0): ?>
                                                ৳<?php echo number_format($item['unit_price'], 2); ?> × <?php echo $item['days']; ?> days × <?php echo $item['quantity']; ?> = ৳<?php echo number_format($item['subtotal'], 2); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Services Section -->
                    <?php if (!empty($groupedItems['service'])): ?>
                        <div class="glass-card p-6 fade-in">
                            <div class="section-title flex items-center">
                                <i class="fas fa-tools mr-3 text-green-500"></i>
                                Services
                                <span class="ml-2 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                    <?php echo count($groupedItems['service']); ?>
                                </span>
                            </div>
                            <div class="space-y-4">
                                <?php foreach ($groupedItems['service'] as $item): ?>
                                    <div class="item-card p-4" id="cart-item-<?php echo $item['id']; ?>">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <h4 class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($item['name']); ?></h4>
                                                    <span class="badge badge-service">Service</span>
                                                </div>
                                                <?php if ($item['description']): ?>
                                                    <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                                <?php endif; ?>
                                                <div class="text-sm text-gray-500 mb-2">
                                                    <?php echo htmlspecialchars($item['spec']); ?>
                                                </div>
                                                <div class="text-green-600 font-semibold">
                                                    <i class="fas fa-tag mr-1"></i>৳<?php echo number_format($item['unit_price'], 2); ?>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xl font-bold text-green-600 mb-1" id="item-total-<?php echo $item['id']; ?>">
                                                    ৳<?php echo number_format($item['subtotal'], 2); ?>
                                                </div>
                                                <form method="POST" class="inline-block">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="remove_item" class="btn-danger" onclick="return confirm('Remove this service from cart?')">
                                                        <i class="fas fa-trash mr-1"></i>Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Service Controls -->
                                        <div class="flex items-end gap-4">
                                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-2">SERVICE LOCATION</label>
                                                    <select class="input-compact service-location" data-item-id="<?php echo $item['id']; ?>" data-item-type="service">
                                                        <option value="">Select location</option>
                                                        <?php foreach ($serviceLocations as $location): ?>
                                                            <option value="<?php echo htmlspecialchars($location); ?>" 
                                                                <?php echo (isset($item['meta']['service_location']) && $item['meta']['service_location'] === $location) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($location); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-2">QUANTITY</label>
                                                    <div class="flex items-center gap-2">
                                                        <button type="button" class="quantity-btn decrease" data-item-id="<?php echo $item['id']; ?>" data-item-type="service">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <input type="number" 
                                                               class="input-compact text-center quantity-input" 
                                                               data-item-id="<?php echo $item['id']; ?>"
                                                               data-item-type="service"
                                                               value="<?php echo $item['quantity']; ?>" 
                                                               min="1" 
                                                               style="width: 80px;">
                                                        <button type="button" class="quantity-btn increase" data-item-id="<?php echo $item['id']; ?>" data-item-type="service">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="total-calculation" id="calculation-<?php echo $item['id']; ?>">
                                            ৳<?php echo number_format($item['unit_price'], 2); ?> × <?php echo $item['quantity']; ?> = ৳<?php echo number_format($item['subtotal'], 2); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Parts Section -->
                    <?php if (!empty($groupedItems['part'])): ?>
                        <div class="glass-card p-6 fade-in">
                            <div class="section-title flex items-center">
                                <i class="fas fa-cog mr-3 text-yellow-500"></i>
                                Parts & Accessories
                                <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">
                                    <?php echo count($groupedItems['part']); ?>
                                </span>
                            </div>
                            <div class="space-y-4">
                                <?php foreach ($groupedItems['part'] as $item): ?>
                                    <div class="item-card p-4" id="cart-item-<?php echo $item['id']; ?>">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <h4 class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($item['name']); ?></h4>
                                                    <span class="badge badge-part">Part</span>
                                                </div>
                                                <?php if ($item['description']): ?>
                                                    <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                                <?php endif; ?>
                                                <div class="text-sm text-gray-500 mb-2">
                                                    <?php echo htmlspecialchars($item['spec']); ?>
                                                </div>
                                                <div class="text-green-600 font-semibold">
                                                    <i class="fas fa-tag mr-1"></i>৳<?php echo number_format($item['unit_price'], 2); ?>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xl font-bold text-green-600 mb-1" id="item-total-<?php echo $item['id']; ?>">
                                                    ৳<?php echo number_format($item['subtotal'], 2); ?>
                                                </div>
                                                <form method="POST" class="inline-block">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="remove_item" class="btn-danger" onclick="return confirm('Remove this part from cart?')">
                                                        <i class="fas fa-trash mr-1"></i>Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Part Controls -->
                                        <div class="flex items-end gap-4">
                                            <div class="flex-1">
                                                <label class="block text-xs font-medium text-gray-700 mb-2">QUANTITY</label>
                                                <div class="flex items-center gap-2" style="width: fit-content;">
                                                    <button type="button" class="quantity-btn decrease" data-item-id="<?php echo $item['id']; ?>" data-item-type="part">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" 
                                                           class="input-compact text-center quantity-input" 
                                                           data-item-id="<?php echo $item['id']; ?>"
                                                           data-item-type="part"
                                                           value="<?php echo $item['quantity']; ?>" 
                                                           min="1" 
                                                           style="width: 80px;">
                                                    <button type="button" class="quantity-btn increase" data-item-id="<?php echo $item['id']; ?>" data-item-type="part">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="total-calculation" id="calculation-<?php echo $item['id']; ?>">
                                            ৳<?php echo number_format($item['unit_price'], 2); ?> × <?php echo $item['quantity']; ?> = ৳<?php echo number_format($item['subtotal'], 2); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Courses Section -->
                    <?php if (!empty($groupedItems['course'])): ?>
                        <div class="glass-card p-6 fade-in">
                            <div class="section-title flex items-center">
                                <i class="fas fa-graduation-cap mr-3 text-purple-500"></i>
                                Driving Courses
                                <span class="ml-2 bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full">
                                    <?php echo count($groupedItems['course']); ?>
                                </span>
                            </div>
                            <div class="space-y-4">
                                <?php foreach ($groupedItems['course'] as $item): ?>
                                    <div class="item-card p-4" id="cart-item-<?php echo $item['id']; ?>">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <h4 class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($item['name']); ?></h4>
                                                    <span class="badge badge-course">Course</span>
                                                </div>
                                                <?php if ($item['description']): ?>
                                                    <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                                <?php endif; ?>
                                                <div class="text-sm text-gray-500 mb-2">
                                                    <?php echo htmlspecialchars($item['spec']); ?>
                                                </div>
                                                <div class="text-green-600 font-semibold">
                                                    <i class="fas fa-tag mr-1"></i>৳<?php echo number_format($item['unit_price'], 2); ?>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xl font-bold text-green-600 mb-1" id="item-total-<?php echo $item['id']; ?>">
                                                    ৳<?php echo number_format($item['subtotal'], 2); ?>
                                                </div>
                                                <form method="POST" class="inline-block">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="remove_item" class="btn-danger" onclick="return confirm('Remove this course from cart?')">
                                                        <i class="fas fa-trash mr-1"></i>Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Course Controls -->
                                        <div class="flex items-end gap-4">
                                            <div class="flex-1">
                                                <label class="block text-xs font-medium text-gray-700 mb-2">QUANTITY</label>
                                                <div class="flex items-center gap-2" style="width: fit-content;">
                                                    <button type="button" class="quantity-btn decrease" data-item-id="<?php echo $item['id']; ?>" data-item-type="course">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" 
                                                           class="input-compact text-center quantity-input" 
                                                           data-item-id="<?php echo $item['id']; ?>"
                                                           data-item-type="course"
                                                           value="<?php echo $item['quantity']; ?>" 
                                                           min="1" 
                                                           style="width: 80px;">
                                                    <button type="button" class="quantity-btn increase" data-item-id="<?php echo $item['id']; ?>" data-item-type="course">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="total-calculation" id="calculation-<?php echo $item['id']; ?>">
                                            ৳<?php echo number_format($item['unit_price'], 2); ?> × <?php echo $item['quantity']; ?> = ৳<?php echo number_format($item['subtotal'], 2); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="glass-card p-6 sticky top-24">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Order Summary</h3>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Items (<span id="summary-total-items"><?php echo $cartSummary['total_items']; ?></span>)</span>
                            <span class="font-medium" id="summary-subtotal">৳<?php echo number_format($cartSummary['subtotal'], 2); ?></span>
                        </div>
                        <?php if ($delivery_charge > 0): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Delivery Charge:</span>
                            <span class="font-medium">৳<?php echo number_format($delivery_charge, 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="border-t pt-3">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total Amount</span>
                                <span class="text-green-600" id="summary-total">৳<?php echo number_format($cartSummary['grand_total'], 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($cartItems)): ?>
                        <button onclick="openCheckoutModal()" class="btn-primary w-full py-3 text-lg mb-4">
                            <i class="fas fa-lock mr-2"></i>
                            Proceed to Checkout
                        </button>
                        
                        <div class="text-center text-sm text-gray-600 mb-6">
                            <p><i class="fas fa-shield-alt mr-1 text-green-500"></i>Secure payment · Instant confirmation</p>
                        </div>
                    <?php endif; ?>

                    <!-- Quick Stats -->
                    <div class="stats-card">
                        <h4 class="font-semibold mb-3">Cart Summary</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-blue-200">Total Items</div>
                                <div class="text-white font-semibold" id="stats-total-items"><?php echo $cartSummary['total_items']; ?></div>
                            </div>
                            <div>
                                <div class="text-blue-200">Categories</div>
                                <div class="text-white font-semibold">
                                    <?php 
                                    $categories = 0;
                                    foreach ($groupedItems as $type => $items) {
                                        if (!empty($items)) $categories++;
                                    }
                                    echo $categories;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../footer.php'; ?>

    <script>
    // Build itemPrices safely from PHP (id => unit_price)
    const itemPrices = <?php
                            $priceMap = [];
                            foreach ($cartItems as $itm) {
                                $priceMap[$itm['id']] = (float)$itm['unit_price'];
                            }
                            echo json_encode($priceMap, JSON_NUMERIC_CHECK);
                            ?>;

    function updateCartItem(itemId, data) {
        const itemElement = $(`#cart-item-${itemId}`);
        itemElement.addClass('loading');

        const payload = {
            ajax: true,
            update_cart_item: true,
            item_id: itemId,
            quantity: data.quantity !== undefined ? data.quantity : $(`.quantity-input[data-item-id="${itemId}"]`).val(),
            rental_start: data.rental_start !== undefined ? data.rental_start : $(`.rental-date[data-item-id="${itemId}"]`).first().val(),
            rental_end: data.rental_end !== undefined ? data.rental_end : $(`.rental-date[data-item-id="${itemId}"]`).last().val(),
            service_location: data.service_location !== undefined ? data.service_location : $(`.service-location[data-item-id="${itemId}"]`).val()
        };

        payload.quantity = parseInt(payload.quantity) || 1;

        $.ajax({
            url: '',
            type: 'POST',
            dataType: 'json',
            data: payload,
            success: function(result) {
                itemElement.removeClass('loading');

                if (!result) {
                    showToast('Invalid server response', 'error');
                    return;
                }

                if (result.success) {
                    showToast(result.message || 'Updated', 'success');

                    const newTotal = Number(result.new_total) || 0;
                    $(`#item-total-${itemId}`).text(`৳${newTotal.toFixed(2)}`);

                    const qtySpan = $(`#item-quantity-${itemId}`);
                    if (qtySpan.length) qtySpan.text(payload.quantity);

                    const itemUnitPrice = Number(itemPrices[itemId]) || 0;

                    if (data.itemType === 'car' || $(`.rental-date[data-item-id="${itemId}"]`).length) {
                        const startDate = $(`.rental-date[data-item-id="${itemId}"]`).first().val();
                        const endDate = $(`.rental-date[data-item-id="${itemId}"]`).last().val();

                        if (startDate && endDate) {
                            const start = new Date(startDate);
                            const end = new Date(endDate);
                            let days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                            if (isNaN(days) || days <= 0) days = 1;

                            $(`#item-days-${itemId}`).text(`${days} day${days > 1 ? 's' : ''}`);
                            $(`#calculation-${itemId}`).html(
                                `৳${itemUnitPrice.toFixed(2)} × ${days} days × ${payload.quantity} = ৳${newTotal.toFixed(2)}`
                            );
                        }
                    } else {
                        $(`#calculation-${itemId}`).html(
                            `৳${itemUnitPrice.toFixed(2)} × ${payload.quantity} = ৳${newTotal.toFixed(2)}`
                        );
                    }

                    updateOrderSummary();
                } else {
                    showToast(result.message || 'Update failed', 'error');
                }
            },
            error: function(xhr, status, err) {
                itemElement.removeClass('loading');
                showToast('Error updating item', 'error');
                console.error('AJAX error:', status, err, xhr.responseText);
            }
        });
    }

    function updateOrderSummary() {
        $.ajax({
            url: '',
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: true,
                calculate_total: true
            },
            success: function(result) {
                if (!result) {
                    showToast('Invalid totals response', 'error');
                    return;
                }

                const subtotal = Number(result.subtotal) || 0;
                const totalItems = Number(result.total_items) || 0;
                
                // Check if there are physical items that need delivery
                let hasPhysicalItems = false;
                $('.item-card').each(function() {
                    const itemType = $(this).find('.badge').text().toLowerCase();
                    if (itemType === 'vehicle' || itemType === 'part') {
                        hasPhysicalItems = true;
                        return false; // break loop
                    }
                });
                
                const deliveryCharge = hasPhysicalItems ? 60 : 0;
                const grandTotal = subtotal + deliveryCharge;

                $('#summary-subtotal').text(`৳${subtotal.toFixed(2)}`);
                $('#summary-total').text(`৳${grandTotal.toFixed(2)}`);
                $('#total-items-count').text(totalItems);
                $('#summary-total-items').text(totalItems);
                $('#stats-total-items').text(totalItems);
                
                // Update modal values too
                $('#modal-subtotal').text(`৳${subtotal.toFixed(2)}`);
                $('#modal-total').text(`৳${grandTotal.toFixed(2)}`);
                
                // Update delivery row in modal
                const deliveryRow = $('#delivery-row');
                if (deliveryCharge > 0) {
                    deliveryRow.find('span:last').text(`৳${deliveryCharge.toFixed(2)}`);
                    deliveryRow.show();
                } else {
                    deliveryRow.hide();
                }
            },
            error: function(xhr, status, err) {
                showToast('Error calculating total', 'error');
                console.error('Totals AJAX error:', status, err, xhr.responseText);
            }
        });
    }

    // Checkout Modal Functions
    function openCheckoutModal() {
    // Validate required fields before opening modal
    let isValid = true;
    const errors = [];

    // Check rental dates for cars
    const processedCarIds = new Set();
    $('.rental-date[data-item-type="car"]').each(function() {
        const itemId = $(this).data('item-id');
        if (processedCarIds.has(itemId)) return;
        processedCarIds.add(itemId);

        const startInput = $(`.rental-date[data-item-id="${itemId}"]`).first();
        const endInput = $(`.rental-date[data-item-id="${itemId}"]`).last();

        const startVal = startInput.val();
        const endVal = endInput.val();

        if ((startVal && !endVal) || (!startVal && endVal)) {
            isValid = false;
            errors.push('Please set both start and end dates for all car rentals');
            return false;
        }

        if (startVal && endVal) {
            const startDate = new Date(startVal);
            const endDate = new Date(endVal);
            if (endDate <= startDate) {
                isValid = false;
                errors.push('Rental end date must be after start date');
                return false;
            }
        }
    });

    // Check service locations
    $('.service-location').each(function() {
        const itemId = $(this).data('item-id');
        if (!$(this).val()) {
            isValid = false;
            errors.push('Please select service location for all services');
            return false;
        }
    });

    if (!isValid) {
        errors.forEach(error => showToast(error, 'error'));
        return;
    }

    // Get the current totals directly from the order summary sidebar
    // This ensures the modal shows exactly what the user sees in the sidebar
    const currentSubtotal = parseFloat($('#summary-subtotal').text().replace('৳', '').replace(/,/g, '')) || 0;
    const currentTotal = parseFloat($('#summary-total').text().replace('৳', '').replace(/,/g, '')) || 0;
    
    // Calculate delivery charge from the difference
    const deliveryCharge = currentTotal - currentSubtotal;

    // Update modal with the exact same values as sidebar
    $('#modal-subtotal').text('৳' + currentSubtotal.toFixed(2));
    $('#modal-total').text('৳' + currentTotal.toFixed(2));
    
    // Show/hide delivery row in modal
    const deliveryRow = $('#delivery-row');
    if (deliveryCharge > 0) {
        deliveryRow.find('span:last').text('৳' + deliveryCharge.toFixed(2));
        deliveryRow.show();
    } else {
        deliveryRow.hide();
    }
    
    $('#checkout-modal').removeClass('hidden');
}

    function closeCheckoutModal() {
        $('#checkout-modal').addClass('hidden');
    }

    function showToast(message, type) {
        const toast = $(`<div class="fixed top-4 right-4 z-50 px-6 py-3 rounded-lg text-white font-medium ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}">${message}</div>`);
        $('body').append(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // Validation functions
    function validateName(name) {
        return name.trim().length >= 2 && name.trim().split(' ').length >= 2;
    }

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
    }

    function validatePhone(phone) {
        return /^01[3-9]\d{8}$/.test(phone.trim());
    }

    function validateAddress(address) {
        return address.trim().length >= 10;
    }

    function showError(fieldId, message) {
        const errorElement = document.getElementById(fieldId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
            
            const inputField = document.querySelector(`[name="${fieldId.replace('-error', '')}"]`);
            if (inputField) {
                inputField.classList.add('border-red-500', 'shake-animation');
                setTimeout(() => {
                    inputField.classList.remove('shake-animation');
                }, 500);
            }
        }
    }

    function hideError(fieldId) {
        const errorElement = document.getElementById(fieldId);
        if (errorElement) {
            errorElement.classList.add('hidden');
            const inputField = document.querySelector(`[name="${fieldId.replace('-error', '')}"]`);
            if (inputField) {
                inputField.classList.remove('border-red-500');
            }
        }
    }

    // Event handlers
    $(document).ready(function() {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        $('input[type="date"]').attr('min', today);

        // Quantity controls
        $('.quantity-btn.increase').click(function() {
            const itemId = $(this).data('item-id');
            const itemType = $(this).data('item-type');
            const input = $(`.quantity-input[data-item-id="${itemId}"]`);
            const newQuantity = parseInt(input.val()) + 1;
            input.val(newQuantity);
            updateCartItem(itemId, {
                quantity: newQuantity,
                itemType: itemType
            });
        });

        $('.quantity-btn.decrease').click(function() {
            const itemId = $(this).data('item-id');
            const itemType = $(this).data('item-type');
            const input = $(`.quantity-input[data-item-id="${itemId}"]`);
            const newQuantity = parseInt(input.val()) - 1;
            if (newQuantity >= 1) {
                input.val(newQuantity);
                updateCartItem(itemId, {
                    quantity: newQuantity,
                    itemType: itemType
                });
            }
        });

        // Manual quantity input
        $('.quantity-input').change(function() {
            const itemId = $(this).data('item-id');
            const itemType = $(this).data('item-type');
            let quantity = parseInt($(this).val()) || 1;
            if (quantity < 1) quantity = 1;
            $(this).val(quantity);
            updateCartItem(itemId, {
                quantity: quantity,
                itemType: itemType
            });
        });

        // Rental date changes
        $('.rental-date').change(function() {
            const itemId = $(this).data('item-id');
            const itemType = $(this).data('item-type');
            const startInput = $(`.rental-date[data-item-id="${itemId}"]`).first();
            const endInput = $(`.rental-date[data-item-id="${itemId}"]`).last();

            if (startInput.val() && endInput.val()) {
                const startDate = new Date(startInput.val());
                const endDate = new Date(endInput.val());

                if (endDate <= startDate) {
                    showToast('Rental end date must be after start date', 'error');
                    startInput.val('');
                    endInput.val('');
                    return;
                }

                updateCartItem(itemId, {
                    rental_start: startInput.val(),
                    rental_end: endInput.val(),
                    itemType: itemType
                });
            }
        });

        // Service location changes
        $('.service-location').change(function() {
            const itemId = $(this).data('item-id');
            const itemType = $(this).data('item-type');
            updateCartItem(itemId, {
                service_location: $(this).val(),
                itemType: itemType
            });
        });

        // Close modal when clicking outside
        $(document).on('click', '#checkout-modal', function(e) {
            if (e.target === this) closeCheckoutModal();
        });

        // Checkout form validation
        $(document).on('submit', '#checkout-form', function(e) {
            let isValid = true;
            const formData = new FormData(this);
            
            // Validate all fields
            if (!validateName(formData.get('customer_name'))) {
                showError('name-error', 'Enter full name (first & last)');
                isValid = false;
            } else {
                hideError('name-error');
            }
            
            if (!validateEmail(formData.get('customer_email'))) {
                showError('email-error', 'Enter valid email');
                isValid = false;
            } else {
                hideError('email-error');
            }
            
            if (!validatePhone(formData.get('customer_phone'))) {
                showError('phone-error', 'Enter valid phone (01XXXXXXXXX)');
                isValid = false;
            } else {
                hideError('phone-error');
            }
            
            if (!validateAddress(formData.get('customer_address'))) {
                showError('address-error', 'Enter complete address');
                isValid = false;
            } else {
                hideError('address-error');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });

        // Clear errors on input
        $(document).on('input', '#checkout-form input, #checkout-form textarea', function() {
            const fieldName = this.name;
            hideError(`${fieldName}-error`);
        });

        // Phone number formatting
        $(document).on('input', 'input[name="customer_phone"]', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.startsWith('1')) {
                value = '0' + value;
            }
            
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            e.target.value = value;
        });

        // Show PHP messages
        <?php if (!empty($success)): ?>
            showToast('<?php echo addslashes($success); ?>', 'success');
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            showToast('<?php echo addslashes($error); ?>', 'error');
        <?php endif; ?>
    });
    </script>
</body>
</html>