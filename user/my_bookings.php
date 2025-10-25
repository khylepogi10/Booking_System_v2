<?php
session_start();
include(__DIR__ . '/../db.php');

// ✅ Session timeout (15 minutes)
$timeout = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?session=expired");
    exit;
}
$_SESSION['last_activity'] = time();

// ✅ Verify login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// ✅ Fetch user's bookings
$sql = "
SELECT 
    b.id AS booking_id,
    e.event_name,
    e.date,
    e.location,
    e.price,
    b.booking_date
FROM bookings b
JOIN events e ON b.event_id = e.id
WHERE b.user_id = ?
ORDER BY b.booking_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// ✅ Compute booking summary
$totalBookings = $result->num_rows;
$totalSpent = 0;
$upcomingCount = 0;

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
    $totalSpent += $row['price'];
    if (strtotime($row['date']) > time()) {
        $upcomingCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings | Event Booking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- ✅ Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: #0d6efd;
        }
        .navbar a {
            color: white !important;
        }
        .navbar a:hover {
            color: #ffc107 !important;
        }
        footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 40px;
        }
        .summary-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }
        .summary-box h5 {
            color: #0d6efd;
        }
        .clock {
            color: #ffc107;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>

<!-- ✅ Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">🎟️ Event Booking</a>
        <div class="d-flex align-items-center">
            <span class="text-white me-3">Welcome, <strong><?= htmlspecialchars($user_name) ?></strong></span>
            <span id="clock" class="clock"></span>
            <a class="nav-link d-inline ms-3" href="dashboard.php">Dashboard</a>
            <a class="nav-link d-inline" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <h2 class="text-center text-primary mb-4">My Bookings</h2>

    <!-- 📊 Booking Summary -->
    <div class="row text-center mb-4">
        <div class="col-md-4 mb-3">
            <div class="summary-box">
                <h5>Total Bookings</h5>
                <h3><?= $totalBookings ?></h3>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="summary-box">
                <h5>Total Spent</h5>
                <h3>₱<?= number_format($totalSpent, 2) ?></h3>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="summary-box">
                <h5>Upcoming Events</h5>
                <h3><?= $upcomingCount ?></h3>
            </div>
        </div>
    </div>

    <?php if ($totalBookings > 0): ?>
        <!-- 🔍 Search + Sort + Download -->
        <div class="d-flex justify-content-between mb-3">
            <div class="d-flex gap-2">
                <input type="text" id="searchInput" class="form-control" placeholder="Search your bookings...">
                <select id="sortSelect" class="form-select">
                    <option value="">Sort by</option>
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="price">Price</option>
                </select>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="window.print()">🖨️ Print / Save</button>
            </div>
        </div>

        <div class="table-responsive shadow-sm">
            <table class="table table-bordered table-hover align-middle bg-white" id="bookingsTable">
                <thead class="table-primary text-center">
                    <tr>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Price</th>
                        <th>Booking Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $row): 
                        $isUpcoming = strtotime($row['date']) > time();
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['event_name']) ?></td>
                            <td><?= date("F j, Y", strtotime($row['date'])) ?></td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                            <td>₱<?= number_format($row['price'], 2) ?></td>
                            <td><?= date("F j, Y g:i A", strtotime($row['booking_date'])) ?></td>
                            <td class="text-center">
                                <?= $isUpcoming 
                                    ? '<span class="badge bg-success">Upcoming</span>' 
                                    : '<span class="badge bg-secondary">Completed</span>' ?>
                            </td>
                            <td class="text-center">
                                <?php if ($isUpcoming): ?>
                                    <button class="btn btn-sm btn-danger cancel-btn" data-id="<?= $row['booking_id'] ?>">Cancel</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled>Done</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">
            You have no bookings yet. <a href="dashboard.php" class="alert-link">Book an event</a> now!
        </div>
    <?php endif; ?>
</div>

<footer>
    &copy; <?= date("Y") ?> Event Booking System | Built with ❤️ by Your Team
</footer>

<!-- ✅ Scripts -->
<script>
    // 🕒 Real-Time Clock
    function updateClock() {
        document.getElementById("clock").textContent = new Date().toLocaleTimeString();
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 🔍 Search Filter
    document.getElementById("searchInput").addEventListener("keyup", function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll("#bookingsTable tbody tr").forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? "" : "none";
        });
    });

    // 🔄 Sort Bookings
    document.getElementById("sortSelect").addEventListener("change", function() {
        const rows = Array.from(document.querySelectorAll("#bookingsTable tbody tr"));
        const tbody = document.querySelector("#bookingsTable tbody");
        const sortBy = this.value;

        rows.sort((a, b) => {
            const dateA = new Date(a.cells[1].innerText);
            const dateB = new Date(b.cells[1].innerText);
            const priceA = parseFloat(a.cells[3].innerText.replace('₱','').replace(',',''));
            const priceB = parseFloat(b.cells[3].innerText.replace('₱','').replace(',',''));

            if (sortBy === "newest") return dateB - dateA;
            if (sortBy === "oldest") return dateA - dateB;
            if (sortBy === "price") return priceB - priceA;
            return 0;
        });

        rows.forEach(row => tbody.appendChild(row));
    });

    // ❌ Cancel Booking (confirmation)
    document.querySelectorAll(".cancel-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            const id = this.getAttribute("data-id");
            if (confirm("Are you sure you want to cancel this booking?")) {
                window.location.href = "cancel_booking.php?id=" + id;
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
