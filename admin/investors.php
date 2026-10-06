<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../config/database.php";

$sql = "SELECT * FROM investors ORDER BY inv_id DESC";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Investors - Grow More</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <h2>Investor List</h2>

    <a href="add_investor.php">Add New Investor</a>

    <br><br>

    <?php if (mysqli_num_rows($result) > 0) { ?>

        <table>

            <tr>

                <th>Inv_ID</th>
                <th>Name</th>
                <th>Mobile Number</th>
                <th>Email</th>
                <th>Action</th>

            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                <tr>

                    <td>
                        <?php
                        echo "INV" . str_pad(
                            $row['inv_id'],
                            3,
                            "0",
                            STR_PAD_LEFT
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars($row['name']);
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars($row['mobile']);
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars($row['email']);
                        ?>
                    </td>

                    <td>

                       <a href="view_investor.php?id=<?php echo $row['inv_id']; ?>">
    View
</a>
|

                        <a href="edit_investor.php?id=<?php echo $row['inv_id']; ?>">
                            Edit
                        </a>

                        |

                        <a href="delete_investor.php?id=<?php echo $row['inv_id']; ?>">
                            Delete
                        </a>

                    </td>

                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>No investors found.</p>

    <?php } ?>

    <br>

    <a href="dashboard.php">Back to Dashboard</a>

</div>

</body>

</html>