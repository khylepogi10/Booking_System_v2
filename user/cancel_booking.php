<?php
session_start();
include(__DIR__ . '/../db.php');

// ✅ Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// ✅ Validate booking ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my_bookings.php?error=invalid_id");
    exit;
}

$booking_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// ✅ Step 1: Verify that the booking belongs to the logged-in user
$check_sql = "SELECT event_id FROM bookings WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $booking_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    // ❌ No booking found or not owned by this user
    header("Location: my_bookings.php?error=not_found");
    exit;
}

$booking = $check_result->fetch_assoc();
$event_id = $booking['event_id'];

// ✅ Step 2: Delete the booking
$delete_sql = "DELETE FROM bookings WHERE id = ? AND user_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $booking_id, $user_id);

if ($delete_stmt->execute()) {
    // ✅ Step 3: Increase seat count for the event
    $update_sql = "UPDATE events SET seats = seats + 1 WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $event_id);
    $update_stmt->execute();

    // ✅ Redirect with success message
    header("Location: my_bookings.php?success=cancelled");
    exit;
} else {
    // ❌ If deletion fails
    header("Location: my_bookings.php?error=delete_failed");
    exit;
}
?>
