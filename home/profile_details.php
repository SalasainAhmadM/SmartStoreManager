<?php
header("Content-Type: application/json");
require_once("../conn/conn.php");

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

$action = $_POST["action"] ?? null;
if ($action !== "complete_profile") {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
    exit;
}

$ownerId = $_POST["owner_id"] ?? null;
if (!$ownerId) {
    echo json_encode(["status" => "error", "message" => "Owner ID is required"]);
    exit;
}

// Required fields
$requiredFields = ["first_name", "middle_name", "last_name", "gender", "age", "birthday", "contact_number", "barangay", "city", "region", "country"];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(["status" => "error", "message" => ucfirst(str_replace("_", " ", $field)) . " is required"]);
        exit;
    }
}

// Modified File Upload Function to return only filename
function uploadFile($file, $directory)
{
    $targetDir = "../assets/$directory/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = uniqid() . "." . pathinfo($file["name"], PATHINFO_EXTENSION);
    $targetPath = $targetDir . $fileName;

    return move_uploaded_file($file["tmp_name"], $targetPath) ? $fileName : false;
}

// Handle file uploads - these will now store just the filename
$profileImageName = !empty($_FILES["profile_image"]["tmp_name"]) ? uploadFile($_FILES["profile_image"], "profiles") : null;
$validIdName = !empty($_FILES["valid_id"]["tmp_name"]) ? uploadFile($_FILES["valid_id"], "valid_ids") : null;

if (!$validIdName) {
    echo json_encode(["status" => "error", "message" => "Valid ID is required"]);
    exit;
}

// Prepare SQL query to update profile
$stmt = $conn->prepare("
    UPDATE owner 
    SET first_name = ?, middle_name = ?, last_name = ?, gender = ?, age = ?, birthday = ?, 
        contact_number = ?, barangay = ?, city = ?, region = ?, country = ?, valid_id = ?, 
        image = IFNULL(?, image)
    WHERE id = ?
");

$stmt->bind_param(
    "ssssissssssssi",
    $_POST["first_name"],
    $_POST["middle_name"],
    $_POST["last_name"],
    $_POST["gender"],
    $_POST["age"],
    $_POST["birthday"],
    $_POST["contact_number"],
    $_POST["barangay"],
    $_POST["city"],
    $_POST["region"],
    $_POST["country"],
    $validIdName,
    $profileImageName,
    $ownerId
);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Profile updated successfully"]);

} else {
    echo json_encode(["status" => "error", "message" => "Failed to update profile"]);
}

$stmt->close();
$conn->close();
?>