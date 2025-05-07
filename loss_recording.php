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

// Validate loss stage
$valid_loss_stages = ['harvesting', 'storage', 'handling', 'transportation'];

// Debug form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data: " . print_r($_POST, true));
}

// Handle Add Loss
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_loss'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = '<div class="alert alert-danger">CSRF validation failed</div>';
        error_log("Add CSRF validation failed. Sent: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: " . $_SESSION['csrf_token']);
    } else {
        $product_id = (int)$_POST['product_id'];
        $loss_stage = $_POST['loss_stage'] ?? '';
        $quantity = (int)$_POST['quantity'];
        $loss_time = $_POST['loss_time'] ?? '';
        $loss_cause = trim($_POST['loss_cause'] ?? '');

        // Server-side validation
        if ($product_id <= 0) {
            $message = '<div class="alert alert-danger">Invalid product selected</div>';
        } elseif (!in_array($loss_stage, $valid_loss_stages)) {
            $message = '<div class="alert alert-danger">Invalid loss stage: ' . htmlspecialchars($loss_stage) . '</div>';
        } elseif ($quantity <= 0) {
            $message = '<div class="alert alert-danger">Quantity must be positive</div>';
        } elseif (empty($loss_time) || strtotime($loss_time) > time()) {
            $message = '<div class="alert alert-danger">Loss time cannot be empty or in the future</div>';
        } elseif (empty($loss_cause)) {
            $message = '<div class="alert alert-danger">Loss cause is required</div>';
        } else {
            // Check if product_id exists in inventory
            $stmt = $conn->prepare("SELECT batch_id FROM inventory WHERE batch_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $message = '<div class="alert alert-danger">Selected product does not exist</div>';
                error_log("Product ID $product_id not found in inventory");
            } else {
                $stmt = $conn->prepare("INSERT INTO losses (product_id, loss_stage, quantity, loss_time, loss_cause) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isiss", $product_id, $loss_stage, $quantity, $loss_time, $loss_cause);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Loss recorded successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error recording loss: ' . htmlspecialchars($stmt->error) . '</div>';
                    error_log("Insert error: " . $stmt->error);
                }
            }
            $stmt->close();
        }
    }
}

// Handle Edit Loss
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_loss'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = '<div class="alert alert-danger">CSRF validation failed</div>';
        error_log("Edit CSRF validation failed. Sent: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: " . $_SESSION['csrf_token']);
    } else {
        $loss_id = (int)$_POST['loss_id'];
        $product_id = (int)$_POST['product_id'];
        $loss_stage = $_POST['loss_stage'] ?? '';
        $quantity = (int)$_POST['quantity'];
        $loss_time = $_POST['loss_time'] ?? '';
        $loss_cause = trim($_POST['loss_cause'] ?? '');

        // Server-side validation
        if ($loss_id <= 0) {
            $message = '<div class="alert alert-danger">Invalid loss ID</div>';
        } elseif ($product_id <= 0) {
            $message = '<div class="alert alert-danger">Invalid product selected</div>';
        } elseif (!in_array($loss_stage, $valid_loss_stages)) {
            $message = '<div class="alert alert-danger">Invalid loss stage: ' . htmlspecialchars($loss_stage) . '</div>';
        } elseif ($quantity <= 0) {
            $message = '<div class="alert alert-danger">Quantity must be positive</div>';
        } elseif (empty($loss_time) || strtotime($loss_time) > time()) {
            $message = '<div class="alert alert-danger">Loss time cannot be empty or in the future</div>';
        } elseif (empty($loss_cause)) {
            $message = '<div class="alert alert-danger">Loss cause is required</div>';
        } else {
            // Check if product_id exists in inventory
            $stmt = $conn->prepare("SELECT batch_id FROM inventory WHERE batch_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $message = '<div class="alert alert-danger">Selected product does not exist</div>';
                error_log("Product ID $product_id not found for edit");
            } else {
                $stmt = $conn->prepare("UPDATE losses SET product_id = ?, loss_stage = ?, quantity = ?, loss_time = ?, loss_cause = ? WHERE loss_id = ?");
                $stmt->bind_param("isisis", $product_id, $loss_stage, $quantity, $loss_time, $loss_cause, $loss_id);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Loss updated successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error updating loss: ' . htmlspecialchars($stmt->error) . '</div>';
                    error_log("Update error: " . $stmt->error);
                }
            }
            $stmt->close();
        }
    }
}

// Handle Delete Loss
if (isset($_GET['delete_id'])) {
    $loss_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM losses WHERE loss_id = ?");
    $stmt->bind_param("i", $loss_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Loss deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting loss: ' . htmlspecialchars($stmt->error) . '</div>';
        error_log("Delete error: " . $stmt->error);
    }
    $stmt->close();
}

