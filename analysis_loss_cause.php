<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$conn = new mysqli('localhost', 'root', '', 'freshtrack');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = '';
// Use existing CSRF token or generate a new one
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Debug form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data: " . print_r($_POST, true));
}

// Handle Add Analysis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_analysis'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = '<div class="alert alert-danger">CSRF validation failed</div>';
        error_log("Add CSRF validation failed. Sent: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: " . $_SESSION['csrf_token']);
    } else {
        $product_id = (int)$_POST['product_id'];
        $loss_cause = isset($_POST['loss_cause']) ? trim($_POST['loss_cause']) : '';
        $quantity_percent = (float)$_POST['quantity_percent'];
        $created_at = $_POST['created_at'] ?? '';

        // Debug logging for loss_cause
        error_log("Add Analysis - Raw loss_cause: " . ($_POST['loss_cause'] ?? 'unset') . ", Trimmed: '$loss_cause'");

        // Server-side validation
        if ($product_id <= 0) {
            $message = '<div class="alert alert-danger">Invalid product selected</div>';
        } elseif ($loss_cause === '') {
            $message = '<div class="alert alert-danger">Loss cause is required</div>';
        } elseif ($quantity_percent <= 0) {
            $message = '<div class="alert alert-danger">Quantity percent must be positive</div>';
        } elseif (empty($created_at) || strtotime($created_at) > time()) {
            $message = '<div class="alert alert-danger">Created date cannot be empty or in the future</div>';
        } else {
            // Check if product_id exists in inventory
            $stmt_check = $conn->prepare("SELECT batch_id FROM inventory WHERE batch_id = ?");
            $stmt_check->bind_param("i", $product_id);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            if ($result->num_rows === 0) {
                $message = '<div class="alert alert-danger">Selected product does not exist</div>';
                error_log("Product ID $product_id not found in inventory");
            } else {
                $stmt_add = $conn->prepare("INSERT INTO loss_analysis (product_id, loss_cause, quantity_percent, created_at) VALUES (?, ?, ?, ?)");
                $stmt_add->bind_param("isds", $product_id, $loss_cause, $quantity_percent, $created_at);
                
                if ($stmt_add->execute()) {
                    $message = '<div class="alert alert-success">Analysis recorded successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error recording analysis: ' . htmlspecialchars($stmt_add->error) . '</div>';
                    error_log("Insert error: " . $stmt_add->error);
                }
                $stmt_add->close();
            }
            $stmt_check->close();
        }
    }
}

// Handle Edit Analysis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_analysis'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = '<div class="alert alert-danger">CSRF validation failed</div>';
        error_log("Edit CSRF validation failed. Sent: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: " . $_SESSION['csrf_token']);
    } else {
        $analysis_id = (int)$_POST['analysis_id'];
        $product_id = (int)$_POST['product_id'];
        $loss_cause = isset($_POST['loss_cause']) ? trim($_POST['loss_cause']) : '';
        $quantity_percent = (float)$_POST['quantity_percent'];
        $created_at = $_POST['created_at'] ?? '';

        // Debug logging for loss_cause
        error_log("Edit Analysis - Raw loss_cause: " . ($_POST['loss_cause'] ?? 'unset') . ", Trimmed: '$loss_cause'");

        // Server-side validation
        if ($analysis_id <= 0) {
            $message = '<div class="alert alert-danger">Invalid analysis ID</div>';
        } elseif ($product_id <= 0) {
            $message = '<div class="alert alert-danger">Invalid product selected</div>';
        } elseif ($loss_cause === '') {
            $message = '<div class="alert alert-danger">Loss cause is required</div>';
        } elseif ($quantity_percent <= 0) {
            $message = '<div class="alert alert-danger">Quantity percent must be positive</div>';
        } elseif (empty($created_at) || strtotime($created_at) > time()) {
            $message = '<div class="alert alert-danger">Created date cannot be empty or in the future</div>';
        } else {
            // Check if product_id exists in inventory
            $stmt_check = $conn->prepare("SELECT batch_id FROM inventory WHERE batch_id = ?");
            $stmt_check->bind_param("i", $product_id);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            if ($result->num_rows === 0) {
                $message = '<div class="alert alert-danger">Selected product does not exist</div>';
                error_log("Product ID $product_id not found for edit");
            } else {
                $stmt_edit = $conn->prepare("UPDATE loss_analysis SET product_id = ?, loss_cause = ?, quantity_percent = ?, created_at = ? WHERE analysis_id = ?");
                $stmt_edit->bind_param("isdsi", $product_id, $loss_cause, $quantity_percent, $created_at, $analysis_id);
                
                if ($stmt_edit->execute()) {
                    $message = '<div class="alert alert-success">Analysis updated successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error updating analysis: ' . htmlspecialchars($stmt_edit->error) . '</div>';
                    error_log("Update error: " . $stmt_edit->error);
                }
                $stmt_edit->close();
            }
            $stmt_check->close();
        }
    }
}

