<?php

require_once "/var/www/portal.twe.tech/includes/inc_all_reports.php";

validateAccountantRole();

if (isset($_GET['year'])) {
    $year = intval($_GET['year']);
} else {
    $year = date('Y');
}

$sql_expense_years = mysqli_query($mysqli, "SELECT DISTINCT YEAR(expense_date) AS expense_year FROM expenses WHERE expense_category_id > 0 ORDER BY expense_year DESC");

$sql_categories = mysqli_query($mysqli, "SELECT * FROM categories WHERE category_type = 'Expense' ORDER BY category_name ASC");

?>

<div class="card">
    <div class="card-header py-2">
        <h3 class="card-title mt-2"><i class="fas fa-fw fa-coins mr-2"></i>Expense Summary</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-label-primary d-print-none" onclick="window.print();"><i class="fas fa-fw fa-print mr-2"></i>Print</button>
        </div>
    </div>
    <div class="card-body">
        <form class="mb-3">
            <select onchange="this.form.submit()" class="form-control" name="year">
                <?php

                while ($row = mysqli_fetch_array($sql_expense_years)) {
                    $expense_year = $row['expense_year'];
                    ?>
                    <option <?php if ($year == $expense_year) { ?> selected <?php } ?> > <?= $expense_year; ?></option>

                <?php } ?>

            </select>
        </form>

        <canvas id="cashFlow" width="100%" height="20"></canvas>

        <div class="card-datatable table-responsive container-fluid  pt-0">            <table id=responsive class="responsive table table-striped">
                <thead class="text-dark">
                <tr>
                    <th>Category</th>
                    <th class="text-right">January</th>
                    <th class="text-right">February</th>
                    <th class="text-right">March</th>
                    <th class="text-right">April</th>
                    <th class="text-right">May</th>
                    <th class="text-right">June</th>
                    <th class="text-right">July</th>
                    <th class="text-right">August</th>
                    <th class="text-right">September</th>
                    <th class="text-right">October</th>
                    <th class="text-right">November</th>
                    <th class="text-right">December</th>
                    <th class="text-right">Total</th>
                </tr>
                </thead>
                <tbody>
                <?php
                while ($row = mysqli_fetch_array($sql_categories)) {
                    $category_id = intval($row['category_id']);
                    $category_name = nullable_htmlentities($row['category_name']);
                    ?>

                    <tr>
                        <td><?= $category_name; ?></td>

                        <?php

                        $total_expense_for_all_months = 0;
                        for ($month = 1; $month<=12; $month++) {
                            $sql_expenses = mysqli_query($mysqli, "SELECT SUM(expense_amount) AS expense_amount_for_month FROM expenses WHERE expense_category_id = $category_id AND YEAR(expense_date) = $year AND MONTH(expense_date) = $month");
                            $row = mysqli_fetch_array($sql_expenses);
                            $expense_amount_for_month = floatval($row['expense_amount_for_month']);
                            $total_expense_for_all_months = $expense_amount_for_month + $total_expense_for_all_months;


                            ?>
                            <td class="text-right"><a class="text-dark" href="expenses.php?q=<?= $category_name; ?>&dtf=<?= "$year-$month"; ?>-01&dtt=<?= "$year-$month"; ?>-31"><?= numfmt_format_currency($currency_format, $expense_amount_for_month, $company_currency); ?></a></td>

                        <?php } ?>

                        <th class="text-right"><a class="text-dark" href="expenses.php?q=<?= $category_name; ?>&dtf=<?= $year; ?>-01-01&dtt=<?= $year; ?>-12-31"><?= numfmt_format_currency($currency_format, $total_expense_for_all_months, $company_currency); ?></a></th>
                    </tr>

                <?php } ?>

                <tr>
                    <th>Total</th>
                    <?php

                    for ($month = 1; $month<=12; $month++) {
                        $sql_expenses = mysqli_query($mysqli, "SELECT SUM(expense_amount) AS expense_total_amount_for_month FROM expenses WHERE YEAR(expense_date) = $year AND MONTH(expense_date) = $month AND expense_vendor_id > 0");
                        $row = mysqli_fetch_array($sql_expenses);
                        $expense_total_amount_for_month = floatval($row['expense_total_amount_for_month']);
                        $total_expense_for_all_months = $expense_total_amount_for_month + $total_expense_for_all_months;


                        ?>

                        <th class="text-right"><a class="text-dark" href="expenses.php?dtf=<?= "$year-$month"; ?>-01&dtt=<?= "$year-$month"; ?>-31"><?= numfmt_format_currency($currency_format, $expense_total_amount_for_month, $company_currency); ?></a></th>

                    <?php } ?>

                    <th class="text-right"><a class="text-dark" href="expenses.php?dtf=<?= $year; ?>-01-01&dtt=<?= $year; ?>-12-31"><?= numfmt_format_currency($currency_format, $total_expense_for_all_months, $company_currency); ?></th>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '/var/www/portal.twe.tech/includes/footer.php';
 ?>

<script>
    // Set new default font family and font color to mimic Bootstrap's default styling
    Chart.defaults.global.defaultFontFamily = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.global.defaultFontColor = '#292b2c';

    var ctx = document.getElementById("cashFlow");
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            datasets: [{
                label: "Expense",
                lineTension: 0.3,
                fill: false,
                borderColor: "#dc3545",
                pointBackgroundColor: "#dc3545",
                pointBorderColor: "#dc3545",
                pointHoverRadius: 5,
                pointHoverBackgroundColor: "#dc3545",
                pointHitRadius: 50,
                pointBorderWidth: 2,
                data: [
                    <?php

                    $largest_expense_month = 0;

                    for ($month = 1; $month<=12; $month++) {
                    $sql_expenses = mysqli_query($mysqli, "SELECT SUM(expense_amount) AS expense_amount_for_month FROM expenses WHERE YEAR(expense_date) = $year AND MONTH(expense_date) = $month AND expense_vendor_id > 0");
                    $row = mysqli_fetch_array($sql_expenses);
                    $expenses_for_month = floatval($row['expense_amount_for_month']);

                    if ($expenses_for_month > 0 && $expenses_for_month > $largest_expense_month) {
                        $largest_expense_month = $expenses_for_month;
                    }

                    echo "$expenses_for_month,";

                    } ?>

                ],
            }],
        },
        options: {
            scales: {
                xAxes: [{
                    time: {
                        unit: 'date'
                    },
                    gridLines: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 12
                    }
                }],
                yAxes: [{
                    ticks: {
                        min: 0,
                        max: <?php $max = max(1000, $largest_expense_month, $largest_income_month, $largest_invoice_month); echo roundUpToNearestMultiple($max); ?>,
                        maxTicksLimit: 5
                    },
                    gridLines: {
                        color: "rgba(0, 0, 0, .125)",
                    }
                }],
            },
            legend: {
                display: false
            }
        }
    });

</script>
