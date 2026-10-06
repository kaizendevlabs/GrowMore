<?php

session_start();

include "config/database.php";

if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username' AND status = 'active'";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'owner') {
                header("Location: admin/dashboard.php");
                exit;
            }

            if ($user['role'] == 'employee') {
                header("Location: employee/dashboard.php");
                exit;
            }

        } else {
            $error = "Invalid username or password";
        }

    } else {
        $error = "Invalid username or password";
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Grow More Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="container">

    <h2>Grow More</h2>
    <p>Management System</p>

    <?php
    if (isset($error)) {
        echo "<p>$error</p>";
    }
    ?>

    <form method="POST" action="">

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <input type="submit" name="login" value="Login">

    </form>

</div>

</body>

</html>