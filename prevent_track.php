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

// Handle Add Measure
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_measure'])) {
    $product_id = $_POST['product_id'];
    $date = $_POST['date'];
    $batch_name = $_POST['batch_name'];
    $problem = $_POST['problem'];
    $suggested_solution = $_POST['suggested_solution'];

    $stmt = $conn->prepare("INSERT INTO preventative_measures (product_id, date, batch_name, problem, suggested_solution, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $product_id, $date, $batch_name, $problem, $suggested_solution);
    $stmt->execute();
    $stmt->close();
}

// Handle Add Tracking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tracking'])) {
    $product_id = $_POST['track_product_id'];
    $date = $_POST['track_date'];
    $batch_name = $_POST['track_batch_name'];
    $solution_applied = $_POST['solution_applied'];
    $improvement_status = $_POST['improvement_status'];

    $stmt = $conn->prepare("INSERT INTO improvement_tracking (product_id, date, batch_name, solution_applied, improvement_status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $product_id, $date, $batch_name, $solution_applied, $improvement_status);
    $stmt->execute();
    $stmt->close();
}

// Handle Edit Measure
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_measure'])) {
    $measure_id = $_POST['measure_id'];
    $product_id = $_POST['product_id'];
    $date = $_POST['date'];
    $batch_name = $_POST['batch_name'];
    $problem = $_POST['problem'];
    $suggested_solution = $_POST['suggested_solution'];

    $stmt = $conn->prepare("UPDATE preventative_measures SET product_id = ?, date = ?, batch_name = ?, problem = ?, suggested_solution = ? WHERE measure_id = ?");
    $stmt->bind_param("sssssi", $product_id, $date, $batch_name, $problem, $suggested_solution, $measure_id);
    $stmt->execute();
    $stmt->close();
}

// Handle Edit Tracking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_tracking'])) {
    $tracking_id = $_POST['tracking_id'];
    $product_id = $_POST['track_product_id'];
    $date = $_POST['track_date'];
    $batch_name = $_POST['track_batch_name'];
    $solution_applied = $_POST['solution_applied'];
    $improvement_status = $_POST['improvement_status'];

    $stmt = $conn->prepare("UPDATE improvement_tracking SET product_id = ?, date = ?, batch_name = ?, solution_applied = ?, improvement_status = ? WHERE tracking_id = ?");
    $stmt->bind_param("sssssi", $product_id, $date, $batch_name, $solution_applied, $improvement_status, $tracking_id);
    $stmt->execute();
    $stmt->close();
}

// Handle Delete Measure
if (isset($_GET['delete_measure'])) {
    $measure_id = $_GET['delete_measure'];
    $stmt = $conn->prepare("DELETE FROM preventative_measures WHERE measure_id = ?");
    $stmt->bind_param("i", $measure_id);
    $stmt->execute();
    $stmt->close();
    header("Location: prevent_track.php");
    exit();
}

// Handle Delete Tracking
if (isset($_GET['delete_tracking'])) {
    $tracking_id = $_GET['delete_tracking'];
    $stmt = $conn->prepare("DELETE FROM improvement_tracking WHERE tracking_id = ?");
    $stmt->bind_param("i", $tracking_id);
    $stmt->execute();
    $stmt->close();
    header("Location: prevent_track.php");
    exit();
}

// Fetch Preventative Measures
$measures_result = $conn->query("SELECT * FROM preventative_measures");

