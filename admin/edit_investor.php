<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../config/database.php";

$error = "";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: investors.php");
    exit;
}

$inv_id = (int) $_GET['id'];

$name = "";
$mobile = "";
$email = "";


/* Fetch investor details */

$stmt = mysqli_prepare(
    $conn,
    "SELECT name, mobile, email FROM investors WHERE inv_id = ?"
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

$name = $investor['name'];
$mobile = $investor['mobile'];
$email = $investor['email'];

mysqli_stmt_close($stmt);


/* Update investor */

if (isset($_POST['update_investor'])) {

    $name = trim($_POST['name']);
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);

    if ($name == "" || $mobile == "") {

        $error = "Name and Mobile Number are required.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE investors
             SET name = ?, mobile = ?, email = ?
             WHERE inv_id = ?"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "sssi",
                $name,
                $mobile,
                $email,
                $inv_id
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                header("Location: investors.php");
                exit;

            } else {

                $error = "Unable to update investor.";

                mysqli_stmt_close($stmt);
            }

        } else {

            $error = "Database error. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Edit Investor - Grow More</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <h2>Edit Investor</h2>

    <p>
        Investor ID:
        <?php
        echo "INV" . str_pad($inv_id, 3, "0", STR_PAD_LEFT);
        ?>
    </p>

    <?php if ($error != "") { ?>

        <p><?php echo htmlspecialchars($error); ?></p>

    <?php } ?>


    <form method="POST" action="">

        <label for="name">Name</label>

        <input
            type="text"
            id="name"
            name="name"
            value="<?php echo htmlspecialchars($name); ?>"
            required
        >


        <label for="mobile">Mobile Number</label>

        <input
            type="text"
            id="mobile"
            name="mobile"
            value="<?php echo htmlspecialchars($mobile); ?>"
            maxlength="15"
            required
        >


        <label for="email">Email</label>

        <input
            type="email"
            id="email"
            name="email"
            value="<?php echo htmlspecialchars($email); ?>"
        >


        <input
            type="submit"
            name="update_investor"
            value="Update Investor"
        >

    </form>

    <br>

    <a href="investors.php">Back to Investors</a>

    <br><br>

    <a href="dashboard.php">Back to Dashboard</a>

</div>

</body>

</html>