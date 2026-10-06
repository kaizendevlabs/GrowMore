<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Grow More Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container">

    <h2>Grow More Management System</h2>

    <p>Welcome, <?php echo $_SESSION['username']; ?></p>

    <hr>

    <h3>Owner Dashboard</h3>

    <p><a href="investors.php">Investors</a></p>
    <p><a href="sip_management.php">SIP Management</a></p>
    <p><a href="payment_tracking.php">Payments</a></p>
    <p><a href="due_sips.php">Due SIPs</a></p>
    <p><a href="../reports/reports.php">Reports</a></p>

    <p><a href="../logout.php">Logout</a></p>

</div>

</body>

</html>