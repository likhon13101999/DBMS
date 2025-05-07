<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'freshtrack');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize message variable
$message = '';

// Handle Add Sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sale'])) {
    $salesman_id = $_POST['salesman_id'];
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];
    $amount = $_POST['amount'];
    $retailer_id = $_POST['retailer_id'];

    $stmt = $conn->prepare("INSERT INTO sales (salesman_id, product_id, product_name, quantity, amount, retailer_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisiis", $salesman_id, $product_id, $product_name, $quantity, $amount, $retailer_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Sale added successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error adding sale: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Handle Edit Sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_sale'])) {
    $sale_id = $_POST['sale_id'];
    $salesman_id = $_POST['salesman_id'];
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];
    $amount = $_POST['amount'];
    $retailer_id = $_POST['retailer_id'];

    $stmt = $conn->prepare("UPDATE sales SET salesman_id = ?, product_id = ?, product_name = ?, quantity = ?, amount = ?, retailer_id = ? WHERE sale_id = ?");
    $stmt->bind_param("sisiisi", $salesman_id, $product_id, $product_name, $quantity, $amount, $retailer_id, $sale_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Sale updated successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error updating sale: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Handle Delete Sale
if (isset($_GET['delete_id'])) {
    $sale_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM sales WHERE sale_id = ?");
    $stmt->bind_param("i", $sale_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Sale deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting sale: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Fetch products for dropdown
$products = $conn->query("SELECT i.batch_id, bt.type FROM inventory i JOIN batch_types bt ON i.type_id = bt.type_id");
$products_data = [];
while ($row = $products->fetch_assoc()) {
    $products_data[] = $row;
}

// Fetch distinct retailer IDs for filter dropdown
$retailers = $conn->query("SELECT DISTINCT retailer_id FROM sales WHERE retailer_id IS NOT NULL");
$retailers_data = [];
while ($row = $retailers->fetch_assoc()) {
    $retailers_data[] = $row['retailer_id'];
}

// Handle filter
$filter_retailer = isset($_POST['retailer_filter']) ? $_POST['retailer_filter'] : '';
$sales_data = [];
if ($filter_retailer && $filter_retailer !== 'all') {
    $stmt = $conn->prepare("SELECT sale_id, salesman_id, product_id, product_name, quantity, amount, retailer_id, sale_date 
                            FROM sales 
                            WHERE retailer_id = ? 
                            ORDER BY sale_date DESC");
    $stmt->bind_param("s", $filter_retailer);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sales_data[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query("SELECT sale_id, salesman_id, product_id, product_name, quantity, amount, retailer_id, sale_date 
                            FROM sales 
                            ORDER BY sale_date DESC");
    while ($row = $result->fetch_assoc()) {
        $sales_data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales & Distribution - FreshTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
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
                    <li class="nav-item"><a class="nav-link active" href="sale_record.php">Sales & Distribution</a></li>
                    <li class="nav-item"><a class="nav-link" href="loss_recording.php">Loss Recording</a></li>
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
                <h1>Sales & Distribution</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSaleModal">Add Sale</button>
            </div>

            <!-- Filter Form -->
            <form method="POST" class="filter-form mb-4">
                <div>
                    <label for="retailer_filter" class="form-label">Retailer</label>
                    <select class="form-select" id="retailer_filter" name="retailer_filter">
                        <option value="all" <?php echo $filter_retailer === 'all' || !$filter_retailer ? 'selected' : ''; ?>>All Retailers</option>
                        <?php foreach ($retailers_data as $retailer): ?>
                            <option value="<?php echo htmlspecialchars($retailer); ?>" 
                                    <?php echo $filter_retailer === $retailer ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($retailer); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Filter</button>
            </form>

            <!-- Success/Error Message -->
            <?php echo $message; ?>

            <!-- Sales Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Salesman ID</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                            <th>Retailer ID</th>
                            <th>Sale Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales_data)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No sales found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales_data as $sale): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale['salesman_id']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                    <td><?php echo number_format($sale['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($sale['retailer_id']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editSaleModal"
                                                data-sale-id="<?php echo $sale['sale_id']; ?>"
                                                data-salesman-id="<?php echo htmlspecialchars($sale['salesman_id']); ?>"
                                                data-product-id="<?php echo $sale['product_id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($sale['product_name']); ?>"
                                                data-quantity="<?php echo $sale['quantity']; ?>"
                                                data-amount="<?php echo $sale['amount']; ?>"
                                                data-retailer-id="<?php echo htmlspecialchars($sale['retailer_id']); ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <a href="?delete_id=<?php echo $sale['sale_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this sale?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Add Sale Modal -->
    <div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSaleModalLabel">Add Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="salesman_id" class="form-label">Salesman ID</label>
                            <input type="text" class="form-control" id="salesman_id" name="salesman_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="product_id" class="form-label">Product</label>
                            <select class="form-select" id="product_id" name="product_id" required>
                                <option value="">Select Product</option>
                                <?php foreach ($products_data as $product): ?>
                                    <option value="<?php echo $product['batch_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($product['type']); ?>">
                                        <?php echo htmlspecialchars($product['type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="product_name" name="product_name">
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount ($)</label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="retailer_id" class="form-label">Retailer ID</label>
                            <input type="text" class="form-control" id="retailer_id" name="retailer_id" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_sale">Add Sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Sale Modal -->
    <div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSaleModalLabel">Edit Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_sale_id" name="sale_id">
                        <div class="mb-3">
                            <label for="edit_salesman_id" class="form-label">Salesman ID</label>
                            <input type="text" class="form-control" id="edit_salesman_id" name="salesman_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_id" class="form-label">Product</label>
                            <select class="form-select" id="edit_product_id" name="product_id" required>
                                <option value="">Select Product</option>
                                <?php foreach ($products_data as $product): ?>
                                    <option value="<?php echo $product['batch_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($product['type']); ?>">
                                        <?php echo htmlspecialchars($product['type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="edit_product_name" name="product_name">
                        </div>
                        <div class="mb-3">
                            <label for="edit_quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="edit_quantity" name="quantity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_amount" class="form-label">Amount ($)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_retailer_id" class="form-label">Retailer ID</label>
                            <input type="text" class="form-control" id="edit_retailer_id" name="retailer_id" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="edit_sale">Save Changes</button>
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
        // Populate Edit Modal with sale data
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                const saleId = button.dataset.saleId;
                const salesmanId = button.dataset.salesmanId;
                const productId = button.dataset.productId;
                const productName = button.dataset.productName;
                const quantity = button.dataset.quantity;
                const amount = button.dataset.amount;
                const retailerId = button.dataset.retailerId;

                document.getElementById('edit_sale_id').value = saleId;
                document.getElementById('edit_salesman_id').value = salesmanId;
                document.getElementById('edit_quantity').value = quantity;
                document.getElementById('edit_amount').value = amount;
                document.getElementById('edit_retailer_id').value = retailerId;
                document.getElementById('edit_product_name').value = productName;

                // Set product dropdown
                const productSelect = document.getElementById('edit_product_id');
                Array.from(productSelect.options).forEach(option => {
                    if (option.value === productId) {
                        option.selected = true;
                    }
                });
            });
        });

        // Update product_name on product selection (Add Modal)
        document.getElementById('product_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('product_name').value = selectedOption.dataset.name || '';
        });

        // Update product_name on product selection (Edit Modal)
        document.getElementById('edit_product_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('edit_product_name').value = selectedOption.dataset.name || '';
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>