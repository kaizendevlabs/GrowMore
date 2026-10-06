<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../config/database.php";


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: investors.php");
    exit;
}

$inv_id = (int) $_GET['id'];


/* Get investor details */

$stmt = mysqli_prepare(
    $conn,
    "SELECT inv_id, name, mobile, email
     FROM investors
     WHERE inv_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $inv_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {

    mysqli_stmt_close($stmt);

    header("Location: investors.php");
    exit;
}

$investor = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html>

<head>

    <title>View Investor - Grow More</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <h2>Investor Details</h2>

    <p>
        <strong>Investor ID:</strong>
        <?php
        echo "INV" . str_pad(
            $investor['inv_id'],
            3,
            "0",
            STR_PAD_LEFT
        );
        ?>
    </p>

    <p>
        <strong>Name:</strong>
        <?php
        echo htmlspecialchars($investor['name']);
        ?>
    </p>

    <p>
        <strong>Mobile Number:</strong>
        <?php
        echo htmlspecialchars($investor['mobile']);
        ?>
    </p>

    <p>
        <strong>Email:</strong>
        <?php
        echo htmlspecialchars($investor['email']);
        ?>
    </p>

    <br>

    <a href="edit_investor.php?id=<?php echo $investor['inv_id']; ?>">
        Edit Investor
    </a>

    <br><br>

    <a href="investors.php">
        Back to Investors
    </a>

    <br><br>

    <a href="dashboard.php">
        Back to Dashboard
    </a>

</div>

</body>

</html>