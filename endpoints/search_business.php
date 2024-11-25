<?php
require_once '../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['query'])) {
    $query = $_GET['query'];

    $sql = "SELECT * FROM business WHERE name LIKE CONCAT('%', ?, '%') ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $query);
    $stmt->execute();
    $result = $stmt->get_result();

    $businesses = [];
    while ($row = $result->fetch_assoc()) {
        $businesses[] = $row;
    }

    echo json_encode($businesses);
    $stmt->close();
    $conn->close();
}
?>