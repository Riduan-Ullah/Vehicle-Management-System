<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/db_connection.php';

$msg = '';
$edit = false;
$edit_row = null;

// Handle Edit Request (show form with data)
if (isset($_GET['edit'])) {
    $sid = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM services WHERE sid=$sid AND service_type='maintenance'");
    if ($res && $res->num_rows > 0) {
        $edit_row = $res->fetch_assoc();
        $edit = true;
    }
}

// Handle Update
if (isset($_POST['update'])) {
    $sid = intval($_POST['sid']);
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $vehicle_type = $_POST['vehicle_type'];
    $price = floatval($_POST['price']);
    
    // Keep existing picture if no new one uploaded
    $picture = $edit_row['picture'] ?? '';
    
    if (!empty($_FILES['picture']['name']) && $_FILES['picture']['error'] == 0) {
        $picture = "maintenance/" . basename($_FILES['picture']['name']);
        $target = "../resources/service/" . $picture;
        
        // Create directory if it doesn't exist
        $upload_dir = "../resources/service/maintenance/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Check if file is an image
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_types)) {
            if (move_uploaded_file($_FILES['picture']['tmp_name'], $target)) {
                $msg = "Service updated with new image!";
            } else {
                $msg = "Image upload failed!";
                $picture = $edit_row['picture'] ?? ''; // Keep old picture
            }
        } else {
            $msg = "Invalid image format! Allowed: jpg, jpeg, png, gif, webp";
            $picture = $edit_row['picture'] ?? ''; // Keep old picture
        }
    }
    
    // Fix: Use proper type specifiers - 'i' for integer, 'd' for double/float
    $stmt = $conn->prepare("UPDATE services SET name=?, description=?, vehicle_type=?, price=?, picture=? WHERE sid=? AND service_type='maintenance'");
    $stmt->bind_param("sssdsi", $name, $desc, $vehicle_type, $price, $picture, $sid);
    
    if ($stmt->execute()) {
        $msg = $msg ?: "Service updated successfully!";
    } else {
        $msg = "Error updating service: " . $stmt->error;
    }
    $stmt->close();
    $edit = false;
}

// Handle Add
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $vehicle_type = $_POST['vehicle_type'];
    $price = floatval($_POST['price']);
    
    if (!empty($_FILES['picture']['name']) && $_FILES['picture']['error'] == 0) {
        $picture = "maintenance/" . basename($_FILES['picture']['name']);
        $target = "../resources/service/" . $picture;
        
        // Create directory if it doesn't exist
        $upload_dir = "../resources/service/maintenance/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Check if file is an image
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_types)) {
            if (move_uploaded_file($_FILES['picture']['tmp_name'], $target)) {
                $stmt = $conn->prepare("INSERT INTO services (name, description, service_type, vehicle_type, price, picture) VALUES (?, ?, 'maintenance', ?, ?, ?)");
                $stmt->bind_param("sssds", $name, $desc, $vehicle_type, $price, $picture);
                
                if ($stmt->execute()) {
                    $msg = "Service added successfully!";
                } else {
                    $msg = "Error adding service: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $msg = "Image upload failed!";
            }
        } else {
            $msg = "Invalid image format! Allowed: jpg, jpeg, png, gif, webp";
        }
    } else {
        $msg = "Please select a valid image file!";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $sid = intval($_GET['delete']);
    $conn->query("DELETE FROM services WHERE sid=$sid AND service_type='maintenance'");
    $msg = "Service deleted!";
}

// Fetch all maintenance services
$result = $conn->query("SELECT * FROM services WHERE service_type='maintenance' ORDER BY name ASC");
?>
<?php include __DIR__ . '/../navbar.php'; ?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Maintenance</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 pt-20">
<div class="max-w-5xl mx-auto p-6 bg-white rounded-xl shadow mt-8">
    <h2 class="text-2xl font-bold mb-4 text-blue-700">Manage Maintenance Services</h2>
    <?php if (!empty($msg)): ?>
        <div class='mb-4 p-3 rounded <?php echo strpos($msg, 'Error') !== false || strpos($msg, 'failed') !== false || strpos($msg, 'Invalid') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?> font-semibold'><?= $msg ?></div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if ($edit && $edit_row): ?>
            <input type="hidden" name="sid" value="<?= $edit_row['sid'] ?>">
            <input required name="name" value="<?= htmlspecialchars($edit_row['name']) ?>" placeholder="Service Name" class="border p-2 rounded" />
            <input required name="vehicle_type" value="<?= htmlspecialchars($edit_row['vehicle_type']) ?>" placeholder="Vehicle Type" class="border p-2 rounded" />
            <input required name="price" type="number" step="0.01" value="<?= htmlspecialchars($edit_row['price']) ?>" placeholder="Price" class="border p-2 rounded" />
            <input name="picture" type="file" accept="image/*" class="border p-2 rounded" />
            <div class="text-sm text-gray-600">
                <?php if (!empty($edit_row['picture'])): ?>
                    Current: <?= htmlspecialchars(basename($edit_row['picture'])) ?>
                <?php else: ?>
                    No image currently
                <?php endif; ?>
            </div>
            <textarea required name="description" placeholder="Description" class="border p-2 rounded col-span-2"><?= htmlspecialchars($edit_row['description']) ?></textarea>
            <button name="update" class="bg-blue-600 text-white px-4 py-2 rounded col-span-2">Update Service</button>
            <a href="?" class="bg-gray-500 text-white px-4 py-2 rounded text-center col-span-2">Cancel Edit</a>
        <?php else: ?>
            <input required name="name" placeholder="Service Name" class="border p-2 rounded" />
            <input required name="vehicle_type" placeholder="Vehicle Type" class="border p-2 rounded" />
            <input required name="price" type="number" step="0.01" placeholder="Price" class="border p-2 rounded" />
            <input required name="picture" type="file" accept="image/*" class="border p-2 rounded" />
            <div class="text-sm text-gray-600">Allowed: JPG, JPEG, PNG, GIF, WebP</div>
            <textarea required name="description" placeholder="Description" class="border p-2 rounded col-span-2"></textarea>
            <button name="add" class="bg-blue-600 text-white px-4 py-2 rounded col-span-2">Add Service</button>
        <?php endif; ?>
    </form>
    
    <table class="w-full table-auto border">
        <thead>
            <tr class="bg-blue-100">
                <th class="p-2">Name</th>
                <th class="p-2">Vehicle</th>
                <th class="p-2">Price</th>
                <th class="p-2">Image</th>
                <th class="p-2">Description</th>
                <th class="p-2">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr class="border-t">
                <td class="p-2"><?=htmlspecialchars($row['name'])?></td>
                <td class="p-2"><?=htmlspecialchars($row['vehicle_type'])?></td>
                <td class="p-2">৳<?=number_format($row['price'],2)?></td>
                <td class="p-2">
                    <?php if (!empty($row['picture'])): ?>
                        <img src="../resources/service/<?=htmlspecialchars($row['picture'])?>" width="60" alt="<?=htmlspecialchars($row['name'])?>">
                    <?php else: ?>
                        No Image
                    <?php endif; ?>
                </td>
                <td class="p-2"><?=htmlspecialchars($row['description'])?></td>
                <td class="p-2">
                    <a href="?edit=<?=$row['sid']?>" class="text-blue-600 mr-2">Edit</a>
                    <a href="?delete=<?=$row['sid']?>" onclick="return confirm('Delete this service?')" class="text-red-600">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>