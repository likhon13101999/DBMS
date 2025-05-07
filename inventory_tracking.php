<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'freshtrack');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize message variable
$message = '';

// Handle Add Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $type_id = $_POST['type_id'];
    $quantity = $_POST['quantity'];
    $harvest_date = $_POST['harvest_date'];
    $expiry_date = $_POST['expiry_date'];
    $storage_location = $_POST['storage_location'];

    $stmt = $conn->prepare("INSERT INTO inventory (type_id, quantity, harvest_date, expiry_date, storage_location) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $type_id, $quantity, $harvest_date, $expiry_date, $storage_location);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Item added successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error adding item: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Handle Edit Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_item'])) {
    $batch_id = $_POST['batch_id'];
    $type_id = $_POST['type_id'];
    $quantity = $_POST['quantity'];
    $harvest_date = $_POST['harvest_date'];
    $expiry_date = $_POST['expiry_date'];
    $storage_location = $_POST['storage_location'];

    $stmt = $conn->prepare("UPDATE inventory SET type_id = ?, quantity = ?, harvest_date = ?, expiry_date = ?, storage_location = ? WHERE batch_id = ?");
    $stmt->bind_param("iisssi", $type_id, $quantity, $harvest_date, $expiry_date, $storage_location, $batch_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Item updated successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error updating item: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Handle Delete Item
if (isset($_GET['delete_id'])) {
    $batch_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM inventory WHERE batch_id = ?");
    $stmt->bind_param("i", $batch_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Item deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting item: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Fetch batch types for dropdown
$batch_types = $conn->query("SELECT * FROM batch_types");
$batch_types_data = [];
while ($row = $batch_types->fetch_assoc()) {
    $batch_types_data[] = $row;
}

// Fetch distinct storage locations for filter dropdown
$storage_locations = $conn->query("SELECT DISTINCT storage_location FROM inventory WHERE storage_location IS NOT NULL");
$storage_locations_data = [];
while ($row = $storage_locations->fetch_assoc()) {
    $storage_locations_data[] = $row['storage_location'];
}

// Handle filter
$filter_location = isset($_POST['storage_location_filter']) ? $_POST['storage_location_filter'] : '';
$inventory_data = [];
if ($filter_location && $filter_location !== 'all') {
    $stmt = $conn->prepare("SELECT i.batch_id, bt.type, i.quantity, i.harvest_date, i.expiry_date, i.storage_location 
                            FROM inventory i 
                            JOIN batch_types bt ON i.type_id = bt.type_id 
                            WHERE i.storage_location = ?");
    $stmt->bind_param("s", $filter_location);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $inventory_data[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query("SELECT i.batch_id, bt.type, i.quantity, i.harvest_date, i.expiry_date, i.storage_location 
                            FROM inventory i 
                            JOIN batch_types bt ON i.type_id = bt.type_id");
    while ($row = $result->fetch_assoc()) {
        $inventory_data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Tracking - FreshTrack</title>
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
                    <li class="nav-item"><a class="nav-link active" href="inventory_tracking.php">Inventory Tracking</a></li>
                    <li class="nav-item"><a class="nav-link" href="stirage_monitor.php">Storage Monitoring</a></li>
                    <li class="nav-item"><a class="nav-link" href="sale_record.php">Sales & Distribution</a></li>
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
                <h1>Inventory Tracking</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">Add Item</button>
            </div>

            <!-- Filter Form -->
            <form method="POST" class="filter-form mb-4">
                <div>
                    <label for="storage_location_filter" class="form-label">Storage Location</label>
                    <select class="form-select" id="storage_location_filter" name="storage_location_filter">
                        <option value="all" <?php echo $filter_location === 'all' || !$filter_location ? 'selected' : ''; ?>>All Locations</option>
                        <?php foreach ($storage_locations_data as $location): ?>
                            <option value="<?php echo htmlspecialchars($location); ?>" 
                                    <?php echo $filter_location === $location ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Filter</button>
            </form>

            <!-- Success/Error Message -->
            <?php echo $message; ?>

            <!-- Inventory Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Harvest Date</th>
                            <th>Expiry Date</th>
                            <th>Storage Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventory_data)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No items found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventory_data as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['batch_id']); ?></td>
                                    <td><?php echo htmlspecialchars($item['type']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($item['harvest_date']); ?></td>
                                    <td><?php echo htmlspecialchars($item['expiry_date']); ?></td>
                                    <td><?php echo htmlspecialchars($item['storage_location']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editItemModal"
                                                data-batch-id="<?php echo $item['batch_id']; ?>"
                                                data-type="<?php echo $item['type']; ?>"
                                                data-quantity="<?php echo $item['quantity']; ?>"
                                                data-harvest-date="<?php echo $item['harvest_date']; ?>"
                                                data-expiry-date="<?php echo $item['expiry_date']; ?>"
                                                data-storage-location="<?php echo $item['storage_location']; ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <a href="?delete_id=<?php echo $item['batch_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this item?');">
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

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">Add Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="type_id" class="form-label">Type</label>
                            <select class="form-select" id="type_id" name="type_id" required>
                                <option value="">Select Type</option>
                                <?php foreach ($batch_types_data as $type): ?>
                                    <option value="<?php echo $type['type_id']; ?>">
                                        <?php echo htmlspecialchars($type['type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity (kg)</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="harvest_date" class="form-label">Harvest Date</label>
                            <input type="date" class="form-control" id="harvest_date" name="harvest_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="expiry_date" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="storage_location" class="form-label">Storage Location</label>
                            <input type="text" class="form-control" id="storage_location" name="storage_location" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_item">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">Edit Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_batch_id" name="batch_id">
                        <div class="mb-3">
                            <label for="edit_type_id" class="form-label">Type</label>
                            <select class="form-select" id="edit_type_id" name="type_id" required>
                                <option value="">Select Type</option>
                                <?php foreach ($batch_types_data as $type): ?>
                                    <option value="<?php echo $type['type_id']; ?>">
                                        <?php echo htmlspecialchars($type['type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_quantity" class="form-label">Quantity (kg)</label>
                            <input type="number" class="form-control" id="edit_quantity" name="quantity" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_harvest_date" class="form-label">Harvest Date</label>
                            <input type="date" class="form-control" id="edit_harvest_date" name="harvest_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_expiry_date" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="edit_expiry_date" name="expiry_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_storage_location" class="form-label">Storage Location</label>
                            <input type="text" class="form-control" id="edit_storage_location" name="storage_location" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="edit_item">Save Changes</button>
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
        // Populate Edit Modal with item data
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                const batchId = button.dataset.batchId;
                const type = button.dataset.type;
                const quantity = button.dataset.quantity;
                const harvestDate = button.dataset.harvestDate;
                const expiryDate = button.dataset.expiryDate;
                const storageLocation = button.dataset.storageLocation;

                document.getElementById('edit_batch_id').value = batchId;
                document.getElementById('edit_quantity').value = quantity;
                document.getElementById('edit_harvest_date').value = harvestDate;
                document.getElementById('edit_expiry_date').value = expiryDate;
                document.getElementById('edit_storage_location').value = storageLocation;

                // Set the type dropdown (match by text and get type_id)
                const typeSelect = document.getElementById('edit_type_id');
                Array.from(typeSelect.options).forEach(option => {
                    if (option.text === type) {
                        option.selected = true;
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>