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

    <title>Employee Dashboard - Grow More</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <h2>Grow More Management System</h2>

    <p>
        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
    </p>

    <hr>

    <h3>Employee Dashboard</h3>

    <p>
        <a href="../admin/investors.php">
            Investors
        </a>
    </p>

    <p>
        <a href="../admin/sip_management.php">
            SIP Management
        </a>
    </p>

    <p>
        <a href="../admin/payment_tracking.php">
            Payments
        </a>
    </p>

    <p>
        <a href="../admin/due_sips.php">
            Due SIPs
        </a>
    </p>

    <p>
        <a href="../reports/reports.php">
            Reports
        </a>
    </p>

    <p>
        <a href="../logout.php">
            Logout
        </a>
    </p>

</div>

</body>

</html>