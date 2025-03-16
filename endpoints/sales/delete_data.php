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

    if ($type === 'sales') {
        $table = "sales";
    } else {
        echo json_encode(["success" => false, "message" => "Invalid data type."]);
        exit;
    }

    // Prepare DELETE SQL statement
    $sql = "DELETE FROM `$table` WHERE id IN ($idList)";

    // Execute the query
    $stmt = $conn->prepare($sql);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Selected sales records deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete sales records."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>