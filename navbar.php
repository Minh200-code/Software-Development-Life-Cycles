<nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
  <div class="container">
    <a class="navbar-brand" href="index.php">Harbor<span>lights</span></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav"
            aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="oi oi-menu"></span> Menu
    </button>

    <div class="collapse navbar-collapse" id="ftco-nav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="rooms.php" class="nav-link">Our Rooms</a></li>
        <li class="nav-item"><a href="about.php" class="nav-link">About Us</a></li>
        <?php if (isset($_SESSION['username'])): ?>
          <?php if ($_SESSION['role_id'] == 1): ?>
            <li class="nav-item"><a href="admin.php" class="nav-link">Manager</a></li>
          <?php elseif ($_SESSION['role_id'] == 2): ?>
            <li class="nav-item"><a href="booking.php" class="nav-link">My Booking</a></li>
          <?php endif; ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-toggle="dropdown">
              <?= htmlspecialchars($_SESSION['username']) ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
              <a class="dropdown-item" href="profile.php">Profile</a>
              <a class="dropdown-item" href="logout.php">Logout</a>
            </div>
          </li>
        <?php else: ?>
          <li class="nav-item"><a href="contact.php" class="nav-link">Login</a></li>
          <li class="nav-item"><a href="register.php" class="nav-link">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav> 