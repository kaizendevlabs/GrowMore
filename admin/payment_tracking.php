<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../config/database.php";

$error = "";
$success = "";

$inv_id = "";
$sip_id = "";
$amount = "";
$due_date = "";
$payment_date = "";
$status = "Pending";


/* -----------------------------
   SAVE PAYMENT
----------------------------- */

if (isset($_POST['save_payment'])) {

    $inv_id = trim($_POST['inv_id']);
    $sip_id = trim($_POST['sip_id']);
    $amount = trim($_POST['amount']);
    $due_date = trim($_POST['due_date']);
    $payment_date = trim($_POST['payment_date']);
    $status = trim($_POST['status']);

    if ($inv_id == "" || $sip_id == "" || $amount == "" || $due_date == "" || $status == "") {

        $error = "Please fill all required fields.";

    } else {

        /*
         Check that selected SIP exists,
         belongs to selected investor,
         and is a SIP (not Lump Sum).
        */

        $check_stmt = mysqli_prepare(
            $conn,
            "SELECT sip_id, amount
             FROM sip_management
             WHERE sip_id = ?
             AND inv_id = ?
             AND investment_type = 'SIP'"
        );

        if ($check_stmt) {

            mysqli_stmt_bind_param(
                $check_stmt,
                "ii",
                $sip_id,
                $inv_id
            );

            mysqli_stmt_execute($check_stmt);

            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) == 1) {

                mysqli_stmt_bind_result(
                    $check_stmt,
                    $checked_sip_id,
                    $sip_amount
                );

                mysqli_stmt_fetch($check_stmt);

                mysqli_stmt_close($check_stmt);


                /*
                 If amount is empty or invalid,
                 use SIP amount automatically.
                */

                if ($amount == "" || $amount <= 0) {
                    $amount = $sip_amount;
                }


                /*
                 Payment date is NULL when
                 payment is not yet completed.
                */

                if ($payment_date == "") {

                    $payment_date_value = null;

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO payments
                        (inv_id, sip_id, amount, due_date, payment_date, status)
                        VALUES (?, ?, ?, ?, NULL, ?)"
                    );

                    if ($stmt) {

                        mysqli_stmt_bind_param(
                            $stmt,
                            "iidss",
                            $inv_id,
                            $sip_id,
                            $amount,
                            $due_date,
                            $status
                        );

                    }

                } else {

                    $payment_date_value = $payment_date;

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO payments
                        (inv_id, sip_id, amount, due_date, payment_date, status)
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    if ($stmt) {

                        mysqli_stmt_bind_param(
                            $stmt,
                            "iidsss",
                            $inv_id,
                            $sip_id,
                            $amount,
                            $due_date,
                            $payment_date_value,
                            $status
                        );

                    }
                }


                if (isset($stmt) && $stmt) {

                    if (mysqli_stmt_execute($stmt)) {

                        mysqli_stmt_close($stmt);

                        header("Location: payment_tracking.php");
                        exit;

                    } else {

                        $error = "Unable to save payment.";

                        mysqli_stmt_close($stmt);
                    }

                } else {

                    $error = "Database error. Please try again.";
                }

            } else {

                mysqli_stmt_close($check_stmt);

                $error = "Invalid SIP selected.";
            }

        } else {

            $error = "Database error. Please try again.";
        }
    }
}


/* -----------------------------
   GET INVESTORS
----------------------------- */

$investor_sql = "
    SELECT inv_id, name
    FROM investors
    ORDER BY name ASC
";

$investor_result = mysqli_query($conn, $investor_sql);


/* -----------------------------
   GET SIP RECORDS FOR PAYMENT
----------------------------- */

$sip_sql = "
    SELECT
        s.sip_id,
        s.inv_id,
        i.name AS investor_name,
        s.scheme_name,
        s.amount,
        s.frequency,
        s.investment_date
    FROM sip_management s
    INNER JOIN investors i
        ON s.inv_id = i.inv_id
    WHERE s.investment_type = 'SIP'
    AND s.status = 'Active'
    ORDER BY s.sip_id DESC
";

$sip_result = mysqli_query($conn, $sip_sql);


/* -----------------------------
   GET PAYMENT RECORDS
----------------------------- */

$payment_sql = "
    SELECT
        p.payment_id,
        p.inv_id,
        p.sip_id,
        i.name AS investor_name,
        s.scheme_name,
        p.amount,
        p.due_date,
        p.payment_date,
        p.status
    FROM payments p

    INNER JOIN investors i
        ON p.inv_id = i.inv_id

    INNER JOIN sip_management s
        ON p.sip_id = s.sip_id

    ORDER BY p.payment_id DESC
";

