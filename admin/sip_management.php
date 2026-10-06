<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../config/database.php";

$error = "";

$inv_id = "";
$investment_type = "";
$scheme_name = "";
$amount = "";
$frequency = "";
$investment_date = "";
$status = "Active";

if (isset($_POST['save_sip'])) {

    $inv_id = trim($_POST['inv_id']);
    $investment_type = trim($_POST['investment_type']);
    $scheme_name = trim($_POST['scheme_name']);
    $amount = trim($_POST['amount']);
    $frequency = trim($_POST['frequency']);
    $investment_date = trim($_POST['investment_date']);
    $status = trim($_POST['status']);

    if (
        $inv_id == "" ||
        $investment_type == "" ||
        $scheme_name == "" ||
        $amount == "" ||
        $frequency == "" ||
        $investment_date == ""
    ) {

        $error = "Please fill all required fields.";

    } else {

        /*
         * Investor ID exists or not
         */
        $investor_stmt = mysqli_prepare(
            $conn,
            "SELECT inv_id FROM investors WHERE inv_id = ?"
        );

        mysqli_stmt_bind_param(
            $investor_stmt,
            "i",
            $inv_id
        );

        mysqli_stmt_execute($investor_stmt);

        $investor_result = mysqli_stmt_get_result($investor_stmt);

        if (mysqli_num_rows($investor_result) == 0) {

            $error = "Selected investor does not exist.";

            mysqli_stmt_close($investor_stmt);

        } else {

            mysqli_stmt_close($investor_stmt);

            /*
             * Save SIP
             */
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO sip_management
                (inv_id, investment_type, scheme_name, amount, frequency, investment_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if ($stmt) {

                $amount = (float)$amount;

                mysqli_stmt_bind_param(
                    $stmt,
                    "issdsss",
                    $inv_id,
                    $investment_type,
                    $scheme_name,
                    $amount,
                    $frequency,
                    $investment_date,
                    $status
                );

                if (mysqli_stmt_execute($stmt)) {

                    mysqli_stmt_close($stmt);

                    header("Location: sip_management.php");
                    exit;

                } else {

                    $error = "Unable to save SIP.";

                    mysqli_stmt_close($stmt);
                }

            } else {

                $error = "Database error. Please try again.";
            }
        }
    }
}


/*
 * Fetch all investors for dropdown
 */
$investor_sql = "SELECT inv_id, name FROM investors ORDER BY inv_id ASC";

$investor_result = mysqli_query(
    $conn,
    $investor_sql
);


/*
 * Fetch SIP records
 */
$sip_sql = "
    SELECT
        s.sip_id,
        s.inv_id,
        i.name AS investor_name,
        s.investment_type,
        s.scheme_name,
        s.amount,
        s.frequency,
        s.investment_date,
        s.status
    FROM sip_management s
    INNER JOIN investors i
        ON s.inv_id = i.inv_id
    ORDER BY s.sip_id DESC
";

$sip_result = mysqli_query(
    $conn,
    $sip_sql
);

?>

<!DOCTYPE html>
<html>

<head>

    <title>SIP Management - Grow More</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <h2>SIP Management</h2>

    <?php if ($error != "") { ?>

        <p><?php echo htmlspecialchars($error); ?></p>

    <?php } ?>


    <h3>Add SIP / Investment</h3>

    <form method="POST" action="">

        <label for="inv_id">Investor</label>

        <select
            id="inv_id"
            name="inv_id"
            required
        >

            <option value="">Select Investor</option>

            <?php while ($investor = mysqli_fetch_assoc($investor_result)) { ?>

                <option
                    value="<?php echo $investor['inv_id']; ?>"
                    <?php
                    if ($inv_id == $investor['inv_id']) {
                        echo "selected";
                    }
                    ?>
                >

                    <?php
                    echo "INV" .
                    str_pad(
                        $investor['inv_id'],
                        3,
                        "0",
                        STR_PAD_LEFT
                    );

                    echo " - " . htmlspecialchars($investor['name']);
                    ?>

                </option>

            <?php } ?>

        </select>


        <label for="investment_type">Investment Type</label>

        <select
            id="investment_type"
            name="investment_type"
            required
        >

            <option value="">Select Investment Type</option>

            <option
                value="SIP"
                <?php
                if ($investment_type == "SIP") {
                    echo "selected";
                }
                ?>
            >
                SIP
            </option>

            <option
                value="Lump Sum"
                <?php
                if ($investment_type == "Lump Sum") {
                    echo "selected";
                }
                ?>
            >
                Lump Sum
            </option>

        </select>


        <label for="scheme_name">Scheme Name</label>

        <input
            type="text"
            id="scheme_name"
            name="scheme_name"
            value="<?php echo htmlspecialchars($scheme_name); ?>"
            required
        >


        <label for="amount">Amount</label>

        <input
            type="number"
            id="amount"
            name="amount"
            value="<?php echo htmlspecialchars($amount); ?>"
            step="0.01"
            min="0"
            required
        >


        <label for="frequency">Frequency</label>

        <select
            id="frequency"
            name="frequency"
            required
        >

            <option value="">Select Frequency</option>

            <option
                value="Monthly"
                <?php
                if ($frequency == "Monthly") {
                    echo "selected";
                }
                ?>
            >
                Monthly
            </option>

            <option
                value="Quarterly"
                <?php
                if ($frequency == "Quarterly") {
                    echo "selected";
                }
                ?>
            >
                Quarterly
            </option>

            <option
                value="One Time"
                <?php
                if ($frequency == "One Time") {
                    echo "selected";
                }
                ?>
            >
                One Time
            </option>

        </select>


        <label for="investment_date">Date</label>

        <input
            type="date"
            id="investment_date"
            name="investment_date"
            value="<?php echo htmlspecialchars($investment_date); ?>"
            required
        >


        <label for="status">Status</label>

        <select
            id="status"
            name="status"
            required
        >

            <option
                value="Active"
                <?php
                if ($status == "Active") {
                    echo "selected";
                }
                ?>
            >
                Active
            </option>

            <option
                value="Inactive"
                <?php
                if ($status == "Inactive") {
                    echo "selected";
                }
                ?>
            >
                Inactive
            </option>

        </select>


        <input
            type="submit"
            name="save_sip"
            value="Save SIP"
        >

    </form>


    <br>

    <h3>SIP List</h3>


    <?php if (mysqli_num_rows($sip_result) > 0) { ?>

        <table>

            <tr>

                <th>SIP_ID</th>
                <th>Inv_ID</th>
                <th>Investor Name</th>
                <th>Investment Type</th>
                <th>Scheme Name</th>
                <th>Amount</th>
                <th>Frequency</th>
                <th>Date</th>
                <th>Status</th>

            </tr>


            <?php while ($row = mysqli_fetch_assoc($sip_result)) { ?>

                <tr>

                    <td>
                        <?php
                        echo "SIP" .
                        str_pad(
                            $row['sip_id'],
                            3,
                            "0",
                            STR_PAD_LEFT
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo "INV" .
                        str_pad(
                            $row['inv_id'],
                            3,
                            "0",
                            STR_PAD_LEFT
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row['investor_name']
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row['investment_type']
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row['scheme_name']
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo "₹" .
                        number_format(
                            $row['amount'],
                            2
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row['frequency']
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row['investment_date']
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row['status']
                        );
                        ?>
                    </td>


                    

                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>No SIP records found.</p>

    <?php } ?>


    <br>

    <a href="dashboard.php">Back to Dashboard</a>

</div>

</body>

</html>