// Fetch products for dropdown
$products = $conn->query("SELECT i.batch_id, bt.type FROM inventory i JOIN batch_types bt ON i.type_id = bt.type_id");
$products_data = [];
while ($row = $products->fetch_assoc()) {
    $products_data[] = $row;
}

// Handle filter
$filter_stage = isset($_POST['loss_stage_filter']) ? $_POST['loss_stage_filter'] : '';
$losses_data = [];
if ($filter_stage && $filter_stage !== 'all') {
    $stmt = $conn->prepare("SELECT l.loss_id, l.product_id, bt.type AS product_name, l.loss_stage, l.quantity, l.loss_time, l.loss_cause 
                            FROM losses l 
                            JOIN inventory i ON l.product_id = i.batch_id 
                            JOIN batch_types bt ON i.type_id = bt.type_id 
                            WHERE l.loss_stage = ? 
                            ORDER BY l.loss_time DESC");
    $stmt->bind_param("s", $filter_stage);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $losses_data[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query("SELECT l.loss_id, l.product_id, bt.type AS product_name, l.loss_stage, l.quantity, l.loss_time, l.loss_cause 
                            FROM losses l 
                            JOIN inventory i ON l.product_id = i.batch_id 
                            JOIN batch_types bt ON i.type_id = bt.type_id 
                            ORDER BY l.loss_time DESC");
    while ($row = $result->fetch_assoc()) {
        $losses_data[] = $row;
    }
}

// Fetch data for line chart
$chart_data = [];
$result = $conn->query("SELECT loss_time, SUM(quantity) as total_quantity 
                        FROM losses 
                        GROUP BY loss_time 
                        ORDER BY loss_time");
while ($row = $result->fetch_assoc()) {
    $chart_data[] = ['loss_time' => $row['loss_time'], 'total_quantity' => $row['total_quantity']];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loss Recording - FreshTrack</title>
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
                    <li class="nav-item"><a class="nav-link active" href="loss_recording.php">Loss Recording</a></li>
                    <li class="nav-item"><a class="nav-link" href="analysis_loss_cause.php">Loss Analysis</a></li>
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
                <h1>Loss Recording</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLossModal">Record Loss</button>
            </div>

            <!-- Filter Form -->
            <form method="POST" class="filter-form mb-4">
                <div>
                    <label for="loss_stage_filter" class="form-label">Loss Stage</label>
                    <select class="form-select" id="loss_stage_filter" name="loss_stage_filter">
                        <option value="all" <?php echo $filter_stage === 'all' || !$filter_stage ? 'selected' : ''; ?>>All Stages</option>
                        <option value="harvesting" <?php echo $filter_stage === 'harvesting' ? 'selected' : ''; ?>>Harvesting</option>
                        <option value="storage" <?php echo $filter_stage === 'storage' ? 'selected' : ''; ?>>Storage</option>
                        <option value="handling" <?php echo $filter_stage === 'handling' ? 'selected' : ''; ?>>Handling</option>
                        <option value="transportation" <?php echo $filter_stage === 'transportation' ? 'selected' : ''; ?>>Transportation</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Filter</button>
            </form>

            <!-- Success/Error Message -->
            <?php echo $message; ?>

            <!-- Losses Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Loss Stage</th>
                            <th>Quantity (kg)</th>
                            <th>Loss Time</th>
                            <th>Loss Cause</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($losses_data)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No losses found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($losses_data as $loss): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($loss['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($loss['loss_stage']); ?></td>
                                    <td><?php echo htmlspecialchars($loss['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($loss['loss_time']); ?></td>
                                    <td><?php echo htmlspecialchars($loss['loss_cause']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editLossModal"
                                                data-loss-id="<?php echo $loss['loss_id']; ?>"
                                                data-product-id="<?php echo $loss['product_id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($loss['product_name']); ?>"
                                                data-loss-stage="<?php echo htmlspecialchars($loss['loss_stage']); ?>"
                                                data-quantity="<?php echo $loss['quantity']; ?>"
                                                data-loss-time="<?php echo $loss['loss_time']; ?>"
                                                data-loss-cause="<?php echo htmlspecialchars($loss['loss_cause']); ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <a href="?delete_id=<?php echo $loss['loss_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this loss?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Line Chart -->
            <div class="chart-container">
                <canvas id="lossChart"></canvas>
            </div>
        </section>
    </main>

    <!-- Add Loss Modal -->
    <div class="modal fade" id="addLossModal" tabindex="-1" aria-labelledby="addLossModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLossModalLabel">Record Loss</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addLossForm">
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
                            <label for="loss_stage" class="form-label">Loss Stage</label>
                            <select class="form-select" id="loss_stage" name="loss_stage" required>
                                <option value="">Select Stage</option>
                                <option value="harvesting">Harvesting</option>
                                <option value="storage">Storage</option>
                                <option value="handling">Handling</option>
                                <option value="transportation">Transportation</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity (kg)</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" step="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="loss_time" class="form-label">Loss Time</label>
                            <input type="date" class="form-control" id="loss_time" name="loss_time" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="loss_cause" class="form-label">Loss Cause</label>
                            <input type="text" class="form-control" id="loss_cause" name="loss_cause" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_loss">Record Loss</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Loss Modal -->
    <div class="modal fade" id="editLossModal" tabindex="-1" aria-labelledby="editLossModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLossModalLabel">Edit Loss</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editLossForm">
                    <input type="hidden" id="edit_csrf_token" name="csrf_token">
                    <input type="hidden" id="edit_loss_id" name="loss_id">
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
                            <label for="edit_loss_stage" class="form-label">Loss Stage</label>
                            <select class="form-select" id="edit_loss_stage" name="loss_stage" required>
                                <option value="">Select Stage</option>
                                <option value="harvesting">Harvesting</option>
                                <option value="storage">Storage</option>
                                <option value="handling">Handling</option>
                                <option value="transportation">Transportation</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_quantity" class="form-label">Quantity (kg)</label>
                            <input type="number" class="form-control" id="edit_quantity" name="quantity" min="1" step="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_loss_time" class="form-label">Loss Time</label>
                            <input type="date" class="form-control" id="edit_loss_time" name="loss_time" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_loss_cause" class="form-label">Loss Cause</label>
                            <input type="text" class="form-control" id="edit_loss_cause" name="loss_cause" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="edit_loss">Save Changes</button>
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

        // Populate Edit Modal with loss data
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                console.log('Edit button clicked');
                const lossId = button.dataset.lossId;
                const productId = button.dataset.productId;
                const lossStage = button.dataset.lossStage;
                const quantity = button.dataset.quantity;
                const lossTime = button.dataset.lossTime;
                const lossCause = button.dataset.lossCause;

                console.log('Loss ID:', lossId);
                console.log('Product ID:', productId);
                console.log('Loss Stage:', lossStage);
                console.log('Quantity:', quantity);
                console.log('Loss Time:', lossTime);
                console.log('Loss Cause:', lossCause);
                console.log('Setting CSRF token:', csrfToken);

                // Populate form fields
                document.getElementById('edit_loss_id').value = lossId;
                document.getElementById('edit_quantity').value = quantity;
                document.getElementById('edit_loss_time').value = lossTime;
                document.getElementById('edit_loss_cause').value = lossCause;

                // Set CSRF token
                document.getElementById('edit_csrf_token').value = csrfToken;

                // Set product dropdown
                const productSelect = document.getElementById('edit_product_id');
                productSelect.value = productId || '';
                console.log('Set productSelect.value to:', productSelect.value);

                // Set loss stage dropdown
                const stageSelect = document.getElementById('edit_loss_stage');
                stageSelect.value = lossStage || '';
                console.log('Set stageSelect.value to:', stageSelect.value);
            });
        });

        // Client-side validation for Add Loss form
        document.getElementById('addLossForm').addEventListener('submit', function(e) {
            const quantity = document.getElementById('quantity').value;
            const lossTime = document.getElementById('loss_time').value;
            const today = new Date().toISOString().split('T')[0];

            console.log('Add form submitted');
            console.log('Quantity:', quantity);
            console.log('Loss Time:', lossTime);

            if (quantity <= 0) {
                e.preventDefault();
                alert('Quantity must be positive');
            } else if (lossTime > today) {
                e.preventDefault();
                alert('Loss time cannot be in the future');
            }
        });

        // Client-side validation for Edit Loss form
        document.getElementById('editLossForm').addEventListener('submit', function(e) {
            const quantity = document.getElementById('edit_quantity').value;
            const lossTime = document.getElementById('edit_loss_time').value;
            const today = new Date().toISOString().split('T')[0];

            console.log('Edit form submitted');
            console.log('Quantity:', quantity);
            console.log('Loss Time:', lossTime);
            console.log('CSRF token:', document.getElementById('edit_csrf_token').value);

            if (quantity <= 0) {
                e.preventDefault();
                alert('Quantity must be positive');
            } else if (lossTime > today) {
                e.preventDefault();
                alert('Loss time cannot be in the future');
            }
        });

        // Line Chart
        const ctx = document.getElementById('lossChart').getContext('2d');
        const lossData = <?php echo json_encode($chart_data); ?>;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: lossData.map(item => item.loss_time),
                datasets: [{
                    label: 'Loss Quantity (kg)',
                    data: lossData.map(item => item.total_quantity),
                    borderColor: 'rgba(229, 62, 62, 1)',
                    backgroundColor: 'rgba(229, 62, 62, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Quantity (kg)'
                        },
                        beginAtZero: true
                    }
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