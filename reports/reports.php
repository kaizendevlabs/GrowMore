<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../config/database.php";

$report = "";

if (isset($_GET['report'])) {
    $report = $_GET['report'];
}


/*
|--------------------------------------------------------------------------
| FUNCTION
|--------------------------------------------------------------------------
| Add months to a date while keeping the original SIP day.
|
| Monthly:
| 20-09-2026
| 20-10-2026
| 20-11-2026
|
| Quarterly:
| 20-09-2026
| 20-12-2026
| 20-03-2027
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
| INVESTOR REPORT
|--------------------------------------------------------------------------
*/

$investor_result = null;

if ($report == "investors") {

    $sql = "
        SELECT
            inv_id,
            name,
            mobile,
            email
        FROM investors
        ORDER BY inv_id DESC
    ";

    $investor_result = mysqli_query(
        $conn,
        $sql
    );
}


/*
|--------------------------------------------------------------------------
| SIP REPORT
|--------------------------------------------------------------------------
*/

$sip_result = null;

if ($report == "sip") {

    $sql = "
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
        $sql
    );
}


/*
|--------------------------------------------------------------------------
| PAYMENT REPORT
|--------------------------------------------------------------------------
*/

$payment_result = null;

if ($report == "payments") {

    $sql = "
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

    $payment_result = mysqli_query(
        $conn,
        $sql
    );
}


/*
|--------------------------------------------------------------------------
| DUE SIP REPORT
|--------------------------------------------------------------------------
*/

$due_result = null;

$payment_statuses = array();