// Fetch Improvement Tracking
$tracking_result = $conn->query("SELECT * FROM improvement_tracking");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Preventative Measures - FreshTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #ffffff;
      color: #1a202c;
      margin: 0;
    }
    .navbar {
      background-color: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      padding: 0.75rem 0;
    }
    .navbar-brand {
      font-size: 1.5rem;
      font-weight: 600;
      color: #1a202c;
    }
    .nav-link {
      color: #1a202c;
      font-size: 0.85rem;
      font-weight: 500;
      padding: 0.5rem 0.75rem;
      transition: color 0.2s ease;
    }
    .nav-link:hover {
      color: #4a5568;
    }
    .btn-logout {
      background-color: #e53e3e;
      color: #ffffff;
      border: none;
      font-size: 0.85rem;
      padding: 0.4rem 1rem;
      border-radius: 6px;
      transition: background-color 0.2s ease;
    }
    .btn-logout:hover {
      background-color: #c53030;
    }
    .main-content {
      padding: 80px 0;
    }
    h2, h3 {
      font-size: 1.75rem;
      font-weight: 600;
      margin-bottom: 1rem;
      text-align: center;
    }
    h3 {
      font-size: 1.25rem;
    }
    .controls {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 1rem;
      align-items: center;
    }
    .btn-primary {
      background-color: #1a202c;
      border: none;
      font-size: 0.85rem;
      padding: 0.4rem 0.75rem;
      border-radius: 6px;
      transition: background-color 0.2s ease;
    }
    .btn-primary:hover {
      background-color: #4a5568;
    }
    .form-control, .form-select {
      font-size: 0.85rem;
      padding: 0.4rem 0.75rem;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
      transition: border-color 0.2s ease;
    }
    .form-control:focus, .form-select:focus {
      border-color: #1a202c;
      box-shadow: none;
    }
    .table {
      background-color: #ffffff;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #e2e8f0;
    }
    .table th {
      font-size: 0.75rem;
      font-weight: 500;
      color: #1a202c;
      background-color: #f7fafc;
      padding: 0.5rem;
      border-bottom: 1px solid #e2e8f0;
    }
    .table td {
      font-size: 0.85rem;
      color: #4a5568;
      padding: 0.5rem;
      border-bottom: 1px solid #e2e8f0;
    }
    .table tr:hover {
      background-color: #f7fafc;
    }
    .action-icons i {
      font-size: 0.9rem;
      cursor: pointer;
      margin: 0 0.3rem;
      transition: color 0.2s ease;
    }
    .action-icons .bi-pencil-square:hover {
      color: #1a202c;
    }
    .action-icons .bi-trash:hover {
      color: #e53e3e;
    }
    .modal-content {
      border: none;
      border-radius: 8px;
      background-color: #ffffff;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }
    .modal-header {
      border-bottom: 1px solid #e2e8f0;
      padding: 0.75rem;
    }
    .modal-title {
      font-size: 1.1rem;
      font-weight: 500;
      color: #1a202c;
    }
    .modal-body {
      padding: 1rem;
    }
    .modal-footer {
      border-top: 1px solid #e2e8f0;
      padding: 0.75rem;
    }
    .form-label {
      font-size: 0.75rem;
      font-weight: 500;
      color: #1a202c;
      margin-bottom: 0.25rem;
    }
    .form-control-modal {
      font-size: 0.85rem;
      padding: 0.4rem 0.75rem;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
      transition: border-color 0.2s ease;
    }
    .form-control-modal:focus {
      border-color: #1a202c;
      box-shadow: none;
    }
    .btn-secondary {
      background-color: #e53e3e;
      border: none;
      font-size: 0.85rem;
      padding: 0.4rem 0.75rem;
      border-radius: 6px;
      transition: background-color 0.2s ease;
    }
    .btn-secondary:hover {
      background-color: #c53030;
    }
    .section {
      margin-bottom: 2rem;
    }
    footer {
      background-color: #ffffff;
      border-top: 1px solid #e2e8f0;
      padding: 1rem 0;
      text-align: center;
    }
    footer p {
      font-size: 0.85rem;
      color: #4a5568;
      margin: 0;
    }
    footer a {
      color: #1a202c;
      text-decoration: none;
      transition: color 0.2s ease;
    }
    footer a:hover {
      color: #e53e3e;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
      animation: fadeIn 0.5s ease-out;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <a class="navbar-brand" href="index.php">FreshTrack</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="inventory_tracking.php">Inventory Tracking</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="stirage_monitor.php">Storage Monitoring</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="sale_record.php">Sales & Distribution</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="loss_recording.php">Loss Recording</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="analysis_loss_cause.php">Loss Analysis</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="prevent_track.php">Preventative Measures</a>
          </li>
          <li class="nav-item">
            <button class="btn btn-logout btn-sm">Logout</button>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <section class="main-content">
    <div class="container">
      <h2 class="animate-fade-in">Preventative Measures</h2>

      <!-- Implementation Section -->
      <div class="section">
        <h3 class="animate-fade-in">Implementation</h3>
        <div class="controls">
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMeasureModal">
            <i class="bi bi-plus"></i> Add New Measure
          </button>
          <input type="text" class="form-control w-auto" placeholder="Search measures...">
          <select class="form-select w-auto">
            <option value="">Filter by Problem</option>
            <option value="physical_damage">Physical Damage</option>
            <option value="spoilage">Spoilage</option>
            <option value="pest_infestation">Pest Infestation</option>
            <option value="temperature_fluctuation">Temperature Fluctuation</option>
          </select>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Measure ID</th>
                <th>Product ID</th>
                <th>Date</th>
                <th>Batch Name</th>
                <th>Problem</th>
                <th>Suggested Solution</th>
                <th>Created At</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $measures_result->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['measure_id']); ?></td>
                  <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                  <td><?php echo htmlspecialchars($row['date']); ?></td>
                  <td><?php echo htmlspecialchars($row['batch_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['problem']); ?></td>
                  <td><?php echo htmlspecialchars($row['suggested_solution']); ?></td>
                  <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                  <td class="action-icons">
                    <i class="bi bi-pencil-square" data-bs-toggle="modal" data-bs-target="#editMeasureModal" 
                       data-id="<?php echo $row['measure_id']; ?>" 
                       data-product-id="<?php echo $row['product_id']; ?>" 
                       data-date="<?php echo $row['date']; ?>" 
                       data-batch-name="<?php echo $row['batch_name']; ?>" 
                       data-problem="<?php echo $row['problem']; ?>" 
                       data-solution="<?php echo $row['suggested_solution']; ?>"></i>
                    <a href="?delete_measure=<?php echo $row['measure_id']; ?>" onclick="return confirm('Are you sure you want to delete this measure?');">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tracking Improvements Section -->
      <div class="section">
        <h3 class="animate-fade-in">Tracking Improvements</h3>
        <div class="controls">
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTrackingModal">
            <i class="bi bi-plus"></i> Add New Tracking
          </button>
          <input type="text" class="form-control w-auto" placeholder="Search tracking...">
          <select class="form-select w-auto">
            <option value="">Filter by Status</option>
            <option value="improved">Improved</option>
            <option value="no_improvement">No Improvement</option>
          </select>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Tracking ID</th>
                <th>Product ID</th>
                <th>Date</th>
                <th>Batch Name</th>
                <th>Solution Applied</th>
                <th>Improvement Status</th>
                <th>Created At</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $tracking_result->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['tracking_id']); ?></td>
                  <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                  <td><?php echo htmlspecialchars($row['date']); ?></td>
                  <td><?php echo htmlspecialchars($row['batch_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['solution_applied']); ?></td>
                  <td><?php echo htmlspecialchars($row['improvement_status']); ?></td>
                  <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                  <td class="action-icons">
                    <i class="bi bi-pencil-square" data-bs-toggle="modal" data-bs-target="#editTrackingModal" 
                       data-id="<?php echo $row['tracking_id']; ?>" 
                       data-product-id="<?php echo $row['product_id']; ?>" 
                       data-date="<?php echo $row['date']; ?>" 
                       data-batch-name="<?php echo $row['batch_name']; ?>" 
                       data-solution="<?php echo $row['solution_applied']; ?>" 
                       data-status="<?php echo $row['improvement_status']; ?>"></i>
                    <a href="?delete_tracking=<?php echo $row['tracking_id']; ?>" onclick="return confirm('Are you sure you want to delete this tracking record?');">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <!-- Add New Measure Modal -->
  <div class="modal fade" id="addMeasureModal" tabindex="-1" aria-labelledby="addMeasureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addMeasureModalLabel">Add New Preventative Measure</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST">
            <div class="mb-2">
              <label for="productId" class="form-label">Product ID</label>
              <input type="text" class="form-control-modal" id="productId" name="product_id" placeholder="e.g., P001" required>
            </div>
            <div class="mb-2">
              <label for="date" class="form-label">Date</label>
              <input type="date" class="form-control-modal" id="date" name="date" required>
            </div>
            <div class="mb-2">
              <label for="batchName" class="form-label">Batch Name</label>
              <input type="text" class="form-control-modal" id="batchName" name="batch_name" placeholder="e.g., Batch A" required>
            </div>
            <div class="mb-2">
              <label for="problem" class="form-label">Problem</label>
              <select class="form-control-modal" id="problem" name="problem" required>
                <option value="physical_damage">Physical Damage</option>
                <option value="spoilage">Spoilage</option>
                <option value="pest_infestation">Pest Infestation</option>
                <option value="temperature_fluctuation">Temperature Fluctuation</option>
              </select>
            </div>
            <div class="mb-2">
              <label for="solution" class="form-label">Suggested Solution</label>
              <input type="text" class="form-control-modal" id="solution" name="suggested_solution" placeholder="e.g., Improved packaging" required>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm" name="add_measure">Add Measure</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Measure Modal -->
  <div class="modal fade" id="editMeasureModal" tabindex="-1" aria-labelledby="editMeasureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editMeasureModalLabel">Edit Preventative Measure</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div the="modal-body">
          <form method="POST">
            <input type="hidden" id="editMeasureId" name="measure_id">
            <div class="mb-2">
              <label for="editProductId" class="form-label">Product ID</label>
              <input type="text" class="form-control-modal" id="editProductId" name="product_id" placeholder="e.g., P001" required>
            </div>
            <div class="mb-2">
              <label for="editDate" class="form-label">Date</label>
              <input type="date" class="form-control-modal" id="editDate" name="date" required>
            </div>
            <div class="mb-2">
              <label for="editBatchName" class="form-label">Batch Name</label>
              <input type="text" class="form-control-modal" id="editBatchName" name="batch_name" placeholder="e.g., Batch A" required>
            </div>
            <div class="mb-2">
              <label for="editProblem" class="form-label">Problem</label>
              <select class="form-control-modal" id="editProblem" name="problem" required>
                <option value="physical_damage">Physical Damage</option>
                <option value="spoilage">Spoilage</option>
                <option value="pest_infestation">Pest Infestation</option>
                <option value="temperature_fluctuation">Temperature Fluctuation</option>
              </select>
            </div>
            <div class="mb-2">
              <label for="editSolution" class="form-label">Suggested Solution</label>
              <input type="text" class="form-control-modal" id="editSolution" name="suggested_solution" placeholder="e.g., Improved packaging" required>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm" name="edit_measure">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Add New Tracking Modal -->
  <div class="modal fade" id="addTrackingModal" tabindex="-1" aria-labelledby="addTrackingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addTrackingModalLabel">Add New Tracking Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST">
            <div class="mb-2">
              <label for="trackProductId" class="form-label">Product ID</label>
              <input type="text" class="form-control-modal" id="trackProductId" name="track_product_id" placeholder="e.g., P001" required>
            </div>
            <div class="mb-2">
              <label for="trackDate" class="form-label">Date</label>
              <input type="date" class="form-control-modal" id="trackDate" name="track_date" required>
            </div>
            <div class="mb-2">
              <label for="trackBatchName" class="form-label">Batch Name</label>
              <input type="text" class="form-control-modal" id="trackBatchName" name="track_batch_name" placeholder="e.g., Batch A" required>
            </div>
            <div class="mb-2">
              <label for="solutionApplied" class="form-label">Solution Applied</label>
              <input type="text" class="form-control-modal" id="solutionApplied" name="solution_applied" placeholder="e.g., Improved packaging" required>
            </div>
            <div class="mb-2">
              <label for="improvementStatus" class="form-label">Improvement Status</label>
              <select class="form-control-modal" id="improvementStatus" name="improvement_status" required>
                <option value="improved">Improved</option>
                <option value="no_improvement">No Improvement</option>
              </select>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm" name="add_tracking">Add Tracking</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Tracking Modal -->
  <div class="modal fade" id="editTrackingModal" tabindex="-1" aria-labelledby="editTrackingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editTrackingModalLabel">Edit Tracking Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST">
            <input type="hidden" id="editTrackingId" name="tracking_id">
            <div class="mb-2">
              <label for="editTrackProductId" class="form-label">Product ID</label>
              <input type="text" class="form-control-modal" id="editTrackProductId" name="track_product_id" placeholder="e.g., P001" required>
            </div>
            <div class="mb-2">
              <label for="editTrackDate" class="form-label">Date</label>
              <input type="date" class="form-control-modal" id="editTrackDate" name="track_date" required>
            </div>
            <div class="mb-2">
              <label for="editTrackBatchName" class="form-label">Batch Name</label>
              <input type="text" class="form-control-modal" id="editTrackBatchName" name="track_batch_name" placeholder="e.g., Batch A" required>
            </div>
            <div class="mb-2">
              <label for="editSolutionApplied" class="form-label">Solution Applied</label>
              <input type="text" class="form-control-modal" id="editSolutionApplied" name="solution_applied" placeholder="e.g., Improved packaging" required>
            </div>
            <div class="mb-2">
              <label for="editImprovementStatus" class="form-label">Improvement Status</label>
              <select class="form-control-modal" id="editImprovementStatus" name="improvement_status" required>
                <option value="improved">Improved</option>
                <option value="no_improvement">No Improvement</option>
              </select>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm" name="edit_tracking">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <div class="container">
      <p>© 2025 <a href="index.html">FreshTrack</a>. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Populate Edit Measure Modal
    const editMeasureModal = document.getElementById('editMeasureModal');
    editMeasureModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const id = button.getAttribute('data-id');
      const productId = button.getAttribute('data-product-id');
      const date = button.getAttribute('data-date');
      const batchName = button.getAttribute('data-batch-name');
      const problem = button.getAttribute('data-problem');
      const solution = button.getAttribute('data-solution');

      const modal = this;
      modal.querySelector('#editMeasureId').value = id;
      modal.querySelector('#editProductId').value = productId;
      modal.querySelector('#editDate').value = date;
      modal.querySelector('#editBatchName').value = batchName;
      modal.querySelector('#editProblem').value = problem;
      modal.querySelector('#editSolution').value = solution;
    });

    // Populate Edit Tracking Modal
    const editTrackingModal = document.getElementById('editTrackingModal');
    editTrackingModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const id = button.getAttribute('data-id');
      const productId = button.getAttribute('data-product-id');
      const date = button.getAttribute('data-date');
      const batchName = button.getAttribute('data-batch-name');
      const solution = button.getAttribute('data-solution');
      const status = button.getAttribute('data-status');

      const modal = this;
      modal.querySelector('#editTrackingId').value = id;
      modal.querySelector('#editTrackProductId').value = productId;
      modal.querySelector('#editTrackDate').value = date;
      modal.querySelector('#editTrackBatchName').value = batchName;
      modal.querySelector('#editSolutionApplied').value = solution;
      modal.querySelector('#editImprovementStatus').value = status;
    });
  </script>
</body>
</html>

<?php
$conn->close();
?>