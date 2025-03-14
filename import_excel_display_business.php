<?php
require 'vendor/autoload.php';
session_start();
require_once './conn/conn.php';
require_once './conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

use PhpOffice\PhpSpreadsheet\IOFactory;

// Handle the form submission for importing data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
    $data = json_decode($_POST['data'], true);
    $businessInfo = $data['business'];
    $products = $data['products'];
    $branches = $data['branches'];

    // Insert business information
    $stmt = $conn->prepare("INSERT INTO business (name, description, asset, employee_count, owner_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $businessInfo['name'], $businessInfo['description'], $businessInfo['asset_size'], $businessInfo['employee_count'], $owner_id);
    $stmt->execute();
    $business_id = $stmt->insert_id;
    $stmt->close();

    // Insert products
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, size, type, business_id) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($products as $product) {
        $stmt->bind_param("sssssi", $product['name'], $product['description'], $product['price'], $product['size'], $product['type'], $business_id);
        $stmt->execute();
    }
    $stmt->close();

    // Insert branches
    $stmt = $conn->prepare("INSERT INTO branch (location, business_id, manager_id) VALUES (?, ?, ?)");
    foreach ($branches as $branch) {
        $stmt->bind_param("sii", $branch['location'], $business_id, $branch['manager_id']);
        $stmt->execute();
    }
    $stmt->close();

    header("Location: ./owner/managebusiness.php?imported=true");
    exit();
}

if (isset($_FILES['file']['tmp_name'])) {
    $filePath = $_FILES['file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Extract business information (Row 2)
        $businessInfo = [
            'name' => $sheet->getCell('A2')->getValue(),
            'description' => $sheet->getCell('B2')->getValue(),
            'asset_size' => $sheet->getCell('C2')->getValue(),
            'employee_count' => $sheet->getCell('D2')->getValue()
        ];

        // Extract product information (starting from Row 5)
        $products = [];
        $row = 5;
        while (true) {
            $name = $sheet->getCell("A$row")->getValue();
            if (empty($name))
                break; // Stop if no more products

            $products[] = [
                'name' => $name,
                'type' => $sheet->getCell("B$row")->getValue(),
                'price' => $sheet->getCell("C$row")->getValue(),
                'size' => $sheet->getCell("D$row")->getValue(),
                'description' => $sheet->getCell("E$row")->getValue()
            ];
            $row++;
        }

        // Find the "Locations" header row
        $locationsHeaderRow = null;
        for ($i = 1; $i <= $sheet->getHighestRow(); $i++) {
            if (trim($sheet->getCell("A$i")->getValue()) === "Locations") {
                $locationsHeaderRow = $i;
                break;
            }
        }

        // Extract branch information (starting from the row after the "Locations" header)
        $branches = [];
        if ($locationsHeaderRow !== null) {
            $row = $locationsHeaderRow + 1; // Start from the row after the "Locations" header
            while (true) {
                $location = $sheet->getCell("A$row")->getValue();
                if (empty($location))
                    break; // Stop if no more branches

                $branches[] = [
                    'location' => $location,
                    'manager_id' => $sheet->getCell("B$row")->getValue()
                ];
                $row++;
            }
        }

        // Display the data in tables
        ?>
        <!DOCTYPE html>
        <html>

        <head>
            <title>Import Preview</title>
            <link rel="icon" href="./assets/logo.png">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>

        <body class="container mt-5">
            <h2>Business Information</h2>
            <table class="table table-bordered mb-5">
                <thead class="table-dark">
                    <tr>
                        <th>Business Name</th>
                        <th>Description</th>
                        <th>Asset Size</th>
                        <th>Number of Employees</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($businessInfo['name']); ?></td>
                        <td><?php echo htmlspecialchars($businessInfo['description']); ?></td>
                        <td><?php echo htmlspecialchars($businessInfo['asset_size']); ?></td>
                        <td><?php echo htmlspecialchars($businessInfo['employee_count']); ?></td>
                    </tr>
                </tbody>
            </table>

            <h2>Products</h2>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Size</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['type']); ?></td>
                            <td><?php echo htmlspecialchars($product['price']); ?></td>
                            <td><?php echo htmlspecialchars($product['size']); ?></td>
                            <td><?php echo htmlspecialchars($product['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Branches</h2>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($branch['location']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form id="importForm" action="import_excel_display_business.php" method="POST" class="mt-4">
                <input type="hidden" name="data"
                    value='<?php echo htmlspecialchars(json_encode(['business' => $businessInfo, 'products' => $products, 'branches' => $branches])); ?>'>
                <button type="button" class="btn btn-primary" id="confirmImport">Confirm Import</button>
                <a href="./owner/managebusiness.php" class="btn btn-secondary">Cancel</a>
            </form>

            <script>
                document.getElementById("confirmImport").addEventListener("click", function () {
                    Swal.fire({
                        title: "Are you sure?",
                        text: "You are about to import this data!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, import it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById("importForm").submit();
                        }
                    });
                });
            </script>
        </body>

        </html>
        <?php
    } catch (Exception $e) {
        echo "<script>
            Swal.fire({
                title: 'Error!',
                text: 'Error reading file: " . addslashes($e->getMessage()) . "',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        </script>";
    }
} else {
    echo "<script>
        Swal.fire({
            title: 'No File Uploaded!',
            text: 'Please upload a valid Excel file.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    </script>";
}
?>