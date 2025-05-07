<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perishable Goods Admin Dashboard - FreshTrack</title>
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
    .hero-section {
      padding: 80px 0;
      text-align: center;
      background-color: #ffffff;
    }
    .hero-section h1 {
      font-size: 2.25rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    .hero-section p {
      font-size: 1rem;
      color: #4a5568;
      max-width: 500px;
      margin: 0 auto;
    }
    .section {
      padding: 60px 0;
    }
    .section h2 {
      font-size: 1.75rem;
      font-weight: 600;
      text-align: center;
      margin-bottom: 1rem;
    }
    .card {
      border: none;
      border-radius: 8px;
      background-color: #ffffff;
      padding: 1rem;
      transition: box-shadow 0.2s ease;
    }
    .card:hover {
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }
    .card-icon {
      font-size: 1.5rem;
      color: #4a5568;
      margin-bottom: 0.5rem;
    }
    .card h3 {
      font-size: 1.1rem;
      font-weight: 500;
      color: #1a202c;
    }
    .card p {
      font-size: 0.85rem;
      color: #4a5568;
    }
    .stat-card {
      border-radius: 8px;
      background-color: #f7fafc;
      padding: 1rem;
      text-align: center;
    }
    .stat-card p.fs-3 {
      font-size: 1.5rem;
      font-weight: 500;
      color: #1a202c;
      margin-bottom: 0.25rem;
    }
    .stat-card p.text-muted {
      font-size: 0.85rem;
      color: #4a5568;
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
            <a class="nav-link active" href="index.php">Home</a>
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
            <a class="nav-link" href="prevent_track.php">Preventative Measures</a>
          </li>
          <li class="nav-item">
            <button class="btn btn-logout btn-sm">Logout</button>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container animate-fade-in">
      <h1>Welcome to FreshTrack</h1>
      <p>Manage perishable goods with real-time insights and precision.</p>
    </div>
  </section>

  <!-- Admin Benefits Section -->
  <section class="section">
    <div class="container">
      <h2 class="animate-fade-in">Empowering Your Workflow</h2>
      <div class="row row-cols-1 row-cols-lg-3 g-3">
        <div class="col">
          <div class="card p-3 text-center animate-fade-in">
            <i class="bi bi-clock card-icon"></i>
            <h3>Instant Monitoring</h3>
            <p>Track inventory and conditions in real time.</p>
          </div>
        </div>
        <div class="col">
          <div class="card p-3 text-center animate-fade-in">
            <i class="bi bi-shield-check card-icon"></i>
            <h3>Spoilage Prevention</h3>
            <p>Identify and address risks to minimize waste.</p>
          </div>
        </div>
        <div class="col">
          <div class="card p-3 text-center animate-fade-in">
            <i class="bi bi-bar-chart card-icon"></i>
            <h3>Actionable Insights</h3>
            <p>Optimize processes with data analytics.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="section">
    <div class="container">
      <h2 class="animate-fade-in">Core Features</h2>
      <div class="row row-cols-1 row-cols-lg-2 g-3">
        <div class="col">
          <div class="card p-3 animate-fade-in">
            <h3>Inventory Tracking</h3>
            <p>Monitor type, quantity, and storage location.</p>
          </div>
        </div>
        <div class="col">
          <div class="card p-3 animate-fade-in">
            <h3>Storage Monitoring</h3>
            <p>Track temperature and humidity in real time.</p>
          </div>
        </div>
        <div class="col">
          <div class="card p-3 animate-fade-in">
            <h3>Sales & Distribution</h3>
            <p>Integrate sales and distribution records.</p>
          </div>
        </div>
        <div class="col">
          <div class="card p-3 animate-fade-in">
            <h3>Loss Recording</h3>
            <p>Log losses to pinpoint inefficiencies.</p>
          </div>
        </div>
        <div class="col">
          <div class="card p-3 animate-fade-in">
            <h3>Loss Analysis</h3>
            <p>Analyze spoilage causes with alerts.</p>
          </div>
        </div>
        <div class="col">
          <div class="card p-3 animate-fade-in">
            <h3>Preventative Measures</h3>
            <p>Implement strategies to reduce losses.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Unique Stats Section -->
  <section class="section">
    <div class="container">
      <h2 class="animate-fade-in">At a Glance</h2>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
        <div class="col">
          <div class="stat-card p-3 animate-fade-in">
            <i class="bi bi-box-seam card-icon"></i>
            <p class="fs-3">12,345 kg</p>
            <p class="text-muted">Total Inventory</p>
          </div>
        </div>
        <div class="col">
          <div class="stat-card p-3 animate-fade-in">
            <i class="bi bi-bell card-icon"></i>
            <p class="fs-3">8</p>
            <p class="text-muted">Active Alerts</p>
          </div>
        </div>
        <div class="col">
          <div class="stat-card p-3 animate-fade-in">
            <i class="bi bi-graph-down card-icon"></i>
            <p class="fs-3">2.3%</p>
            <p class="text-muted">Loss Rate</p>
          </div>
        </div>
        <div class="col">
          <div class="stat-card p-3 animate-fade-in">
            <i class="bi bi-house-door card-icon"></i>
            <p class="fs-3">24</p>
            <p class="text-muted">Storage Units</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div class="container">
      <p>© 2025 <a href="#">FreshTrack</a>. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>