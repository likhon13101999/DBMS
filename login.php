<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - FreshTrack</title>
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
    .login-container {
      max-width: 700px;
      margin: 80px auto;
      display: flex;
      align-items: center;
      background-color: #ffffff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }
    .left-panel, .right-panel {
      flex: 1;
      padding: 2rem;
    }
    .left-panel {
      background-color: #f7fafc;
      color: #1a202c;
    }
    .left-panel h1 {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    .left-panel p {
      font-size: 0.85rem;
      color: #4a5568;
    }
    .form-group {
      position: relative;
      margin-bottom: 1rem;
    }
    .form-group i {
      position: absolute;
      left: 0.75rem;
      top: 2.2rem;
      color: #4a5568;
      font-size: 0.9rem;
    }
    .form-control {
      font-size: 0.85rem;
      padding: 0.5rem 0.75rem 0.5rem 2rem;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
      transition: border-color 0.2s ease;
    }
    .form-control:focus {
      border-color: #1a202c;
      box-shadow: none;
      outline: none;
    }
    .form-label {
      font-size: 0.75rem;
      font-weight: 500;
      color: #1a202c;
    }
    .btn-login {
      background-color: #1a202c;
      color: #ffffff;
      border: none;
      font-size: 0.85rem;
      padding: 0.5rem;
      border-radius: 6px;
      width: 100%;
      transition: background-color 0.2s ease;
    }
    .btn-login:hover {
      background-color: #4a5568;
    }
    .forgot-password {
      font-size: 0.75rem;
      color: #4a5568;
      text-decoration: none;
      display: block;
      text-align: center;
      margin-top: 0.5rem;
      transition: color 0.2s ease;
    }
    .forgot-password:hover {
      color: #e53e3e;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
      animation: fadeIn 0.5s ease-out;
    }
    @media (max-width: 768px) {
      .login-container {
        flex-direction: column;
        margin: 20px;
      }
      .left-panel, .right-panel {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar fixed-top">
    <div class="container">
      <a class="navbar-brand" href="#">FreshTrack</a>
    </div>
  </nav>

  <!-- Login Content -->
  <div class="login-container animate-fade-in">
    <div class="left-panel">
      <h1>Welcome to FreshTrack</h1>
      <p>Streamline inventory and minimize post-harvest losses.</p>
    </div>
    <div class="right-panel">
      <form>
        <div class="form-group">
          <label for="username" class="form-label">Username</label>
          <i class="bi bi-person"></i>
          <input type="text" class="form-control" id="username" placeholder="Enter username" required>
        </div>
        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <i class="bi bi-lock"></i>
          <input type="password" class="form-control" id="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn btn-login">Login</button>
        <a href="#" class="forgot-password">Forgot Password?</a>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>