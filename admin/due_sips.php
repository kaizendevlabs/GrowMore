<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../config/database.php";


/*
|--------------------------------------------------------------------------
| DATE SETTINGS
|--------------------------------------------------------------------------
*/

$today = date("Y-m-d");

$alert_date = date(
    "Y-m-d",
    strtotime("+2 days")
);


/*
|--------------------------------------------------------------------------
| FUNCTION
|--------------------------------------------------------------------------
| Add months while keeping the original SIP day.
|
| Example:
| 31-01-2026 + 1 month = 28-02-2026
| 31-01-2026 + 2 months = 31-03-2026
|
|--------------------------------------------------------------------------
*/

function addMonthsToDate($date, $months)
{
    $date_object = new DateTime($date);

    $original_day = (int)$date_object->format("d");

    $year = (int)$date_object->format("Y");

    $month = (int)$date_object->format("m");

    $total_months = ($year * 12) + ($month - 1) + $months;

    $new_year = intdiv($total_months, 12);

    $new_month = ($total_months % 12) + 1;

    $last_day = cal_days_in_month(
        CAL_GREGORIAN,
        $new_month,
        $new_year
    );

    $new_day = min(
        $original_day,
        $last_day
    );

    return sprintf(
        "%04d-%02d-%02d",
        $new_year,
        $new_month,
        $new_day
    );
}


/*
|--------------------------------------------------------------------------
| GET ACTIVE SIP RECORDS
|--------------------------------------------------------------------------
|
| Only:
| 1. Active SIPs
| 2. Investment Type = SIP
| 3. Monthly / Quarterly
|
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        s.sip_id,
        s.inv_id,
        i.name AS investor_name,
        s.scheme_name,
        s.amount,
        s.frequency,
        s.investment_date,
        s.status

    FROM sip_management s

    INNER JOIN investors i
        ON s.inv_id = i.inv_id

    WHERE s.investment_type = 'SIP'
    AND s.frequency IN ('Monthly', 'Quarterly')
    AND s.status = 'Active'

    ORDER BY s.investment_date ASC
";

$result = mysqli_query($conn, $sql);


/*
|--------------------------------------------------------------------------
| GET PAYMENT RECORDS
|--------------------------------------------------------------------------
|
| Latest payment status for each SIP and due date is stored
| in the array below.
|
|--------------------------------------------------------------------------
*/

$payment_sql = "
    SELECT
        payment_id,
        sip_id,
        due_date,
        status

    FROM payments

    ORDER BY payment_id DESC
";

$payment_result = mysqli_query(
    $conn,
    $payment_sql
);


$payment_statuses = array();


if ($payment_result) {

    while ($payment = mysqli_fetch_assoc($payment_result)) {

        $sip_id = $payment['sip_id'];

        $due_date = $payment['due_date'];

        /*
        --------------------------------------------------------------
        Since records are ordered DESC by payment_id,
        the first record for the same SIP and due date
        is the latest payment record.
        --------------------------------------------------------------
        */

        if (
            !isset(
                $payment_statuses[$sip_id][$due_date]
            )
        ) {

            $payment_statuses[$sip_id][$due_date]
                = $payment['status'];
        }
    }
}

?>


<!DOCTYPE html>
<html>

