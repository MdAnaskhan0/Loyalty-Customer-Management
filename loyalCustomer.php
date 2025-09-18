<?php

session_start();

// If not logged in, redirect to login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "loyal_customer";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If POST request (AJAX form submission)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');

    $customerName = trim($_POST['customerName']);
    $phone = trim($_POST['phone']);
    $branch = $_POST['branch'];
    $entryDate = $_POST['entryDate'];
    $forceSave = isset($_POST['forceSave']) ? $_POST['forceSave'] : 0;

    // Check exact duplicate (case-insensitive name + phone)
    $checkSql = "SELECT * FROM customersinfo 
                 WHERE LOWER(TRIM(customerName)) = LOWER(TRIM('$customerName')) 
                 AND phone = '$phone'";
    $result = $conn->query($checkSql);

    if ($result->num_rows > 0) {
        echo json_encode([
            "status" => "duplicate",
            "message" => "Error: This customer already exists."
        ]);
        exit();
    }

    // Check if phone already exists
    $phoneCheckSql = "SELECT * FROM customersinfo WHERE phone = '$phone'";
    $phoneResult = $conn->query($phoneCheckSql);

    if ($phoneResult->num_rows > 0 && !$forceSave) {
        $existingCustomers = [];
        while ($row = $phoneResult->fetch_assoc()) {
            $existingCustomers[] = $row;
        }
        echo json_encode([
            "status" => "phone_exists",
            "customers" => $existingCustomers
        ]);
        exit();
    }

    // Insert new customer
    $insertSql = "INSERT INTO customersinfo (customerName, phone, branch, entryDate) 
                  VALUES ('$customerName', '$phone', '$branch', '$entryDate')";

    if ($conn->query($insertSql)) {
        echo json_encode([
            "status" => "success",
            "message" => "Customer added successfully!"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Error: " . $conn->error
        ]);
    }
    exit();
}


// ------------------ EXPORT CSV ------------------
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=customers.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, array(
        'ID',
        'Name',
        'Phone',
        'Branch',
        'Branch Code',
        'Date',
        'Number',
        'CardID',
        'Entry Date'
    ));

    $exportSql = "SELECT * FROM customersinfo";
    $whereClauses = [];

    if (!empty($_GET['search'])) {
        $search = $_GET['search'];
        $whereClauses[] = "(customerName LIKE '%$search%' OR phone LIKE '%$search%' OR branch LIKE '%$search%')";
    }
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
        $branchCode = $row['branch'];
        if (strpos($row['branch'], '-') !== false) {
            $branchParts = explode("-", $row['branch'], 2);
            $branchCode = $branchParts[0];
        }
        $dateCode = date('my', strtotime($row['entryDate']));
        $phoneRaw = (string) $row['phone'];
        $number = strlen($phoneRaw) > 2 ? substr($phoneRaw, 2) : '';
        $cardId = $branchCode . $dateCode . $number;

        fputcsv($output, array(
            $row['customerId'],
            $row['customerName'],
            $row['phone'],
            $row['branch'],
            $branchCode,
            $dateCode,
            $number,
            $cardId,
            $row['entryDate']
        ));
    }

    fclose($output);
    exit();
}

// ------------------ SEARCH + PAGINATION ------------------
$search = isset($_GET['search']) ? $_GET['search'] : "";
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : "";
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : "";

$records_per_page = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$countSql = "SELECT COUNT(*) AS total FROM customersinfo";
$whereClauses = [];

if (!empty($search)) {
    $whereClauses[] = "(customerName LIKE '%$search%' OR phone LIKE '%$search%' OR branch LIKE '%$search%')";
}
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

if ($page < 1)
    $page = 1;
if ($page > $totalPages && $totalPages > 0)
    $page = $totalPages;

$sql = "SELECT * FROM customersinfo";
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY customerId DESC LIMIT $offset, $records_per_page";
$result = $conn->query($sql);

