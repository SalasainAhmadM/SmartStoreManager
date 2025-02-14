<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Import/Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        button,
        input[type="submit"] {
            padding: 10px 15px;
            font-size: 16px;
            margin: 10px 0;
            cursor: pointer;
        }

        table {
            width: 50%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }
    </style>
</head>

<body>
    <h1>Excel Import/Export</h1>

    <!-- Export Button -->
    <form action="export_excel.php" method="POST">
        <button type="submit">Download Excel Template</button>
    </form>

    <!-- Upload Form -->
    <form action="import_excel.php" method="POST" enctype="multipart/form-data">
        <label for="file">Upload Edited Excel File:</label>
        <input type="file" name="file" id="file" accept=".xlsx, .xls">
        <input type="submit" value="Upload Excel">
    </form>


</body>

</html>