<head>

    <title>Due SIPs - Grow More</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <h2>Due SIPs</h2>

    <p>
        SIP payment reminders are shown up to 2 days before
        the next SIP due date.
    </p>

    <hr>


    <?php

    $due_count = 0;

    $alert_count = 0;

    $overdue_count = 0;

    ?>


    <?php if ($result && mysqli_num_rows($result) > 0) { ?>


        <table>

            <tr>

                <th>Inv_ID</th>

                <th>Investor Name</th>

                <th>Scheme Name</th>

                <th>Amount</th>

                <th>Frequency</th>

                <th>SIP Date</th>

                <th>Payment Status</th>

                <th>Alert</th>

            </tr>


            <?php

            while ($row = mysqli_fetch_assoc($result)) {

                $sip_id = (int)$row['sip_id'];

                $investment_date = $row['investment_date'];

                $frequency = $row['frequency'];


                /*
                ----------------------------------------------------------
                Determine recurrence interval
                ----------------------------------------------------------
                */

                if ($frequency == "Monthly") {

                    $months_interval = 1;

                } else {

                    $months_interval = 3;
                }


                /*
                ----------------------------------------------------------
                Find the first unpaid due date.
                ----------------------------------------------------------
                */

                $due_date = $investment_date;

                $found_due_date = false;

                $payment_status = null;


                /*
                ----------------------------------------------------------
                Safety limit:
                Maximum 240 cycles = 20 years for Monthly SIP.
                ----------------------------------------------------------
                */

                for ($cycle = 0; $cycle < 240; $cycle++) {

                    /*
                    ------------------------------------------------------
                    If this due date is after alert date,
                    there is nothing to show yet.
                    ------------------------------------------------------
                    */

                    if ($due_date > $alert_date) {

                        break;
                    }


                    /*
                    ------------------------------------------------------
                    Get latest payment status for this SIP and due date.
                    ------------------------------------------------------
                    */

                    if (
                        isset(
                            $payment_statuses[$sip_id][$due_date]
                        )
                    ) {

                        $payment_status =
                            $payment_statuses[$sip_id][$due_date];

                    } else {

                        $payment_status = null;
                    }


                    /*
                    ------------------------------------------------------
                    If payment is Paid, move to next SIP cycle.
                    ------------------------------------------------------
                    */

                    if ($payment_status == "Paid") {

                        $due_date = addMonthsToDate(
                            $investment_date,
                            ($cycle + 1) * $months_interval
                        );

                        continue;
                    }


                    /*
                    ------------------------------------------------------
                    Pending, Failed or no payment:
                    this is the current unpaid due date.
                    ------------------------------------------------------
                    */

                    $found_due_date = true;

                    break;
                }


                /*
                ----------------------------------------------------------
                If no due date is found within alert period,
                skip this SIP.
                ----------------------------------------------------------
                */

                if (!$found_due_date) {

                    continue;
                }


                /*
                ----------------------------------------------------------
                Calculate difference between today and due date.
                ----------------------------------------------------------
                */

                $today_timestamp = strtotime($today);

                $due_timestamp = strtotime($due_date);

                $difference = floor(
                    ($due_timestamp - $today_timestamp) / 86400
                );


                /*
                ----------------------------------------------------------
                Show only:
                - Overdue
                - Due today
                - Due tomorrow
                - Due in 2 days
                ----------------------------------------------------------
                */

                if ($difference > 2) {

                    continue;
                }


                $due_count++;


                /*
                ----------------------------------------------------------
                Alert message
                ----------------------------------------------------------
                */

                if ($difference == 2) {

                    $alert_message = "Due in 2 days";

                    $alert_count++;

                } elseif ($difference == 1) {

                    $alert_message = "Due tomorrow";

                    $alert_count++;

                } elseif ($difference == 0) {

                    $alert_message = "Due today";

                    $alert_count++;

                } else {

                    $alert_message = "Overdue";

                    $overdue_count++;
                }


                /*
                ----------------------------------------------------------
                Payment status display
                ----------------------------------------------------------
                */

                if ($payment_status == "Pending") {

                    $display_payment_status = "Pending";

                } elseif ($payment_status == "Failed") {

                    $display_payment_status = "Failed";

                } else {

                    $display_payment_status = "Not Paid";
                }

            ?>


                <tr>


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
                            $row['scheme_name']
                        );

                        ?>

                    </td>


                    <td>

                        ₹<?php

                        echo number_format(
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
                            $due_date
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $display_payment_status
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $alert_message
                        );

                        ?>

                    </td>


                </tr>


            <?php

            }

            ?>


            <?php if ($due_count == 0) { ?>

                <tr>

                    <td colspan="8">

                        No due SIPs found.

                    </td>

                </tr>

            <?php } ?>


        </table>


    <?php } else { ?>


        <p>

            No active SIPs found.

        </p>


    <?php } ?>


    <br>


    <h3>Due SIP Summary</h3>


    <p>

        Total Due / Upcoming SIPs:

        <?php

        echo $due_count;

        ?>

    </p>


    <p>

        SIP Alerts:

        <?php

        echo $alert_count;

        ?>

    </p>


    <p>

        Overdue SIPs:

        <?php

        echo $overdue_count;

        ?>

    </p>


    <br>


    <a href="dashboard.php">

        Back to Dashboard

    </a>


    <br><br>


    <a href="payment_tracking.php">

        Go to Payments

    </a>


</div>

</body>

</html>