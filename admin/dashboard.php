<?php
session_start();
include '../db.php';

// Session check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Auto logout after 15 minutes
$timeout = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// Fetch stats
$total_events = $conn->query("SELECT COUNT(*) AS count FROM events")->fetch_assoc()['count'];
$total_bookings = $conn->query("SELECT COUNT(*) AS count FROM bookings")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role='user'")->fetch_assoc()['count'];

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

$query = "SELECT * FROM events WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (event_name LIKE ? OR location LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}
if ($filter_date) {
    $query .= " AND date = ?";
    $params[] = $filter_date;
    $types .= "s";
}
$query .= " ORDER BY date ASC";
$stmt = $conn->prepare($query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$events = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #eef2f7; padding: 20px; }
.header {
    background: white; padding: 20px 40px; border-radius: 12px;
    margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: flex; justify-content: space-between; align-items: center;
}
.header h1 { color: #2c3e50; }
.nav-links a {
    margin-left: 20px; color: #2980b9; text-decoration: none; font-weight: 600;
}
.nav-links a:hover { color: #1f6391; }
.stats-container {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;
}
.stat-card {
    background: white; padding: 25px; border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center;
}
.stat-card h3 { font-size: 14px; color: #7f8c8d; margin-bottom: 10px; text-transform: uppercase; }
.stat-card .number { font-size: 36px; color: #2980b9; font-weight: bold; }
.container {
    background: white; padding: 30px; border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15); margin-top: 30px;
}
table {
    width: 100%; border-collapse: collapse; margin-top: 20px;
}
th, td { padding: 12px; border-bottom: 1px solid #ecf0f1; text-align: left; }
th { background: #34495e; color: white; }
tr:hover { background: #f8f9fa; }
.btn {
    background: #2980b9; color: white; padding: 8px 12px;
    border-radius: 6px; text-decoration: none; font-weight: 600;
}
.btn:hover { background: #1f6391; }
.btn-success { background: #27ae60; }
.btn-warning { background: #f39c12; }
.btn-danger { background: #e74c3c; }
</style>
</head>
<body>
    <div class="header">
        <h1>Admin Dashboard</h1>
        <div class="nav-links">
            <a href="view_bookings.php">View Bookings</a>
            <a href="../index.php">Home</a>
            <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
        </div>
    </div>

    <div class="stats-container">
        <div class="stat-card"><h3>Total Events</h3><div class="number"><?= $total_events ?></div></div>
        <div class="stat-card"><h3>Total Bookings</h3><div class="number"><?= $total_bookings ?></div></div>
        <div class="stat-card"><h3>Total Users</h3><div class="number"><?= $total_users ?></div></div>
    </div>

    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2>Manage Events</h2>
            <a href="add_event.php" class="btn btn-success">+ Add Event</a>
        </div>

        <form method="GET" style="margin-top:20px;display:flex;gap:10px;">
            <input type="text" name="search" placeholder="Search by name/location" value="<?= htmlspecialchars($search) ?>">
            <input type="date" name="filter_date" value="<?= htmlspecialchars($filter_date) ?>">
            <button type="submit" class="btn">Search</button>
            <a href="dashboard.php" class="btn" style="background:#95a5a6;">Clear</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Image</th><th>Name</th><th>Date</th><th>Location</th>
                    <th>Seats</th><th>Price</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $events->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if ($row['image']): ?>
                            <img src="../uploads/<?= htmlspecialchars($row['image']) ?>" width="60" height="60" style="border-radius:6px;object-fit:cover;">
                        <?php else: ?>
                            <div style="background:#ecf0f1;width:60px;height:60px;display:flex;align-items:center;justify-content:center;">No Image</div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['event_name']) ?></td>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td><?= htmlspecialchars($row['seats']) ?></td>
                    <td>$<?= number_format($row['price'], 2) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td>
                        <a href="edit_event.php?id=<?= $row['id'] ?>" class="btn btn-warning">Edit</a>
                        <a href="delete_event.php?id=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this event?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
