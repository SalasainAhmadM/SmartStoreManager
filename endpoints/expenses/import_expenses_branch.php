<?php
require_once '../../conn/conn.php';
require_once '../../conn/auth.php';

// Set the default timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

session_start();
validateSession('owner');

$owner_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
    $expenseData = json_decode($_POST['data'], true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($expenseData)) {
        foreach ($expenseData as $expense) {
            $expenseType = $expense['expense_type'];
            $amount = $expense['amount'];
            $category = $expense['category'];
            $business = $expense['business'];
            $branch = $expense['branch'];
            $description = $expense['description'];
            $date = $expense['date'];

            // Skip if amount is zero
            if ($amount == 0) {
                continue;
            }

            $category = strtolower($category[0]) . substr($category, 1);

            if ($category === 'business') {
                $category_id = extractIdFromName($business);
            } elseif ($category === 'branch') {
                $category_id = extractIdFromName($branch);
            } else {
                continue;
            }

            $month = date('m', strtotime($date));
            $created_at = date('Y-m-d H:i:s');

            // Insert into database
            $query = "INSERT INTO expenses (
                expense_type, amount, description, created_at, owner_id, category_id, category, month
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "ssssiisi",
                $expenseType,
                $amount,
                $description,
                $created_at,
                $owner_id,
                $category_id,
                $category,
                $month
            );

            if (!$stmt->execute()) {
                header("Location: ../../owner/manageexpenses_branch.php?imported=false&error=" . urlencode($stmt->error));
                exit();
            }
        }

        header("Location: ../../owner/manageexpenses_branch.php?imported=true");
        exit();
    } else {
        header("Location: ../../owner/manageexpenses_branch.php?imported=false&error=Invalid data format");
        exit();
    }
} else {
    header("Location: ../../owner/manageexpenses_branch.php?imported=false&error=Invalid request");
    exit();
}

function extractIdFromName($name)
{
    // Assuming the format is "ID - Name"
    $parts = explode(' - ', $name);
    return (int) trim($parts[0]);
}
?>