// Set default date to previous day
$defaultDate = date('Y-m-d', strtotime('-1 day'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fashion Optics Loyalty Customer Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        body {
            font-size: 0.875rem;
            background-color: #f8f9fa;
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1rem;
        }

        .table th,
        .table td {
            padding: 0.5rem;
        }

        .form-label {
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .btn-sm {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .pagination {
            margin-bottom: 0.5rem;
        }

        .search-section {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        .modal-customer-list {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="container-fluid" style="min-height: 90vh;">
        <div class="row">
            <div class="col-12 d-flex align-items-center justify-content-between shadow p-3 mb-5 bg-white rounded">
                <!-- Logo -->
                <div>
                    <img src="logo.png" alt="Fashion Optics Ltd." class="img-fluid" style="max-height: 50px;">
                </div>

                <!-- Title + Dropdown -->
                <div class="d-flex align-items-center">
                    <h1 class="h5 mb-0 me-3"
                        style="color: #CD2128; font-family: 'Playfair Display', serif; font-weight: 800; letter-spacing: 0.5px;">
                        Loyalty Customer Management System
                    </h1>

                    <!-- Dropdown Menu -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userMenu"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Account
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="changePassword.php">Change Password</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            <li><a class="dropdown-item" href="downloadDatabase.php">Download Database (SQL)</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <!-- Left side: Input Form -->
            <div class="col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header bg-light py-2">
                        <h5 class="mb-0">Add New Customer</h5>
                    </div>
                    <div class="card-body">
                        <form id="customerForm" method="POST">
                            <div class="mb-2">
                                <label for="customerName" class="form-label">Customer Name:</label>
                                <input type="text" class="form-control form-control-sm" id="customerName"
                                    name="customerName" required>
                            </div>
                            <div class="mb-2">
                                <label for="phone" class="form-label">Phone Number:</label>
                                <input type="text" class="form-control form-control-sm" id="phone" name="phone"
                                    required>
                            </div>
                            <div class="mb-2">
                                <label for="branch" class="form-label">Branch:</label>
                                <select class="form-select form-select-sm" id="branch" name="branch" required>
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
                            <div class="mb-3">
                                <label for="entryDate" class="form-label">Entry Date:</label>
                                <input type="date" class="form-control form-control-sm" id="entryDate" name="entryDate"
                                    value="<?php echo $defaultDate; ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">Add Customer</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right side: Data Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Customer Records</h5>
                    </div>
                    <div class="card-body">
                        <div class="search-section mb-3">
                            <form method="GET" action="">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label for="search" class="form-label">Search:</label>
                                        <input type="text" class="form-control form-control-sm" name="search"
                                            placeholder="Name, phone, or branch"
                                            value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="startDate" class="form-label">From:</label>
                                        <input type="date" class="form-control form-control-sm" id="startDate"
                                            name="startDate" value="<?php echo htmlspecialchars($startDate); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="endDate" class="form-label">To:</label>
                                        <input type="date" class="form-control form-control-sm" id="endDate"
                                            name="endDate" value="<?php echo htmlspecialchars($endDate); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary btn-sm w-100">Search</button>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <a href="loyalCustomer.php" class="btn btn-secondary btn-sm">Reset</a>
                                        <a href="?export=1&search=<?php echo urlencode($search); ?>&startDate=<?php echo urlencode($startDate); ?>&endDate=<?php echo urlencode($endDate); ?>"
                                            class="btn btn-success btn-sm">
                                            <i class="bi bi-download me-1"></i>Export CSV
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="table-light">
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
                                            <td colspan="4" class="text-center py-3">No customers found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Showing page <?php echo $page; ?> of <?php echo $totalPages; ?> | Total Records:
                                    <?php echo $totalRecords; ?>
                                </div>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link"
                                                    href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&startDate=<?php echo urlencode($startDate); ?>&endDate=<?php echo urlencode($endDate); ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link"
                                                    href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&startDate=<?php echo urlencode($startDate); ?>&endDate=<?php echo urlencode($endDate); ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light py-3 mt-4 shadow-sm" style="box-shadow: 0 -2px 10px rgba(0,0,0,0.1) !important;">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-1 small">All right received <a href="https://fashionoptics.store/en" target="_blank"
                            class="text-decoration-none fw-bold" style="color: #CD2128;">Fashion Optics Ltd.</a></p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0 small">Develop by <a href="https://mdanaskhan.vercel.app" target="_blank"
                            class="text-decoration-none fw-bold" style="color: #6321cdff;">Fashion Group IT</a></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal for showing duplicate phone numbers -->
    <div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="duplicateModalLabel">Phone Number Already Exists</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This phone number is already registered for the following customers:</p>
                    <div class="modal-customer-list">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Branch</th>
                                    <th>Entry Date</th>
                                </tr>
                            </thead>
                            <tbody id="duplicateCustomersList">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2">Do you want to save anyway?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="forceSaveBtn">Save Anyway</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById("customerForm").addEventListener("submit", function (e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);

            fetch("", { method: "POST", body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "phone_exists") {
                        // Populate the modal with all customers having the same phone number
                        const customersList = document.getElementById('duplicateCustomersList');
                        customersList.innerHTML = '';
                        
                        data.customers.forEach(customer => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${customer.customerName}</td>
                                <td>${customer.branch}</td>
                                <td>${customer.entryDate}</td>
                            `;
                            customersList.appendChild(row);
                        });
                        
                        // Show the modal
                        const duplicateModal = new bootstrap.Modal(document.getElementById('duplicateModal'));
                        duplicateModal.show();
                        
                        // Set up the force save button
                        document.getElementById('forceSaveBtn').onclick = function() {
                            formData.append("forceSave", "1");
                            fetch("", { method: "POST", body: formData })
                                .then(r => r.json())
                                .then(d => {
                                    alert(d.message);
                                    if (d.status === "success") form.reset();
                                    duplicateModal.hide();
                                    location.reload();
                                });
                        };
                    } else {
                        alert(data.message);
                        if (data.status === "success") {
                            form.reset();
                            location.reload();
                        }
                    }
                });
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>
