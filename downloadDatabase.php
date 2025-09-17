<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "loyal_customer";

// DB connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set headers for download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="loyal_customer_backup.sql"');

// Get all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    // Get CREATE TABLE statement
    $row = $conn->query("SHOW CREATE TABLE `$table`")->fetch_assoc();
    echo "\n\n-- Table structure for table `$table`\n";
    echo $row['Create Table'] . ";\n\n";

    // Get table data
    $dataResult = $conn->query("SELECT * FROM `$table`");
    $numFields = $dataResult->field_count;

    if ($dataResult->num_rows > 0) {
        echo "-- Dumping data for table `$table`\n";
        while ($rowData = $dataResult->fetch_assoc()) {
            $columns = array_map(function($col){ return "`$col`"; }, array_keys($rowData));
            $values = array_map(function($val) use ($conn){
                return isset($val) ? "'" . $conn->real_escape_string($val) . "'" : "NULL";
            }, array_values($rowData));

            echo "INSERT INTO `$table` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ");\n";
        }
        echo "\n";
    }
}

$conn->close();
exit();
?>
