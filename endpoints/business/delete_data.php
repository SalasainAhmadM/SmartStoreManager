<?php
require_once '../../conn/conn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $type = $_POST['type'] ?? '';
    $ids = $_POST['ids'] ?? '';

    if (empty($type) || empty($ids)) {
        echo json_encode(["success" => false, "message" => "Invalid request."]);
        exit;
    }

    $idArray = explode(',', $ids);
    $idArray = array_map('intval', $idArray);
    $idList = implode(',', $idArray);


    $table = "";
    switch ($type) {
        case 'business':
            $table = "business";
            break;
        case 'branch':
            $table = "branch";
            break;
        case 'products':
            $table = "products";
            break;
        default:
            echo json_encode(["success" => false, "message" => "Invalid data type."]);
            exit;
    }

    // Create SQL DELETE query
    $sql = "DELETE FROM `$table` WHERE id IN ($idList)";

    // Execute query
    $stmt = $conn->prepare($sql);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Data deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete data."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>