<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// DB connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "loyal_customer";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = "";

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $currentPass = $_POST['current_password'];
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];
    $user = $_SESSION['username'];

    // Fetch user by username
    $sql = "SELECT * FROM users WHERE username='$user' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check current password
        if ($row['password'] === $currentPass) {
            if ($newPass === $confirmPass) {
                // Update password
                $updateSql = "UPDATE users SET password='$newPass' WHERE username='$user'";
                if ($conn->query($updateSql) === TRUE) {
                    $message = "Password updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error updating password. Please try again.";
                    $messageType = "danger";
                }
            } else {
                $message = "New password and confirmation do not match!";
                $messageType = "warning";
            }
        } else {
            $message = "Current password is incorrect!";
            $messageType = "danger";
        }
    } else {
        $message = "User not found!";
        $messageType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow p-4" style="width: 28rem;">
            <h3 class="text-center mb-3">Change Password</h3>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" class="form-control" name="current_password" id="current_password" required>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" name="new_password" id="new_password" required>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Update Password</button>
            </form>

            <div class="text-center mt-3">
                <a href="loyalCustomer.php" class="btn btn-link">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>