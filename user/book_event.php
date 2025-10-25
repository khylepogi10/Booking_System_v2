<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$event_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// ✅ Check if seats available
$stmt = $conn->prepare("SELECT seats FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$stmt->bind_result($seats);
$stmt->fetch();
$stmt->close();

if ($seats <= 0) {
    echo "<script>alert('Sorry, this event is sold out.'); window.location='dashboard.php';</script>";
    exit;
}

// ✅ Insert booking
$stmt = $conn->prepare("INSERT INTO bookings (user_id, event_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();

// ✅ Reduce available seats
$conn->query("UPDATE events SET seats = seats - 1 WHERE id = $event_id");

echo "<script>alert('Booking successful!'); window.location='my_bookings.php';</script>";
?>
