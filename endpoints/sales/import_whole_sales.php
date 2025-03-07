<?php
require '../../vendor/autoload.php';
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode($_POST['data'], true);

    $businessInfo = $data['businessInfo'];
    $branches = $data['branches'];
    $salesData = $data['salesData'];

    preg_match('/ID:(\d+)/', $businessInfo['name'], $matches);
    $business_id = $matches[1] ?? 0;

    // Prepare the SQL statement for inserting sales data
    $stmt = $conn->prepare("
        INSERT INTO sales (quantity, total_sales, date, product_id, branch_id, type)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    foreach ($salesData as $sale) {
        // Skip if amount_sold is 0
        if ($sale['amount_sold'] == 0) {
            continue;
        }

        preg_match('/ID:(\d+)/', $sale['product'], $matches);
        $product_id = $matches[1] ?? 0;

        preg_match('/ID:(\d+)/', $sale['business_branch'], $matches);
        $branch_id = $matches[1] ?? 0;

        // Determine the type and branch ID
        $type = ($branch_id > 0) ? 'branch' : 'business';
        $branch_id = ($branch_id > 0) ? $branch_id : 0;

        // Calculate total_sales as price * amount_sold
        $total_sales = $sale['price'] * $sale['amount_sold'];

        // Bind the parameters and execute the statement
        $stmt->bind_param(
            'sssiis',
            $sale['amount_sold'],
            $total_sales,
            $sale['date'],
            $product_id,
            $branch_id,
            $type
        );

        if (!$stmt->execute()) {
            die("Error executing statement: " . $stmt->error);
        }
    }

    $stmt->close();
    $conn->close();

    // Redirect to tracksales.php with a success parameter
    header("Location: ../../owner/tracksales.php?imported=true");
    exit();
} else {
    // Redirect to tracksales.php with an error parameter
    header("Location: ../../owner/tracksales.php?imported=false");
    exit();
}
?>