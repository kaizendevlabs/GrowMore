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


/* Check whether investor exists */

$stmt = mysqli_prepare(
    $conn,
    "SELECT inv_id FROM investors WHERE inv_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $inv_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {

    mysqli_stmt_close($stmt);

    header("Location: investors.php");
    exit;
}

mysqli_stmt_close($stmt);


/* Delete investor */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM investors WHERE inv_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $inv_id);

mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);

header("Location: investors.php");
exit;

?>