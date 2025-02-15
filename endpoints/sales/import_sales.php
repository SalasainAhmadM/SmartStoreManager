<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
validateSession('owner');

// Set timezone
date_default_timezone_set('Asia/Manila');

// Decode the JSON data from the POST request
$data = json_decode($_POST['data'], true);

// Extract product_id from the product value
$productInfo = $data['businessInfo']['product'];
preg_match('/ID:(\d+)/', $productInfo, $productMatches);
$product_id = $productMatches[1] ?? 0; // Default to 0 if no ID is found

// Extract branch_id from the branch value
$branchInfo = $data['businessInfo']['branch'];
preg_match('/ID:(\d+)/', $branchInfo, $branchMatches);
$branch_id = $branchMatches[1] ?? 0; // Default to 0 if no branch is found

// Determine the type based on the branch_id
$type = ($branch_id == 0) ? 'business' : 'branch';

// Prepare the SQL statement for inserting sales data
$query = "
    INSERT INTO `sales` (
        `quantity`, 
        `total_sales`, 
        `date`, 
        `created_at`, 
        `product_id`, 
        `branch_id`, 
        `type`
    ) VALUES (
        ?, 
        ?, 
        ?, 
        NOW(), 
        ?, 
        ?, 
        ?
    )
";

// Prepare the statement
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

// Loop through the sales data and insert each record
foreach ($data['salesData'] as $sale) {
    $amountSold = $sale['amount_sold'];
    $totalSales = $amountSold * $data['businessInfo']['price'];
    $date = $sale['date'];

    // Skip rows where both Amount Sold and Total Sales are 0
    if ($amountSold == 0 && $totalSales == 0) {
        continue;
    }

    // Bind parameters and execute the statement
    $stmt->bind_param(
        'sssiis', // Types: s = string, i = integer
        $amountSold,
        $totalSales,
        $date,
        $product_id,
        $branch_id,
        $type
    );

    if (!$stmt->execute()) {
        // Handle SQL execution errors
        die("Error executing statement: " . $stmt->error);
    }
}

// Close the statement and connection
$stmt->close();
$conn->close();

// Redirect to tracksales.php with a success parameter
header("Location: ../../owner/tracksales.php?imported=true");
exit;
?>