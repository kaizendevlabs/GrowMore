<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../config/database.php";

$error = "";

$name = "";
$mobile = "";
$email = "";

if (isset($_POST['save_investor'])) {

    $name = trim($_POST['name']);
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);

    if ($name == "" || $mobile == "") {

        $error = "Name and Mobile Number are required.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO investors (name, mobile, email) VALUES (?, ?, ?)"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $name,
                $mobile,
                $email
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                header("Location: investors.php");
                exit;

            } else {

                $error = "Unable to save investor.";

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

    <title>Add New Investor - Grow More</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <h2>Add New Investor</h2>

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
            name="save_investor"
            value="Save Investor"
        >

    </form>

    <br>

    <a href="investors.php">Back to Investors</a>

    <br><br>

    <a href="dashboard.php">Back to Dashboard</a>

</div>

</body>

</html>