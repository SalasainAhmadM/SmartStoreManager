<?php
session_start();
include '../../conn/conn.php';

$owner_id = $_SESSION['user_id']; // Get owner ID from session

if (isset($_POST['type'])) {
    $type = $_POST['type'];
    $year = isset($_POST['year']) ? $_POST['year'] : null;
    $month = isset($_POST['month']) ? $_POST['month'] : null;

    if ($type == 'business') {
        $query = "SELECT * FROM business WHERE owner_id = ?";
        if ($year && $month) {
            $query .= " AND YEAR(created_at) = ? AND MONTH(created_at) = ?";
        } elseif ($year) {
            $query .= " AND YEAR(created_at) = ?";
        }
        $stmt = $conn->prepare($query);
        if ($year && $month) {
            $stmt->bind_param("iii", $owner_id, $year, $month);
        } elseif ($year) {
            $stmt->bind_param("ii", $owner_id, $year);
        } else {
            $stmt->bind_param("i", $owner_id);
        }
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
            if ($year && $month) {
                $query .= " AND YEAR(created_at) = ? AND MONTH(created_at) = ?";
            } elseif ($year) {
                $query .= " AND YEAR(created_at) = ?";
            }
            $stmt = $conn->prepare($query);
            $params = array_merge($business_ids, $year && $month ? [$year, $month] : ($year ? [$year] : []));
            $stmt->bind_param(str_repeat('i', count($params)), ...$params);
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
            if ($year && $month) {
                $query .= " AND YEAR(created_at) = ? AND MONTH(created_at) = ?";
            } elseif ($year) {
                $query .= " AND YEAR(created_at) = ?";
            }
            $stmt = $conn->prepare($query);
            $params = array_merge($business_ids, $year && $month ? [$year, $month] : ($year ? [$year] : []));
            $stmt->bind_param(str_repeat('i', count($params)), ...$params);
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