<?php
session_start();
include(__DIR__ . '/db.php'); // Secure path

// If already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}

// Define your secret admin access code
$admin_secret_code = "ADMIN2025";

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // user or admin
    $admin_code = $_POST['admin_code'] ?? '';

    // Input validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.');</script>";
    } elseif ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.');</script>";
    } elseif (strlen($password) < 6) {
        echo "<script>alert('Password must be at least 6 characters long.');</script>";
    } elseif ($role === 'admin' && $admin_code !== $admin_secret_code) {
        echo "<script>alert('Invalid admin access code.');</script>";
    } else {
        // Check if email exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('Email already exists. Please use another.');</script>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                echo "<script>
                    alert('Registration successful! You can now log in.');
                    window.location='login.php';
                </script>";
            } else {
                echo "<script>alert('Registration failed. Please try again.');</script>";
            }

            $stmt->close();
        }

        $check->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register | Event Booking System</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #2980b9, #6dd5fa);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}
.register-container {
    background: #fff;
    width: 380px;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    text-align: center;
}
h2 {
    margin-bottom: 20px;
    color: #2c3e50;
}
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
input[type="text"],
input[type="email"],
input[type="password"],
select {
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
}
input:focus, select:focus {
    border-color: #2980b9;
    box-shadow: 0 0 4px rgba(41, 128, 185, 0.4);
    outline: none;
}
button {
    background-color: #2980b9;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}
button:hover {
    background-color: #1f6391;
}
a {
    display: block;
    margin-top: 15px;
    text-decoration: none;
    color: #2980b9;
    font-size: 14px;
    transition: 0.3s;
}
a:hover {
    color: #1f6391;
    text-decoration: underline;
}
.footer {
    margin-top: 20px;
    color: #888;
    font-size: 12px;
}
.hidden {
    display: none;
}
</style>
<script>
function toggleAdminCode() {
    const roleSelect = document.getElementById('role');
    const adminField = document.getElementById('admin-code-field');
    adminField.classList.toggle('hidden', roleSelect.value !== 'admin');
}
</script>
</head>
<body>

<div class="register-container">
    <h2>📝 Create Account</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password (min 6 chars)" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        
        <select name="role" id="role" onchange="toggleAdminCode()" required>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>

        <div id="admin-code-field" class="hidden">
            <input type="password" name="admin_code" placeholder="Enter Admin Access Code">
        </div>

        <button type="submit" name="register">Register</button>
    </form>
    <a href="login.php">Already have an account? Login</a>
    <div class="footer">&copy; <?= date('Y') ?> Event Booking System</div>
</div>

</body>
</html>