// Handle Delete Analysis
if (isset($_GET['delete_id'])) {
    $analysis_id = (int)$_GET['delete_id'];
    $stmt_delete = $conn->prepare("DELETE FROM loss_analysis WHERE analysis_id = ?");
    $stmt_delete->bind_param("i", $analysis_id);
    
    if ($stmt_delete->execute()) {
        $message = '<div class="alert alert-success">Analysis deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting analysis: ' . htmlspecialchars($stmt_delete->error) . '</div>';
        error_log("Delete error: " . $stmt_delete->error);
    }
    $stmt_delete->close();
}

// Fetch products for dropdown
$products = $conn->query("SELECT i.batch_id, bt.type FROM inventory i JOIN batch_types bt ON i.type_id = bt.type_id");
$products_data = [];
while ($row = $products->fetch_assoc()) {
    $products_data[] = $row;
}

// Handle Filter
$filter_product_id = $_POST['product_id_filter'] ?? '';
$filter_date_start = $_POST['date_start'] ?? '';
$filter_date_end = $_POST['date_end'] ?? '';
$analysis_data = [];

$where_clauses = [];
$params = [];
$param_types = '';

if ($filter_product_id && $filter_product_id !== 'all') {
    $where_clauses[] = "la.product_id = ?";
    $params[] = $filter_product_id;
    $param_types .= 'i';
}
if ($filter_date_start) {
    $where_clauses[] = "DATE(la.created_at) >= ?";
    $params[] = $filter_date_start;
    $param_types .= 's';
}
if ($filter_date_end) {
    $where_clauses[] = "DATE(la.created_at) <= ?";
    $params[] = $filter_date_end;
    $param_types .= 's';
}

$query = "SELECT la.analysis_id, la.product_id, bt.type AS product_name, la.loss_cause, la.quantity_percent, la.created_at 
          FROM loss_analysis la 
          JOIN inventory i ON la.product_id = i.batch_id 
          JOIN batch_types bt ON i.type_id = bt.type_id";
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}
$query .= " ORDER BY la.created_at DESC";

if (!empty($params)) {
    $stmt_filter = $conn->prepare($query);
    $stmt_filter->bind_param($param_types, ...$params);
    $stmt_filter->execute();
    $result = $stmt_filter->get_result();
    $stmt_filter->close();
} else {
    $result = $conn->query($query);
}

while ($row = $result->fetch_assoc()) {
    $analysis_data[] = $row;
}

// Fetch data for charts
$bar_chart_data = [];
$pie_chart_data = [];

