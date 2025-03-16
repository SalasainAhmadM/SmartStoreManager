<?php
session_start();
include '../../conn/conn.php';

$owner_id = $_SESSION['user_id']; // Get owner ID from session

if (isset($_POST['type'])) {
    $type = $_POST['type'];

    if ($type == 'business') {
        $query = "SELECT * FROM business WHERE owner_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $owner_id);
    } elseif ($type == 'branch') {
        // First, get all business IDs owned by the owner
        $query = "SELECT id FROM business WHERE owner_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $business_ids = [];

        while ($row = $result->fetch_assoc()) {
            $business_ids[] = $row['id'];
        }

        if (!empty($business_ids)) {
            $placeholders = implode(',', array_fill(0, count($business_ids), '?'));
            $query = "SELECT * FROM branch WHERE business_id IN ($placeholders)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(str_repeat('i', count($business_ids)), ...$business_ids);
        } else {
            echo json_encode([]); // No businesses found
            exit;
        }
    } elseif ($type == 'products') {
        // First, get all business IDs owned by the owner
        $query = "SELECT id FROM business WHERE owner_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $business_ids = [];

        while ($row = $result->fetch_assoc()) {
            $business_ids[] = $row['id'];
        }

        if (!empty($business_ids)) {
            $placeholders = implode(',', array_fill(0, count($business_ids), '?'));
            $query = "SELECT * FROM products WHERE business_id IN ($placeholders)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(str_repeat('i', count($business_ids)), ...$business_ids);
        } else {
            echo json_encode([]); // No businesses found
            exit;
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}
?>