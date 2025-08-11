<?php
session_start();
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
  header("Location: index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/admin.css">
</head>
<body>

<!-- Menu Top -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">Harbor<span>lights</span></a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="rooms.php" class="nav-link">Rooms</a></li>
        
        <li class="nav-item"><a href="logout.php" class="nav-link">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Admin layout -->
<div class="admin-container">
  <!-- Sidebar -->
  <div class="sidebar">
    <h4><a href="admin.php" style="color: inherit; text-decoration: none;">Admin Menu</a></h4>
    <a href="manage_rooms.php">Manage Rooms</a>
    <a href="manage_reviews.php">Manage Reviews</a>
    <a href="manage_customers.php">Manage Customers</a>
  </div>

  <!-- Main content -->
  <div class="main-content">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
    <p>Select an item from the left menu to manage the system.</p>
  </div>
</div>

<script src="js/custom.js"></script>
</body>
</html>
