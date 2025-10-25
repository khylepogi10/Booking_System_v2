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

// ✅ Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'User';

// ✅ Fetch all events
$sql = "SELECT id, event_name, date, location, seats, description, price FROM events ORDER BY date ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard | Event Booking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- ✅ Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fc;
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
        .table-hover tbody tr:hover {
            background-color: #f1f7ff;
        }
        .clock {
            color: #ffc107;
            font-weight: bold;
            margin-left: 10px;
        }
        footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 40px;
        }
        .view-toggle {
            cursor: pointer;
            color: #0d6efd;
        }
        .card-grid {
            display: none;
        }
        .event-card {
            border-radius: 12px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 14px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

<!-- ✅ Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">🎟️ Event Booking</a>
        <div class="d-flex align-items-center">
            <span class="text-white me-3">Welcome, <strong><?= htmlspecialchars($user_name) ?></strong></span>
            <span id="clock" class="clock"></span>
            <a class="nav-link d-inline ms-3" href="my_bookings.php">My Bookings</a>
            <a class="nav-link d-inline" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="text-primary mb-0">Available Events</h2>

        <!-- 🔍 Search + Sort Controls -->
        <div class="d-flex gap-2">
            <input type="text" id="searchInput" class="form-control" placeholder="Search events...">
            <select id="sortSelect" class="form-select">
                <option value="">Sort by</option>
                <option value="soonest">Date (Soonest)</option>
                <option value="latest">Date (Latest)</option>
                <option value="seats">Available Seats</option>
            </select>
            <span id="toggleView" class="view-toggle">🗂️ Toggle View</span>
        </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div id="tableView" class="table-responsive shadow-sm">
            <table class="table table-bordered table-hover align-middle bg-white">
                <thead class="table-primary text-center">
                    <tr>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Seats</th>
                        <th>Price</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="eventTable">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['event_name']) ?></td>
                            <td><?= date("F j, Y", strtotime($row['date'])) ?></td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                            <td class="text-center">
                                <?= ($row['seats'] > 0) 
                                    ? '<span class="badge bg-success">'.$row['seats'].' available</span>' 
                                    : '<span class="badge bg-danger">Sold Out</span>' ?>
                            </td>
                            <td>$<?= number_format($row['price'], 2) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td class="text-center">
                                <?php if ($row['seats'] > 0): ?>
                                    <a href="book_event.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success">
                                        Book Now
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>Sold Out</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">
            No events available at the moment. Please check back later.
        </div>
    <?php endif; ?>
</div>

<footer>
    &copy; <?= date("Y") ?> Event Booking System | Built with ❤️ by Your Team
</footer>

<!-- ✅ JS: Search, Sort, Clock, Auto Logout -->
<script>
    // 🕒 Real-Time Clock
    function updateClock() {
        const now = new Date();
        document.getElementById("clock").textContent = now.toLocaleTimeString();
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 🔍 Search Filter
    document.getElementById("searchInput").addEventListener("keyup", function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll("#eventTable tr");
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });

    // 🔄 Sort Events
    document.getElementById("sortSelect").addEventListener("change", function() {
        const rows = Array.from(document.querySelectorAll("#eventTable tr"));
        const tableBody = document.getElementById("eventTable");
        const sortBy = this.value;

        rows.sort((a, b) => {
            const dateA = new Date(a.cells[1].innerText);
            const dateB = new Date(b.cells[1].innerText);
            const seatsA = parseInt(a.cells[3].innerText) || 0;
            const seatsB = parseInt(b.cells[3].innerText) || 0;

            if (sortBy === "soonest") return dateA - dateB;
            if (sortBy === "latest") return dateB - dateA;
            if (sortBy === "seats") return seatsB - seatsA;
            return 0;
        });

        rows.forEach(row => tableBody.appendChild(row));
    });

    // 🧭 Toggle Table/Card View (Future expansion ready)
    document.getElementById("toggleView").addEventListener("click", function() {
        const table = document.getElementById("tableView");
        table.style.display = (table.style.display === "none") ? "block" : "none";
    });

    // ⚠️ Session Timeout Warning (1 min before auto logout)
    setTimeout(() => {
        alert("⚠️ Your session will expire soon due to inactivity. Please refresh or interact to stay logged in.");
    }, 840000); // 14 minutes
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
