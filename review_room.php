<?php
session_start();

// Database connection
$conn = new mysqli("sql203.infinityfree.com", "if0_39667996", "3xJyzO66bT", "if0_39667996_asm");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: contact.php");
    exit();
}

// Get room details
$room_code = isset($_GET['room']) ? $_GET['room'] : '';
$booking_code = isset($_GET['booking']) ? $_GET['booking'] : '';

if (!$room_code || !$booking_code) {
    header("Location: rooms.php");
    exit();
}

// Get room and booking details
$sql = "SELECT r.*, rt.Room_typename, rt.Room_price 
        FROM room r 
        LEFT JOIN roomtypeid rt ON r.RoomtypeID = rt.RoomtypeID 
        WHERE r.Room_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $room_code);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

// Check if booking exists and belongs to user
// Note: This will be implemented when booking system is ready
$booking_exists = true; // Placeholder for booking verification

if (!$room || !$booking_exists) {
    header("Location: rooms.php");
    exit();
}

// Handle review submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $user_code = $_SESSION['user_code']; // Assuming user_code is stored in session
    
    // Insert review
    $sql = "INSERT INTO review (Customer_code, Room_code, Booking_code, Rating, Comment, Review_date) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $user_code, $room_code, $booking_code, $rating, $comment);
    
    if ($stmt->execute()) {
        $success_message = "Thank you for your review!";
    } else {
        $error_message = "Error submitting review. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Review Room - Harbor Lights Hotel</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:200,300,400,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/open-iconic-bootstrap.min.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/owl.theme.default.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/aos.css">
    <link rel="stylesheet" href="css/ionicons.min.css">
    <link rel="stylesheet" href="css/bootstrap-datepicker.css">
    <link rel="stylesheet" href="css/jquery.timepicker.css">
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/icomoon.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">Harbor<span>lights</span></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="oi oi-menu"></span> Menu
        </button>

        <div class="collapse navbar-collapse" id="ftco-nav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="rooms.php" class="nav-link">Our Rooms</a></li>
                <li class="nav-item"><a href="about.php" class="nav-link">About Us</a></li>
                <?php if (isset($_SESSION['username']) && $_SESSION['role_id'] == 1): ?>
                    <li class="nav-item"><a href="admin.php" class="nav-link">Manager</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['username'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-toggle="dropdown">
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="profile.php">Profile</a>
                            <?php if ($_SESSION['role_id'] == 1): ?>
                                <a class="dropdown-item" href="admin.php">Admin Menu</a>
                            <?php endif; ?>
                            <a class="dropdown-item" href="logout.php">Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a href="contact.php" class="nav-link">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="hero-wrap" style="background-image: url('images/bg_3.jpg');">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text d-flex align-itemd-center justify-content-center">
            <div class="col-md-9 ftco-animate text-center d-flex align-items-end justify-content-center">
                <div class="text">
                    <p class="breadcrumbs mb-2"><span class="mr-2"><a href="index.php">Home</a></span> <span class="mr-2"><a href="rooms.php">Rooms</a></span> <span>Review Room</span></p>
                    <h1 class="mb-4 bread">Review Your Stay</h1>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="ftco-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Review: <?= htmlspecialchars($room['Room_name']) ?></h3>
                        <p class="mb-0">Booking Code: <?= htmlspecialchars($booking_code) ?></p>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success">
                                <?= $success_message ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <?= $error_message ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="form-group">
                                <label><strong>Rating</strong></label>
                                <div class="rating-stars">
                                    <input type="radio" name="rating" value="5" id="star5" required>
                                    <label for="star5"><i class="icon-star"></i></label>
                                    <input type="radio" name="rating" value="4" id="star4">
                                    <label for="star4"><i class="icon-star"></i></label>
                                    <input type="radio" name="rating" value="3" id="star3">
                                    <label for="star3"><i class="icon-star"></i></label>
                                    <input type="radio" name="rating" value="2" id="star2">
                                    <label for="star2"><i class="icon-star"></i></label>
                                    <input type="radio" name="rating" value="1" id="star1">
                                    <label for="star1"><i class="icon-star"></i></label>
                                </div>
                                <small class="form-text text-muted">Click on a star to rate your experience</small>
                            </div>

                            <div class="form-group">
                                <label><strong>Your Review</strong></label>
                                <textarea name="comment" class="form-control" rows="5" placeholder="Share your experience with this room..." required></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                                <a href="rooms.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.rating-stars {
    display: inline-block;
    direction: rtl;
}

.rating-stars input {
    display: none;
}

.rating-stars label {
    cursor: pointer;
    font-size: 30px;
    color: #ddd;
    padding: 5px;
}

.rating-stars label:hover,
.rating-stars label:hover ~ label,
.rating-stars input:checked ~ label {
    color: #ffc107;
}

.rating-stars input:checked ~ label:hover,
.rating-stars input:checked ~ label:hover ~ label {
    color: #ffc107;
}
</style>

<script src="js/jquery.min.js"></script>
<script src="js/jquery-migrate-3.0.1.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.easing.1.3.js"></script>
<script src="js/jquery.waypoints.min.js"></script>
<script src="js/jquery.stellar.min.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/jquery.magnific-popup.min.js"></script>
<script src="js/aos.js"></script>
<script src="js/jquery.animateNumber.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="js/jquery.timepicker.min.js"></script>
<script src="js/scrollax.min.js"></script>
<script src="js/main.js"></script>

</body>
</html> 