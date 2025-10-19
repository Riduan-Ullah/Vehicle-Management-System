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
    $dcid = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM driving_course WHERE dcid=$dcid");
    if ($res && $res->num_rows > 0) {
        $edit_row = $res->fetch_assoc();
        $edit = true;
    }
}
if (isset($_POST['update'])) {
    $dcid = intval($_POST['dcid']);
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $level = $_POST['level'];
    $duration = $_POST['duration'];
    $vehicle_type = $_POST['vehicle_type'];
    $price = $_POST['price'];
    $picture = $edit_row['picture'] ?? '';
    if (!empty($_FILES['picture']['name'])) {
        $picture = $_FILES['picture']['name'];
        $target = "../resources/course/" . basename($picture);
        move_uploaded_file($_FILES['picture']['tmp_name'], $target);
    }
    $stmt = $conn->prepare("UPDATE driving_course SET name=?, description=?, level=?, duration=?, vehicle_type=?, price=?, picture=? WHERE dcid=?");
    $stmt->bind_param("sssssssi", $name, $desc, $level, $duration, $vehicle_type, $price, $picture, $dcid);
    $stmt->execute();
    $stmt->close();
    $msg = "Course updated!";
    $edit = false;
}
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $level = $_POST['level'];
    $duration = $_POST['duration'];
    $vehicle_type = $_POST['vehicle_type'];
    $price = $_POST['price'];
    $picture = $_FILES['picture']['name'];
    $target = "../resources/course/" . basename($picture);
    if (move_uploaded_file($_FILES['picture']['tmp_name'], $target)) {
        $stmt = $conn->prepare("INSERT INTO driving_course (name, description, level, duration, vehicle_type, price, picture) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $name, $desc, $level, $duration, $vehicle_type, $price, $picture);
        $stmt->execute();
        $stmt->close();
        $msg = "Course added!";
    } else {
        $msg = "Image upload failed!";
    }
}
if (isset($_GET['delete'])) {
    $dcid = intval($_GET['delete']);
    $conn->query("DELETE FROM driving_course WHERE dcid=$dcid");
    $msg = "Course deleted!";
}
$result = $conn->query("SELECT * FROM driving_course ORDER BY name ASC");
?>
<?php include __DIR__ . '/../navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Courses</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 pt-20">
<div class="max-w-6xl mx-auto p-6 bg-white rounded-xl shadow mt-8">
    <h2 class="text-2xl font-bold mb-4 text-blue-700">Manage Driving Courses</h2>
    <?php if (!empty($msg)) echo "<div class='mb-4 text-green-600 font-semibold'>$msg</div>"; ?>
    <form method="post" enctype="multipart/form-data" class="mb-8 grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php if ($edit && $edit_row): ?>
            <input type="hidden" name="dcid" value="<?= $edit_row['dcid'] ?>">
            <input required name="name" value="<?= htmlspecialchars($edit_row['name']) ?>" placeholder="Course Name" class="border p-2 rounded" />
            <input required name="level" value="<?= htmlspecialchars($edit_row['level']) ?>" placeholder="Level" class="border p-2 rounded" />
            <input required name="duration" value="<?= htmlspecialchars($edit_row['duration']) ?>" placeholder="Duration" class="border p-2 rounded" />
            <input required name="vehicle_type" value="<?= htmlspecialchars($edit_row['vehicle_type']) ?>" placeholder="Vehicle Type" class="border p-2 rounded" />
            <input required name="price" type="number" step="0.01" value="<?= htmlspecialchars($edit_row['price']) ?>" placeholder="Price" class="border p-2 rounded" />
            <input name="picture" type="file" accept="image/*" class="border p-2 rounded" />
            <textarea required name="description" placeholder="Description" class="border p-2 rounded col-span-3"><?= htmlspecialchars($edit_row['description']) ?></textarea>
            <button name="update" class="bg-blue-600 text-white px-4 py-2 rounded col-span-3">Update Course</button>
        <?php else: ?>
            <input required name="name" placeholder="Course Name" class="border p-2 rounded" />
            <input required name="level" placeholder="Level (Beginner/Intermediate/Advanced)" class="border p-2 rounded" />
            <input required name="duration" placeholder="Duration (e.g. 15 days)" class="border p-2 rounded" />
            <input required name="vehicle_type" placeholder="Vehicle Type" class="border p-2 rounded" />
            <input required name="price" type="number" step="0.01" placeholder="Price" class="border p-2 rounded" />
            <input required name="picture" type="file" accept="image/*" class="border p-2 rounded" />
            <textarea required name="description" placeholder="Description" class="border p-2 rounded col-span-3"></textarea>
            <button name="add" class="bg-blue-600 text-white px-4 py-2 rounded col-span-3">Add Course</button>
        <?php endif; ?>
    </form>
    <table class="w-full table-auto border">
        <thead>
            <tr class="bg-blue-100">
                <th class="p-2">Name</th>
                <th class="p-2">Level</th>
                <th class="p-2">Duration</th>
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
                <td class="p-2"><?=htmlspecialchars($row['level'])?></td>
                <td class="p-2"><?=htmlspecialchars($row['duration'])?></td>
                <td class="p-2"><?=htmlspecialchars($row['vehicle_type'])?></td>
                <td class="p-2">৳<?=number_format($row['price'],2)?></td>
                <td class="p-2">
                    <?php if (!empty($row['picture'])): ?>
                        <img src="../resources/course/<?=htmlspecialchars($row['picture'])?>" width="60">
                    <?php endif; ?>
                </td>
                <td class="p-2"><?=htmlspecialchars($row['description'])?></td>
                <td class="p-2">
                    <a href="?edit=<?=$row['dcid']?>" class="text-blue-600 mr-2">Edit</a>
                    <a href="?delete=<?=$row['dcid']?>" onclick="return confirm('Delete this course?')" class="text-red-600">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>