$payment_result = mysqli_query($conn, $payment_sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Payment Tracking - Grow More</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <h2>Payment Tracking</h2>

    <?php if ($error != "") { ?>

        <p>
            <?php echo htmlspecialchars($error); ?>
        </p>

    <?php } ?>


    <!-- =========================
         ADD PAYMENT
    ========================== -->

    <h3>Add Payment</h3>

    <form method="POST" action="">


        <label for="inv_id">
            Investor
        </label>

        <select
            id="inv_id"
            name="inv_id"
            required
        >

            <option value="">
                Select Investor
            </option>

            <?php

            if ($investor_result &&
                mysqli_num_rows($investor_result) > 0) {

                while ($investor = mysqli_fetch_assoc($investor_result)) {

            ?>

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

                    echo " - " .
                    htmlspecialchars($investor['name']);

                    ?>

                </option>

            <?php

                }

            }

            ?>

        </select>


        <label for="sip_id">
            SIP
        </label>

        <select
            id="sip_id"
            name="sip_id"
            required
        >

            <option value="">
                Select SIP
            </option>

            <?php

            if ($sip_result &&
                mysqli_num_rows($sip_result) > 0) {

                while ($sip = mysqli_fetch_assoc($sip_result)) {

            ?>

                <option
                    value="<?php echo $sip['sip_id']; ?>"
                    <?php
                    if ($sip_id == $sip['sip_id']) {
                        echo "selected";
                    }
                    ?>
                >

                    <?php

                    echo "SIP" .
                    str_pad(
                        $sip['sip_id'],
                        3,
                        "0",
                        STR_PAD_LEFT
                    );

                    echo " - " .
                    htmlspecialchars($sip['investor_name']);

                    echo " - " .
                    htmlspecialchars($sip['scheme_name']);

                    echo " - ₹" .
                    number_format(
                        $sip['amount'],
                        2
                    );

                    ?>

                </option>

            <?php

                }

            } else {

            ?>

                <option value="">
                    No active SIP found
                </option>

            <?php

            }

            ?>

        </select>


        <label for="amount">
            Amount
        </label>

        <input
            type="number"
            id="amount"
            name="amount"
            value="<?php echo htmlspecialchars($amount); ?>"
            step="0.01"
            min="0"
            required
        >


        <label for="due_date">
            Due Date
        </label>

        <input
            type="date"
            id="due_date"
            name="due_date"
            value="<?php echo htmlspecialchars($due_date); ?>"
            required
        >


        <label for="payment_date">
            Payment Date
        </label>

        <input
            type="date"
            id="payment_date"
            name="payment_date"
            value="<?php echo htmlspecialchars($payment_date); ?>"
        >


        <label for="status">
            Status
        </label>

        <select
            id="status"
            name="status"
            required
        >

            <option
                value="Paid"
                <?php
                if ($status == "Paid") {
                    echo "selected";
                }
                ?>
            >
                Paid
            </option>

            <option
                value="Pending"
                <?php
                if ($status == "Pending") {
                    echo "selected";
                }
                ?>
            >
                Pending
            </option>

            <option
                value="Failed"
                <?php
                if ($status == "Failed") {
                    echo "selected";
                }
                ?>
            >
                Failed
            </option>

        </select>


        <input
            type="submit"
            name="save_payment"
            value="Save Payment"
        >

    </form>


    <br>

    <a href="dashboard.php">
        Back to Dashboard
    </a>


    <hr>


    <!-- =========================
         PAYMENT LIST
    ========================== -->

    <h3>Payment List</h3>


    <?php

    if ($payment_result &&
        mysqli_num_rows($payment_result) > 0) {

    ?>

        <table>

            <tr>

                <th>Payment_ID</th>
                <th>Inv_ID</th>
                <th>SIP_ID</th>
                <th>Investor Name</th>
                <th>Scheme Name</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Payment Date</th>
                <th>Status</th>

            </tr>


            <?php

            while ($payment = mysqli_fetch_assoc($payment_result)) {

            ?>

                <tr>

                    <td>

                        <?php

                        echo "PAY" .
                        str_pad(
                            $payment['payment_id'],
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
                            $payment['inv_id'],
                            3,
                            "0",
                            STR_PAD_LEFT
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo "SIP" .
                        str_pad(
                            $payment['sip_id'],
                            3,
                            "0",
                            STR_PAD_LEFT
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $payment['investor_name']
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $payment['scheme_name']
                        );

                        ?>

                    </td>


                    <td>

                        ₹<?php

                        echo number_format(
                            $payment['amount'],
                            2
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $payment['due_date']
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        if ($payment['payment_date'] != null &&
                            $payment['payment_date'] != "") {

                            echo htmlspecialchars(
                                $payment['payment_date']
                            );

                        } else {

                            echo "-";

                        }

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $payment['status']
                        );

                        ?>

                    </td>


                   

                </tr>

            <?php

            }

            ?>

        </table>


    <?php

    } else {

    ?>

        <p>
            No payment records found.
        </p>

    <?php

    }

    ?>


    <br>

    <a href="dashboard.php">
        Back to Dashboard
    </a>

</div>

</body>

</html>


