<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lcustomer";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customerName = $_POST['customerName'];
    $phone = $_POST['phone'];
    $branch = $_POST['branch'];
    $entryDate = $_POST['entryDate'];

    // Check if customer with same name and phone exists
    $checkSql = "SELECT * FROM customersinfo WHERE customerName = '$customerName' AND phone = '$phone'";
    $result = $conn->query($checkSql);

    if ($result->num_rows > 0) {
        $message = "Error: This customer already exists.";
    } else {
        // Insert new customer
        $insertSql = "INSERT INTO customersinfo (customerName, phone, branch, entryDate) 
                      VALUES ('$customerName', '$phone', '$branch', '$entryDate')";

        if ($conn->query($insertSql)) {
            $message = "Customer added successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

// Handle Excel export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=customers.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'Name', 'Phone', 'Branch', 'Entry Date'));

    $exportSql = "SELECT * FROM customersinfo";
    $whereClauses = [];

    if (!empty($_GET['search'])) {
        $search = $_GET['search'];
        $whereClauses[] = "(customerName LIKE '%$search%' OR phone LIKE '%$search%' OR branch LIKE '%$search%')";
    }

    // Add date filter to export if set
    if (!empty($_GET['startDate'])) {
        $startDate = $_GET['startDate'];
        $whereClauses[] = "entryDate >= '$startDate'";
    }

    if (!empty($_GET['endDate'])) {
        $endDate = $_GET['endDate'];
        $whereClauses[] = "entryDate <= '$endDate'";
    }

    if (!empty($whereClauses)) {
        $exportSql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $result = $conn->query($exportSql);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

// Get search term if any
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Get date filters if any
$startDate = "";
$endDate = "";
if (isset($_GET['startDate'])) {
    $startDate = $_GET['startDate'];
}
if (isset($_GET['endDate'])) {
    $endDate = $_GET['endDate'];
}

// Pagination setup
$records_per_page = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$countSql = "SELECT COUNT(*) AS total FROM customersinfo";
$whereClauses = [];

if (!empty($search)) {
    $whereClauses[] = "(customerName LIKE '%$search%' OR phone LIKE '%$search%' OR branch LIKE '%$search%')";
}

// Add date filters to count query
if (!empty($startDate)) {
    $whereClauses[] = "entryDate >= '$startDate'";
}

if (!empty($endDate)) {
    $whereClauses[] = "entryDate <= '$endDate'";
}

if (!empty($whereClauses)) {
    $countSql .= " WHERE " . implode(" AND ", $whereClauses);
}

$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $records_per_page);

// Ensure page is within valid range
if ($page < 1)
    $page = 1;
if ($page > $totalPages && $totalPages > 0)
    $page = $totalPages;

// Fetch customers with optional search, date filters and pagination
$sql = "SELECT * FROM customersinfo";

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY customerId DESC LIMIT $offset, $records_per_page";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <h1>Customer Management System</h1>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="content-wrapper">
            <!-- Left side: Input Form -->
            <div class="form-container">
                <h2>Add New Customer</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="customerName">Customer Name:</label>
                        <input type="text" id="customerName" name="customerName" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="text" id="phone" name="phone" required>
                    </div>

                    <div class="form-group">
                        <label for="branch">Branch:</label>
                        <select id="branch" name="branch" required>
                            <option value="">-- Select Branch --</option>
                            <option value="F01-Fashion Optics Ltd.">F01-Fashion Optics Ltd.</option>
                            <option value="F02-FEHL">F02-FEHL</option>
                            <option value="F03-Gulshan-2">F03-Gulshan-2</option>
                            <option value="F04-Gulshan-1">F04-Gulshan-1</option>
                            <option value="F05-Uttara">F05-Uttara</option>
                            <option value="F06-Mogbazar">F06-Mogbazar</option>
                            <option value="F07-DSMR">F07-DSMR</option>
                            <option value="F08-Mirpur-12">F08-Mirpur-12</option>
                            <option value="F09-Jamal Khan CTG">F09-Jamal Khan CTG</option>
                            <option value="F10-Ego Vission">F10-Ego Vission</option>
                            <option value="F11-Elephant Road">F11-Elephant Road</option>
                            <option value="F12-New Market">F12-New Market</option>
                            <option value="F13-Patuatuly">F13-Patuatuly</option>
                            <option value="F14-Hira Panna Hospital">F14-Hira Panna Hospital</option>
                            <option value="F15-HPHS">F15-HPHS</option>
                            <option value="F16-Unimart">F16-Unimart</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="entryDate">Entry Date:</label>
                        <input type="date" id="entryDate" name="entryDate" value="<?php echo date('Y-m-d'); ?>"
                            required>
                    </div>

                    <button type="submit">Add Customer</button>
                </form>
            </div>

            <!-- Right side: Data Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Customer Records</h2>
                    <!-- Replace the existing search form with this code -->
                    <div class="controls">
                        <form method="GET" action="" class="search-form">
                            <input type="text" name="search" placeholder="Search by name, phone, or branch"
                                value="<?php echo htmlspecialchars($search); ?>">

                            <div class="date-filter-row">
                                <label for="startDate">From:</label>
                                <input type="date" id="startDate" name="startDate"
                                    value="<?php echo htmlspecialchars($startDate); ?>">

                                <label for="endDate">To:</label>
                                <input type="date" id="endDate" name="endDate"
                                    value="<?php echo htmlspecialchars($endDate); ?>">
                            </div>

                            <div class="button-row">
                                <button type="submit">Search</button>
                                <a href="index.php" class="reset-btn">Reset</a>
                                <a href="?export=1&search=<?php echo urlencode($search); ?>&startDate=<?php echo urlencode($startDate); ?>&endDate=<?php echo urlencode($endDate); ?>"
                                    class="download-btn">Download Excel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Branch</th>
                            <th>Entry Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['customerName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['branch']); ?></td>
                                    <td><?php echo $row['entryDate']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No customers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&startDate=<?php echo urlencode($startDate); ?>&endDate=<?php echo urlencode($endDate); ?>"
                                class="pagination-link">Previous</a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&startDate=<?php echo urlencode($startDate); ?>&endDate=<?php echo urlencode($endDate); ?>"
                                class="pagination-link">Next</a>
                        <?php endif; ?>
                    </div>
                    <div class="pagination-info">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?> |
                        Total Records: <?php echo $totalRecords; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>

<?php $conn->close(); ?>