// Bar Chart: Quantity percent by loss cause, grouped by product
$result = $conn->query("SELECT la.loss_cause, la.product_id, bt.type AS product_name, SUM(la.quantity_percent) as quantity_percent 
                        FROM loss_analysis la 
                        JOIN inventory i ON la.product_id = i.batch_id 
                        JOIN batch_types bt ON i.type_id = bt.type_id 
                        GROUP BY la.loss_cause, la.product_id 
                        ORDER BY la.loss_cause");
while ($row = $result->fetch_assoc()) {
    $bar_chart_data[] = $row;
}

// Pie Chart: Quantity percent by product
$result = $conn->query("SELECT la.product_id, bt.type AS product_name, SUM(la.quantity_percent) as quantity_percent 
                        FROM loss_analysis la 
                        JOIN inventory i ON la.product_id = i.batch_id 
                        JOIN batch_types bt ON i.type_id = bt.type_id 
                        GROUP BY la.product_id");
while ($row = $result->fetch_assoc()) {
    $pie_chart_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loss Cause Analysis - FreshTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary-color: #1a202c;
            --secondary-color: #4a5568;
            --accent-color: #e53e3e;
            --background-color: #ffffff;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--primary-color);
        }
        .navbar {
            background-color: var(--background-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand, .nav-link {
            color: var(--primary-color) !important;
        }
        .nav-link:hover {
            color: var(--accent-color) !important;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        .table {
            background-color: var(--background-color);
            border: 1px solid var(--secondary-color);
        }
        .table th, .table td {
            border-color: var(--secondary-color);
        }
        .modal-content {
            border-radius: 0.5rem;
            border: 1px solid var(--secondary-color);
        }
        .fadeIn {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        footer {
            background-color: var(--background-color);
            color: var(--secondary-color);
            border-top: 1px solid var(--secondary-color);
        }
        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .chart-container {
            max-width: 600px;
            margin: 2rem auto;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">FreshTrack</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventory_tracking.php">Inventory Tracking</a></li>
                    <li class="nav-item"><a class="nav-link" href="stirage_monitor.php">Storage Monitoring</a></li>
                    <li class="nav-item"><a class="nav-link" href="sale_record.php">Sales & Distribution</a></li>
                    <li class="nav-item"><a class="nav-link" href="loss_recording.php">Loss Recording</a></li>
                    <li class="nav-item"><a class="nav-link active" href="analysis_loss_cause.php">Loss Analysis</a></li>
                    <li class="nav-item"><a class="nav-link" href="prevent_track.php">Preventative Measures</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-danger text-white ms-2" href="#">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-5 pt-5">
        <section class="fadeIn">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Loss Cause Analysis</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnalysisModal">Add Analysis</button>
            </div>

            <!-- Filter Form -->
            <form method="POST" class="filter-form mb-4">
                <div>
                    <label for="product_id_filter" class="form-label">Product</label>
                    <select class="form-select" id="product_id_filter" name="product_id_filter">
                        <option value="all" <?php echo $filter_product_id === 'all' || !$filter_product_id ? 'selected' : ''; ?>>All Products</option>
                        <?php foreach ($products_data as $product): ?>
                            <option value="<?php echo $product['batch_id']; ?>" <?php echo $filter_product_id == $product['batch_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="date_start" class="form-label">Date Start</label>
                    <input type="date" class="form-control" id="date_start" name="date_start" value="<?php echo htmlspecialchars($filter_date_start); ?>">
                </div>
                <button type="submit" class="btn btn-primary mt-3">Filter</button>
            </form>

            <!-- Success/Error Message -->
            <?php echo $message; ?>

            <!-- Analysis Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Loss Cause</th>
                            <th>Quantity Percent (%)</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($analysis_data)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No analysis records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($analysis_data as $analysis): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($analysis['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($analysis['loss_cause'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($analysis['quantity_percent'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($analysis['created_at']))); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editAnalysisModal"
                                                data-analysis-id="<?php echo $analysis['analysis_id']; ?>"
                                                data-product-id="<?php echo $analysis['product_id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($analysis['product_name']); ?>"
                                                data-loss-cause="<?php echo htmlspecialchars($analysis['loss_cause'] ?: ''); ?>"
                                                data-quantity-percent="<?php echo $analysis['quantity_percent']; ?>"
                                                data-created-at="<?php echo date('Y-m-d', strtotime($analysis['created_at'])); ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <a href="?delete_id=<?php echo $analysis['analysis_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this analysis?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Charts -->
            <div class="chart-container">
                <h3>Quantity Percent by Cause and Product (Bar Chart)</h3>
                <canvas id="barChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Quantity Percent Distribution by Product (Pie Chart)</h3>
                <canvas id="pieChart"></canvas>
            </div>
        </section>
    </main>

    <!-- Add Analysis Modal -->
    <div class="modal fade" id="addAnalysisModal" tabindex="-1" aria-labelledby="addAnalysisModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAnalysisModalLabel">Add Analysis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addAnalysisForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="product_id" class="form-label">Product</label>
                            <select class="form-select" id="product_id" name="product_id" required>
                                <option value="">Select Product</option>
                                <?php foreach ($products_data as $product): ?>
                                    <option value="<?php echo $product['batch_id']; ?>">
                                        <?php echo htmlspecialchars($product['type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="loss_cause" class="form-label">Loss Cause</label>
                            <input type="text" class="form-control" id="loss_cause" name="loss_cause" required>
                        </div>
                        <div class="mb-3">
                            <label for="quantity_percent" class="form-label">Quantity Percent (%)</label>
                            <input type="number" class="form-control" id="quantity_percent" name="quantity_percent" min="0.01" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="created_at" class="form-label">Created At</label>
                            <input type="date" class="form-control" id="created_at" name="created_at" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_analysis">Add Analysis</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Analysis Modal -->
    <div class="modal fade" id="editAnalysisModal" tabindex="-1" aria-labelledby="editAnalysisModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAnalysisModalLabel">Edit Analysis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editAnalysisForm">
                    <input type="hidden" id="edit_csrf_token" name="csrf_token">
                    <input type="hidden" id="edit_analysis_id" name="analysis_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_product_id" class="form-label">Product</label>
                            <select class="form-select" id="edit_product_id" name="product_id" required>
                                <option value="">Select Product</option>
                                <?php foreach ($products_data as $product): ?>
                                    <option value="<?php echo $product['batch_id']; ?>">
                                        <?php echo htmlspecialchars($product['type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_loss_cause" class="form-label">Loss Cause</label>
                            <input type="text" class="form-control" id="edit_loss_cause" name="loss_cause" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_quantity_percent" class="form-label">Quantity Percent (%)</label>
                            <input type="number" class="form-control" id="edit_quantity_percent" name="quantity_percent" min="0.01" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_created_at" class="form-label">Created At</label>
                            <input type="date" class="form-control" id="edit_created_at" name="created_at" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="edit_analysis">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-4">
        <p>© 2025 <a href="index.php" class="text-decoration-none">FreshTrack</a>. All rights reserved.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store CSRF token in JavaScript for dynamic updates
        const csrfToken = <?php echo json_encode($csrf_token); ?>;

        // Populate Edit Modal with analysis data
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                console.log('Edit button clicked');
                const analysisId = button.dataset.analysisId;
                const productId = button.dataset.productId;
                const lossCause = button.dataset.lossCause || '';
                const quantityPercent = button.dataset.quantityPercent;
                const createdAt = button.dataset.createdAt;

                console.log('Analysis ID:', analysisId);
                console.log('Product ID:', productId);
                console.log('Loss Cause:', lossCause);
                console.log('Quantity Percent:', quantityPercent);
                console.log('Created At:', createdAt);
                console.log('Setting CSRF token:', csrfToken);

                // Populate form fields
                document.getElementById('edit_analysis_id').value = analysisId;
                document.getElementById('edit_product_id').value = productId;
                document.getElementById('edit_loss_cause').value = lossCause;
                document.getElementById('edit_quantity_percent').value = quantityPercent;
                document.getElementById('edit_created_at').value = createdAt;

                // Set CSRF token
                document.getElementById('edit_csrf_token').value = csrfToken;
            });
        });

        // Client-side validation for Add Analysis form
        document.getElementById('addAnalysisForm').addEventListener('submit', function(e) {
            const quantityPercent = document.getElementById('quantity_percent').value;
            const createdAt = document.getElementById('created_at').value;
            const lossCause = document.getElementById('loss_cause').value.trim();
            const today = new Date().toISOString().split('T')[0];

            console.log('Add form submitted');
            console.log('Loss Cause:', lossCause);
            console.log('Quantity Percent:', quantityPercent);
            console.log('Created At:', createdAt);

            if (!lossCause) {
                e.preventDefault();
                alert('Loss cause is required');
            } else if (quantityPercent <= 0) {
                e.preventDefault();
                alert('Quantity percent must be positive');
            } else if (createdAt > today) {
                e.preventDefault();
                alert('Created date cannot be in the future');
            }
        });

        // Client-side validation for Edit Analysis form
        document.getElementById('editAnalysisForm').addEventListener('submit', function(e) {
            const quantityPercent = document.getElementById('edit_quantity_percent').value;
            const createdAt = document.getElementById('edit_created_at').value;
            const lossCause = document.getElementById('edit_loss_cause').value.trim();
            const today = new Date().toISOString().split('T')[0];

            console.log('Edit form submitted');
            console.log('Loss Cause:', lossCause);
            console.log('Quantity Percent:', quantityPercent);
            console.log('Created At:', createdAt);
            console.log('CSRF token:', document.getElementById('edit_csrf_token').value);

            if (!lossCause) {
                e.preventDefault();
                alert('Loss cause is required');
            } else if (quantityPercent <= 0) {
                e.preventDefault();
                alert('Quantity percent must be positive');
            } else if (createdAt > today) {
                e.preventDefault();
                alert('Created date cannot be in the future');
            }
        });

        // Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        const barData = <?php echo json_encode($bar_chart_data); ?>;
        const causes = [...new Set(barData.map(item => item.loss_cause || 'Unknown'))];
        const products = [...new Set(barData.map(item => item.product_name))];
        const datasets = products.map(product => {
            return {
                label: product,
                data: causes.map(cause => {
                    const item = barData.find(d => (d.loss_cause || 'Unknown') === cause && d.product_name === product);
                    return item ? item.quantity_percent : 0;
                }),
                backgroundColor: product === products[0] ? 'rgba(75, 192, 192, 0.6)' :
                                product === products[1] ? 'rgba(255, 99, 132, 0.6)' :
                                product === products[2] ? 'rgba(54, 162, 235, 0.6)' :
                                'rgba(255, 206, 86, 0.6)',
                borderColor: product === products[0] ? 'rgba(75, 192, 192, 1)' :
                             product === products[1] ? 'rgba(255, 99, 132, 1)' :
                             product === products[2] ? 'rgba(54, 162, 235, 1)' :
                             'rgba(255, 206, 86, 1)',
                borderWidth: 1
            };
        });

        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: causes,
                datasets: datasets
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Quantity Percent by Cause and Product' }
                },
                scales: {
                    x: { stacked: false, title: { display: true, text: 'Loss Cause' } },
                    y: { stacked: false, title: { display: true, text: 'Quantity Percent (%)' }, beginAtZero: true }
                }
            }
        });

        // Pie Chart
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        const pieData = <?php echo json_encode($pie_chart_data); ?>;
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: pieData.map(item => item.product_name),
                datasets: [{
                    data: pieData.map(item => item.quantity_percent),
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Quantity Percent Distribution by Product' }
                }
            }
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>