if ($report == "due_sips") {

    $today = date("Y-m-d");

    $alert_date = date(
        "Y-m-d",
        strtotime("+2 days")
    );


    /*
    ----------------------------------------------------------------------
    Get all active SIP records
    ----------------------------------------------------------------------
    */

    $due_sql = "
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

    $due_result = mysqli_query(
        $conn,
        $due_sql
    );


    /*
    ----------------------------------------------------------------------
    Get payment records
    ----------------------------------------------------------------------
    */

    $payment_status_sql = "
        SELECT
            payment_id,
            sip_id,
            due_date,
            status

        FROM payments

        ORDER BY payment_id DESC
    ";

    $payment_status_result = mysqli_query(
        $conn,
        $payment_status_sql
    );


    if ($payment_status_result) {

        while (
            $payment = mysqli_fetch_assoc(
                $payment_status_result
            )
        ) {

            $sip_id = $payment['sip_id'];

            $due_date = $payment['due_date'];


            /*
            --------------------------------------------------------------
            First record is the latest payment record because
            payment_id is ordered DESC.
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
}


/*
|--------------------------------------------------------------------------
| PENDING / FAILED PAYMENT REPORT
|--------------------------------------------------------------------------
*/

$pending_failed_result = null;

if ($report == "pending_failed") {

    $sql = "
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

        WHERE p.status IN ('Pending', 'Failed')

        ORDER BY p.due_date ASC
    ";

    $pending_failed_result = mysqli_query(
        $conn,
        $sql
    );
}

?>


<!DOCTYPE html>
<html>

<head>

    <title>Reports - Grow More</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <h2>Reports</h2>

    <p>
        Select a report to view the required records.
    </p>

    <hr>


    <!--
    ============================================================
    REPORT MENU
    ============================================================
    -->

    <h3>Available Reports</h3>


    <p>

        <a href="reports.php?report=investors">

            Investor Report

        </a>

    </p>


    <p>

        <a href="reports.php?report=sip">

            SIP Report

        </a>

    </p>


    <p>

        <a href="reports.php?report=payments">

            Payment Report

        </a>

    </p>


    <p>

        <a href="reports.php?report=due_sips">

            Due SIP Report

        </a>

    </p>


    <p>

        <a href="reports.php?report=pending_failed">

            Pending / Failed Payment Report

        </a>

    </p>


    <hr>


    <!--
    ============================================================
    INVESTOR REPORT
    ============================================================
    -->

    <?php if ($report == "investors") { ?>

        <h3>Investor Report</h3>


        <?php

        if (
            $investor_result &&
            mysqli_num_rows($investor_result) > 0
        ) {

        ?>

            <table>

                <tr>

                    <th>Inv_ID</th>

                    <th>Name</th>

                    <th>Mobile Number</th>

                    <th>Email</th>

                </tr>


                <?php

                while (
                    $row = mysqli_fetch_assoc(
                        $investor_result
                    )
                ) {

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
                                $row['name']
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['mobile']
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row['email']
                            );

                            ?>

                        </td>

                    </tr>


                <?php } ?>


            </table>


        <?php } else { ?>


            <p>

                No investor records found.

            </p>


        <?php } ?>


    <?php } ?>


    <!--
    ============================================================
    SIP REPORT
    ============================================================
    -->

    <?php if ($report == "sip") { ?>

        <h3>SIP Report</h3>


        <?php

        if (
            $sip_result &&
            mysqli_num_rows($sip_result) > 0
        ) {

        ?>

            <table>

                <tr>

                    <th>SIP_ID</th>

                    <th>Inv_ID</th>

                    <th>Investor Name</th>

                    <th>Investment Type</th>

                    <th>Scheme Name</th>

                    <th>Amount</th>

                    <th>Frequency</th>

                    <th>Investment Date</th>

                    <th>Status</th>

                </tr>


                <?php

                while (
                    $row = mysqli_fetch_assoc(
                        $sip_result
                    )
                ) {

                ?>

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


            <p>

                No SIP records found.

            </p>


        <?php } ?>


    <?php } ?>


    <!--
    ============================================================
    PAYMENT REPORT
    ============================================================
    -->

    <?php if ($report == "payments") { ?>

        <h3>Payment Report</h3>


        <?php

        if (
            $payment_result &&
            mysqli_num_rows($payment_result) > 0
        ) {

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

                while (
                    $row = mysqli_fetch_assoc(
                        $payment_result
                    )
                ) {

                ?>

                    <tr>

                        <td>

                            <?php

                            echo "PAY" .
                            str_pad(
                                $row['payment_id'],
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
                                $row['due_date']
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            if (
                                $row['payment_date'] != null &&
                                $row['payment_date'] != ""
                            ) {

                                echo htmlspecialchars(
                                    $row['payment_date']
                                );

                            } else {

                                echo "-";
                            }

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


            <p>

                No payment records found.

            </p>


        <?php } ?>


    <?php } ?>


    <!--
    ============================================================
    DUE SIP REPORT
    ============================================================
    -->

    <?php if ($report == "due_sips") { ?>

        <h3>Due SIP Report</h3>


        <?php

        $due_count = 0;


        if (
            $due_result &&
            mysqli_num_rows($due_result) > 0
        ) {

        ?>

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

                while (
                    $row = mysqli_fetch_assoc(
                        $due_result
                    )
                ) {


                    $sip_id = (int)$row['sip_id'];

                    $investment_date =
                        $row['investment_date'];

                    $frequency =
                        $row['frequency'];


                    /*
                    ------------------------------------------------------
                    Determine recurrence interval
                    ------------------------------------------------------
                    */

                    if ($frequency == "Monthly") {

                        $months_interval = 1;

                    } else {

                        $months_interval = 3;
                    }


                    /*
                    ------------------------------------------------------
                    Find first unpaid due date
                    ------------------------------------------------------
                    */

                    $due_date =
                        $investment_date;

                    $found_due_date = false;

                    $payment_status = null;


                    /*
                    ------------------------------------------------------
                    Maximum 240 cycles = 20 years
                    ------------------------------------------------------
                    */

                    for (
                        $cycle = 0;
                        $cycle < 240;
                        $cycle++
                    ) {


                        /*
                        --------------------------------------------------
                        Stop when due date is more than 2 days away.
                        --------------------------------------------------
                        */

                        if (
                            $due_date >
                            $alert_date
                        ) {

                            break;
                        }


                        /*
                        --------------------------------------------------
                        Get latest payment status
                        --------------------------------------------------
                        */

                        if (
                            isset(
                                $payment_statuses
                                [$sip_id]
                                [$due_date]
                            )
                        ) {

                            $payment_status =
                                $payment_statuses
                                [$sip_id]
                                [$due_date];

                        } else {

                            $payment_status = null;
                        }


                        /*
                        --------------------------------------------------
                        Paid:
                        move to next cycle.
                        --------------------------------------------------
                        */

                        if (
                            $payment_status ==
                            "Paid"
                        ) {

                            $due_date =
                                addMonthsToDate(
                                    $investment_date,
                                    (
                                        $cycle + 1
                                    ) *
                                    $months_interval
                                );

                            continue;
                        }


                        /*
                        --------------------------------------------------
                        Pending / Failed / No payment
                        --------------------------------------------------
                        */

                        $found_due_date = true;

                        break;
                    }


                    /*
                    ------------------------------------------------------
                    No due date found
                    ------------------------------------------------------
                    */

                    if (!$found_due_date) {

                        continue;
                    }


                    /*
                    ------------------------------------------------------
                    Calculate date difference
                    ------------------------------------------------------
                    */

                    $today_timestamp =
                        strtotime($today);

                    $due_timestamp =
                        strtotime($due_date);

                    $difference = floor(
                        (
                            $due_timestamp -
                            $today_timestamp
                        ) / 86400
                    );


                    /*
                    ------------------------------------------------------
                    Only show overdue / today / tomorrow / 2 days
                    ------------------------------------------------------
                    */

                    if ($difference > 2) {

                        continue;
                    }


                    $due_count++;


                    /*
                    ------------------------------------------------------
                    Alert message
                    ------------------------------------------------------
                    */

                    if ($difference == 2) {

                        $alert =
                            "Due in 2 days";

                    } elseif ($difference == 1) {

                        $alert =
                            "Due tomorrow";

                    } elseif ($difference == 0) {

                        $alert =
                            "Due today";

                    } else {

                        $alert =
                            "Overdue";
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

                            if (
                                $payment_status != null
                            ) {

                                echo htmlspecialchars(
                                    $payment_status
                                );

                            } else {

                                echo "Not Paid";
                            }

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $alert
                            );

                            ?>

                        </td>


                    </tr>


                <?php } ?>


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

                No active SIP records found.

            </p>


        <?php } ?>


    <?php } ?>


    <!--
    ============================================================
    PENDING / FAILED PAYMENT REPORT
    ============================================================
    -->

    <?php if ($report == "pending_failed") { ?>

        <h3>
            Pending / Failed Payment Report
        </h3>


        <?php

        if (
            $pending_failed_result &&
            mysqli_num_rows(
                $pending_failed_result
            ) > 0
        ) {

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

                while (
                    $row = mysqli_fetch_assoc(
                        $pending_failed_result
                    )
                ) {

                ?>

                    <tr>


                        <td>

                            <?php

                            echo "PAY" .
                            str_pad(
                                $row['payment_id'],
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
                                $row['due_date']
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            if (
                                $row['payment_date'] != null &&
                                $row['payment_date'] != ""
                            ) {

                                echo htmlspecialchars(
                                    $row['payment_date']
                                );

                            } else {

                                echo "-";
                            }

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


            <p>

                No pending or failed payment records found.

            </p>


        <?php } ?>


    <?php } ?>


    <br>


    <a href="../admin/dashboard.php">

        Back to Dashboard

    </a>


</div>

</body>

</html>