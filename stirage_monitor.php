<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'freshtrack');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize message variable
$message = '';

// Handle Add Storage Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_storage'])) {
    $warehouse_id = $_POST['warehouse_id'];
    $date = $_POST['date'];
    $temperature = $_POST['temperature'];
    $humidity = $_POST['humidity'];

    $stmt = $conn->prepare("INSERT INTO storage (warehouse_id, date, temperature, humidity) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssdd", $warehouse_id, $date, $temperature, $humidity);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Storage record added successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error adding record: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Handle Edit Storage Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_storage'])) {
    $storage_id = $_POST['storage_id'];
    $warehouse_id = $_POST['warehouse_id'];
    $date = $_POST['date'];
    $temperature = $_POST['temperature'];
    $humidity = $_POST['humidity'];

    $stmt = $conn->prepare("UPDATE storage SET warehouse_id = ?, date = ?, temperature = ?, humidity = ? WHERE storage_id = ?");
    $stmt->bind_param("ssddi", $warehouse_id, $date, $temperature, $humidity, $storage_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Storage record updated successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error updating record: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Handle Delete Storage Record
if (isset($_GET['delete_id'])) {
    $storage_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM storage WHERE storage_id = ?");
    $stmt->bind_param("i", $storage_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Storage record deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting record: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Fetch distinct warehouse IDs for filter dropdown
$warehouses = $conn->query("SELECT DISTINCT warehouse_id FROM storage WHERE warehouse_id IS NOT NULL");
$warehouse_data = [];
while ($row = $warehouses->fetch_assoc()) {
    $warehouse_data[] = $row['warehouse_id'];
}

// Handle filter
$filter_warehouse = isset($_POST['warehouse_filter']) ? $_POST['warehouse_filter'] : '';
$storage_data = [];
if ($filter_warehouse && $filter_warehouse !== 'all') {
    $stmt = $conn->prepare("SELECT storage_id, warehouse_id, date, temperature, humidity 
                            FROM storage 
                            WHERE warehouse_id = ? 
                            ORDER BY date DESC");
    $stmt->bind_param("s", $filter_warehouse);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $storage_data[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query("SELECT storage_id, warehouse_id, date, temperature, humidity 
                            FROM storage 
                            ORDER BY date DESC");
    while ($row = $result->fetch_assoc()) {
        $storage_data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Monitoring - FreshTrack</title>
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
                    <li class="nav-item"><a class="nav-link active" href="storage_monitor.php">Storage Monitoring</a></li>
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
                <h1>Storage Monitoring</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStorageModal">Add Storage Record</button>
            </div>

            <!-- Filter Form -->
            <form method="POST" class="filter-form mb-4">
                <div>
                    <label for="warehouse_filter" class="form-label">Warehouse</label>
                    <select class="form-select" id="warehouse_filter" name="warehouse_filter">
                        <option value="all" <?php echo $filter_warehouse === 'all' || !$filter_warehouse ? 'selected' : ''; ?>>All Warehouses</option>
                        <?php foreach ($warehouse_data as $warehouse): ?>
                            <option value="<?php echo htmlspecialchars($warehouse); ?>" 
                                    <?php echo $filter_warehouse === $warehouse ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($warehouse); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Filter</button>
            </form>

            <!-- Success/Error Message -->
            <?php echo $message; ?>

            <!-- Storage Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Warehouse ID</th>
                            <th>Date</th>
                            <th>Temperature (°C)</th>
                            <th>Humidity (%)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($storage_data)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No storage records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($storage_data as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['warehouse_id']); ?></td>
                                    <td><?php echo htmlspecialchars($record['date']); ?></td>
                                    <td><?php echo number_format($record['temperature'], 2); ?></td>
                                    <td><?php echo number_format($record['humidity'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editStorageModal"
                                                data-storage-id="<?php echo $record['storage_id']; ?>"
                                                data-warehouse-id="<?php echo htmlspecialchars($record['warehouse_id']); ?>"
                                                data-date="<?php echo $record['date']; ?>"
                                                data-temperature="<?php echo $record['temperature']; ?>"
                                                data-humidity="<?php echo $record['humidity']; ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <a href="?delete_id=<?php echo $record['storage_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this record?');">
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

    <!-- Add Storage Modal -->
    <div class="modal fade" id="addStorageModal" tabindex="-1" aria-labelledby="addStorageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStorageModalLabel">Add Storage Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="warehouse_id" class="form-label">Warehouse ID</label>
                            <input type="text" class="form-control" id="warehouse_id" name="warehouse_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label for="temperature" class="form-label">Temperature (°C)</label>
                            <input type="number" step="0.01" class="form-control" id="temperature" name="temperature" required>
                        </div>
                        <div class="mb-3">
                            <label for="humidity" class="form-label">Humidity (%)</label>
                            <input type="number" step="0.01" class="form-control" id="humidity" name="humidity" min="0" max="100" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_storage">Add Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Storage Modal -->
    <div class="modal fade" id="editStorageModal" tabindex="-1" aria-labelledby="editStorageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStorageModalLabel">Edit Storage Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_storage_id" name="storage_id">
                        <div class="mb-3">
                            <label for="edit_warehouse_id" class="form-label">Warehouse ID</label>
                            <input type="text" class="form-control" id="edit_warehouse_id" name="warehouse_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="edit_date" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_temperature" class="form-label">Temperature (°C)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_temperature" name="temperature" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_humidity" class="form-label">Humidity (%)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_humidity" name="humidity" min="0" max="100" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="edit_storage">Save Changes</button>
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
        // Populate Edit Modal with record data
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                const storageId = button.dataset.storageId;
                const warehouseId = button.dataset.warehouseId;
                const date = button.dataset.date;
                const temperature = button.dataset.temperature;
                const humidity = button.dataset.humidity;

                document.getElementById('edit_storage_id').value = storageId;
                document.getElementById('edit_warehouse_id').value = warehouseId;
                document.getElementById('edit_date').value = date;
                document.getElementById('edit_temperature').value = temperature;
                document.getElementById('edit_humidity').value = humidity;
            });
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>