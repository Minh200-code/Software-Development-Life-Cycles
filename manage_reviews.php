<?php
session_start();
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
  header("Location: index.php");
  exit();
}

// Database connection configuration
$servername = "sql203.infinityfree.com";
$username = "if0_39667996";
$password = "3xJyzO66bT";
$dbname = "if0_39667996_asm";

$conn = new mysqli($servername, $username, $password, $dbname);



$reviews = $conn->query("SELECT r.*, u.Username, ro.Room_name 
                         FROM review r 
                         JOIN user u ON r.Customer_code = u.User_code 
                         JOIN room ro ON r.Room_code = ro.Room_code
                         ORDER BY r.Review_code DESC");


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Review Management</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">Harbor<span>Lights</span></a>
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
  <div class="sidebar">
    <h4><a href="admin.php" style="color: inherit; text-decoration: none;">Admin Menu</a></h4>
    <a href="manage_rooms.php">Manage Rooms</a>
    <a href="manage_reviews.php" class="active">Manage Reviews</a>
    <a href="manage_customers.php">Manage Customers</a>
  </div>

  <div class="main-content">
    <h2>Review Management</h2>
    <p class="text-muted">View all customer reviews and ratings.</p>

    <!-- Review List -->
    <h3>Review List</h3>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="thead-dark">
          <tr>
            <th>Review Code</th>
            <th>Customer</th>
            <th>Room</th>
            <th>Booking Code</th>
            <th>Rating</th>
            <th>Comment</th>
          </tr>
        </thead>
        <tbody>
                      <?php if ($reviews && $reviews->num_rows > 0): ?>
              <?php while ($r = $reviews->fetch_assoc()): ?>
                <tr>
                  <td><?= $r['Review_code'] ?></td>
                  <td><?= htmlspecialchars($r['Username']) ?></td>
                  <td><?= htmlspecialchars($r['Room_name']) ?></td>
                  <td><?= $r['Booking_code'] ?? 'N/A' ?></td>
                  <td>
                    <span class="badge badge-warning"><?= $r['Rating'] ?>/5</span>
                  </td>
                  <td><?= htmlspecialchars($r['Comment']) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-center">No reviews found.</td></tr>
            <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="js/custom.js"></script>
</body>
</html>
