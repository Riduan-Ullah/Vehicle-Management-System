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

if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM parts WHERE id=$id");
    if ($res && $res->num_rows > 0) {
        $edit_row = $res->fetch_assoc();
        $edit = true;
    }
}
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $sku = $_POST['sku'];
    $category = $_POST['category'];
    $stock_quantity = $_POST['stock_quantity'];
    $picture = $edit_row['image_url'] ?? '';
    if (!empty($_FILES['picture']['name'])) {
        $picture = $_FILES['picture']['name'];
        $target = "../resources/part/" . basename($picture);
        move_uploaded_file($_FILES['picture']['tmp_name'], $target);
    }
    $stmt = $conn->prepare("UPDATE parts SET name=?, description=?, price=?, image_url=?, sku=?, category=?, stock_quantity=? WHERE id=?");
    $stmt->bind_param("ssdsssii", $name, $desc, $price, $picture, $sku, $category, $stock_quantity, $id);
    $stmt->execute();
    $stmt->close();
    $msg = "Part updated!";
    $edit = false;
}
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $sku = $_POST['sku'];
    $category = $_POST['category'];
    $stock_quantity = $_POST['stock_quantity'];
    $picture = $_FILES['picture']['name'];
    $target = "../resources/part/" . basename($picture);
    if (move_uploaded_file($_FILES['picture']['tmp_name'], $target)) {
        $stmt = $conn->prepare("INSERT INTO parts (name, description, price, image_url, sku, category, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsssi", $name, $desc, $price, $picture, $sku, $category, $stock_quantity);
        $stmt->execute();
        $stmt->close();
        $msg = "Part added!";
    } else {
        $msg = "Image upload failed!";
    }
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM parts WHERE id=$id");
    $msg = "Part deleted!";
}
$result = $conn->query("SELECT * FROM parts ORDER BY name ASC");
?>
<?php include __DIR__ . '/../navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Parts</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 pt-20">
<div class="max-w-6xl mx-auto p-6 bg-white rounded-xl shadow mt-8">
    <h2 class="text-2xl font-bold mb-4 text-blue-700">Manage Parts</h2>
    <?php if (!empty($msg)) echo "<div class='mb-4 text-green-600 font-semibold'>$msg</div>"; ?>
    <form method="post" enctype="multipart/form-data" class="mb-8 grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php if ($edit && $edit_row): ?>
            <input type="hidden" name="id" value="<?= $edit_row['id'] ?>">
            <input required name="name" value="<?= htmlspecialchars($edit_row['name']) ?>" placeholder="Part Name" class="border p-2 rounded" />
            <input required name="sku" value="<?= htmlspecialchars($edit_row['sku']) ?>" placeholder="SKU" class="border p-2 rounded" />
            <input required name="category" value="<?= htmlspecialchars($edit_row['category']) ?>" placeholder="Category" class="border p-2 rounded" />
            <input required name="price" type="number" step="0.01" value="<?= htmlspecialchars($edit_row['price']) ?>" placeholder="Price" class="border p-2 rounded" />
            <input required name="stock_quantity" type="number" min="0" value="<?= (int)$edit_row['stock_quantity'] ?>" placeholder="Stock Quantity" class="border p-2 rounded" />
            <input name="picture" type="file" accept="image/*" class="border p-2 rounded" />
            <textarea required name="description" placeholder="Description" class="border p-2 rounded col-span-3"><?= htmlspecialchars($edit_row['description']) ?></textarea>
            <button name="update" class="bg-blue-600 text-white px-4 py-2 rounded col-span-3">Update Part</button>
        <?php else: ?>
            <input required name="name" placeholder="Part Name" class="border p-2 rounded" />
            <input required name="sku" placeholder="SKU" class="border p-2 rounded" />
            <input required name="category" placeholder="Category" class="border p-2 rounded" />
            <input required name="price" type="number" step="0.01" placeholder="Price" class="border p-2 rounded" />
            <input required name="stock_quantity" type="number" min="0" placeholder="Stock Quantity" class="border p-2 rounded" />
            <input required name="picture" type="file" accept="image/*" class="border p-2 rounded" />
            <textarea required name="description" placeholder="Description" class="border p-2 rounded col-span-3"></textarea>
            <button name="add" class="bg-blue-600 text-white px-4 py-2 rounded col-span-3">Add Part</button>
        <?php endif; ?>
    </form>
    <table class="w-full table-auto border">
        <thead>
            <tr class="bg-blue-100">
                <th class="p-2">Name</th>
                <th class="p-2">SKU</th>
                <th class="p-2">Category</th>
                <th class="p-2">Price</th>
                <th class="p-2">Stock</th>
                <th class="p-2">Image</th>
                <th class="p-2">Description</th>
                <th class="p-2">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr class="border-t">
                <td class="p-2"><?=htmlspecialchars($row['name'])?></td>
                <td class="p-2"><?=htmlspecialchars($row['sku'])?></td>
                <td class="p-2"><?=htmlspecialchars($row['category'])?></td>
                <td class="p-2">৳<?=number_format($row['price'],2)?></td>
                <td class="p-2"><?= (int)$row['stock_quantity'] ?></td>
                <td class="p-2">
                    <?php if (!empty($row['image_url'])): ?>
                        <img src="../resources/part/<?=htmlspecialchars($row['image_url'])?>" width="60">
                    <?php endif; ?>
                </td>
                <td class="p-2"><?=htmlspecialchars($row['description'])?></td>
                <td class="p-2">
                    <a href="?edit=<?=$row['id']?>" class="text-blue-600 mr-2">Edit</a>
                    <a href="?delete=<?=$row['id']?>" onclick="return confirm('Delete this part?')" class="text-red-600">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>