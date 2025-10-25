<?php
session_start();
include '../db.php';
if ($_SESSION['role'] != 'admin') { header("Location: ../login.php"); exit; }

// Auto logout after 15 mins
$timeout = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?session=expired");
    exit;
}
$_SESSION['last_activity'] = time();

if (isset($_POST['add'])) {
    $event_name = trim($_POST['event_name']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $location = trim($_POST['location']);
    $seats = intval($_POST['seats']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];

    // Single image upload
    $main_image = null;
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $main_image = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $main_image);
        }
    }

    // Multiple images (store as JSON)
    $extra_images = [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $newname = uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], "../uploads/" . $newname)) {
                    $extra_images[] = $newname;
                }
            }
        }
    }
    $images_json = json_encode($extra_images);

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO events (event_name, description, date, location, seats, price, image, category, status, images) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssdssss", $event_name, $description, $date, $location, $seats, $price, $main_image, $category, $status, $images_json);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Event added successfully!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('❌ Error adding event');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }
        .container {
            max-width: 750px;
            margin: auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; margin-bottom: 25px; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        label { font-weight: 600; display: block; margin-bottom: 8px; }
        input, textarea, select {
            width: 100%; padding: 12px;
            border: 1px solid #ddd; border-radius: 6px;
            font-size: 15px;
        }
        .btn {
            background-color: #2980b9; color: white;
            padding: 12px; border: none;
            width: 100%; border-radius: 6px;
            cursor: pointer; font-weight: 600;
        }
        .btn:hover { background-color: #1f6391; }
        .back-link { text-decoration: none; color: #2980b9; font-weight: bold; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    <h2>Add New Event</h2>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Event Name *</label>
            <input type="text" name="event_name" required>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description"></textarea>
        </div>
        <div class="form-group">
            <label>Date *</label>
            <input type="date" name="date" required>
        </div>
        <div class="form-group">
            <label>Location *</label>
            <input type="text" name="location" required>
        </div>
        <div class="form-group">
            <label>Available Seats *</label>
            <input type="number" name="seats" min="1" required>
        </div>
        <div class="form-group">
            <label>Price (₱) *</label>
            <input type="number" name="price" step="0.01" required>
        </div>
        <div class="form-group">
            <label>Category *</label>
            <select name="category" required>
                <option value="">-- Select Category --</option>
                <option value="Concert">Concert</option>
                <option value="Seminar">Seminar</option>
                <option value="Workshop">Workshop</option>
                <option value="Sports">Sports</option>
                <option value="Festival">Festival</option>
            </select>
        </div>
        <div class="form-group">
            <label>Status *</label>
            <select name="status" required>
                <option value="Active">Active</option>
                <option value="Cancelled">Cancelled</option>
                <option value="Completed">Completed</option>
            </select>
        </div>
        <div class="form-group">
            <label>Main Image</label>
            <input type="file" name="image" accept="image/*">
        </div>
        <div class="form-group">
            <label>Extra Images (multiple)</label>
            <input type="file" name="images[]" accept="image/*" multiple>
        </div>
        <button type="submit" name="add" class="btn">Add Event</button>
    </form>
</div>
</body>
</html>
