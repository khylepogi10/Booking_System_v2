<?php
session_start();
include(__DIR__ . '/../db.php');

// Optional: Protect the admin page
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch all events
$stmt = $conn->prepare("SELECT id, title, date, venue, seats, description FROM events ORDER BY date ASC");
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Events</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e3f2fd, #ffffff);
            margin: 0;
            padding: 20px;
            color: #2c3e50;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        a {
            color: #3498db;
            text-decoration: none;
            margin-right: 15px;
            font-weight: 500;
            transition: 0.3s;
        }

        a:hover {
            text-decoration: underline;
            color: #21618c;
        }

        hr {
            border: 0;
            border-top: 2px solid #d6eaf8;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: white;
        }

        th {
            background-color: #2980b9;
            color: white;
            padding: 12px;
            text-align: left;
            letter-spacing: 0.5px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #e8f4fd;
            transition: background 0.2s ease;
        }

        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 14px;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-edit {
            background-color: #f39c12;
        }

        .btn-edit:hover {
            background-color: #e67e22;
        }

        .btn-delete {
            background-color: #e74c3c;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }

        .btn-add {
            background-color: #27ae60;
            margin-bottom: 20px;
            padding: 10px 20px;
            display: inline-block;
        }

        .btn-add:hover {
            background-color: #219150;
        }

        td span {
            color: #e74c3c;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            body { padding: 10px; }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            th, td { font-size: 14px; }
        }
    </style>
</head>
<body>

<h2>Event Management Dashboard</h2>

<a href="add_event.php" class="btn btn-add">➕ Add New Event</a>
<a href="../logout.php">Logout</a>

<hr>

<table>
    <tr>
        <th>Title</th>
        <th>Date</th>
        <th>Venue</th>
        <th>Seats</th>
        <th>Description</th>
        <th>Actions</th>
    </tr>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['venue']) ?></td>
                <td><?= htmlspecialchars($row['seats']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td>
                    <a href="edit_event.php?id=<?= $row['id'] ?>" class="btn btn-edit">Edit</a>
                    <a href="delete_event.php?id=<?= $row['id'] ?>" class="btn btn-delete" onclick="return confirm('Delete this event?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6" style="text-align:center;">No events found</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
