<?php
session_start();

// Database connection
$conn = new mysqli("sql203.infinityfree.com", "if0_39667996", "3xJyzO66bT", "if0_39667996_asm");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get room details from URL parameter
$room_code = isset($_GET['room']) ? $_GET['room'] : '';
$room = null;

if ($room_code) {
    $sql = "SELECT r.*, rt.Room_typename, rt.Room_price 
            FROM room r 
            LEFT JOIN roomtypeid rt ON r.RoomtypeID = rt.RoomtypeID 
            WHERE r.Room_code = ? AND r.Status = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $room_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
}

// Check if user already has an active booking for this room (for UI display)
$has_active_booking = false;
if (isset($_SESSION['user_code']) && $room_code) {
    $check_booking_sql = "SELECT Booking_code FROM booking WHERE User_code = ? AND Room_code = ? AND Status = 'Booked'";
    $check_booking_stmt = $conn->prepare($check_booking_sql);
    $check_booking_stmt->bind_param("ss", $_SESSION['user_code'], $room_code);
    $check_booking_stmt->execute();
    $existing_booking = $check_booking_stmt->get_result()->fetch_assoc();
    $has_active_booking = $existing_booking !== null;
}

// If no room found, redirect to rooms page
if (!$room) {
    header("Location: rooms.php");
    exit();
}

// Check if user has paid for this room and hasn't reviewed yet (for review functionality)
$can_review = false;
$user_has_paid_booking = null;
$has_reviewed = false;
if (isset($_SESSION['user_code']) && $room_code) {
    // Kiểm tra user đã có booking đã thanh toán cho phòng này chưa
    $review_sql = "SELECT b.Booking_code, b.CheckInDate, b.CheckOutDate 
                   FROM booking b 
                   LEFT JOIN payment p ON b.Booking_code = p.Booking_code 
                   WHERE b.User_code = ? AND b.Room_code = ? AND p.Payment_code IS NOT NULL 
                   ORDER BY b.CheckInDate DESC LIMIT 1";
    $review_stmt = $conn->prepare($review_sql);
    $review_stmt->bind_param("ss", $_SESSION['user_code'], $room_code);
    $review_stmt->execute();
    $user_has_paid_booking = $review_stmt->get_result()->fetch_assoc();

    // Check if user has already reviewed this room
    if ($user_has_paid_booking) {
        $check_review_sql = "SELECT Review_code FROM review WHERE Customer_code = ? AND Room_code = ? AND Booking_code = ?";
        $check_review_stmt = $conn->prepare($check_review_sql);
        $check_review_stmt->bind_param("sss", $_SESSION['user_code'], $room_code, $user_has_paid_booking['Booking_code']);
        $check_review_stmt->execute();
        $existing_review = $check_review_stmt->get_result()->fetch_assoc();
        $has_reviewed = $existing_review !== null;
    }

    $can_review = $user_has_paid_booking !== null && !$has_reviewed;
}

// Get all reviews for this room
$room_reviews = [];
if ($room_code) {
    $reviews_sql = "SELECT r.*, u.Username 
                    FROM review r 
                    LEFT JOIN user u ON r.Customer_code = u.User_code 
                    WHERE r.Room_code = ? 
                    ORDER BY r.Review_code DESC";
    $reviews_stmt = $conn->prepare($reviews_sql);
    $reviews_stmt->bind_param("s", $room_code);
    $reviews_stmt->execute();
    $reviews_result = $reviews_stmt->get_result();
    while ($review = $reviews_result->fetch_assoc()) {
        $room_reviews[] = $review;
    }
}

// Handle booking form submission
$booking_message = '';
$booking_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_now'])) {
    // Check if user is logged in
    if (!isset($_SESSION['username'])) {
        $booking_error = "Please login to book a room.";
    } else {
        $check_in_date = $_POST['check_in_date'];
        $check_out_date = $_POST['check_out_date'];
        $num_guests = $_POST['num_guests'];
        $special_requests = $_POST['special_requests'];
        $user_code = $_SESSION['user_code'];
        
        // Validate dates
        $today = date('Y-m-d');
        if ($check_in_date < $today) {
            $booking_error = "Check-in date cannot be in the past.";
        } elseif ($check_out_date <= $check_in_date) {
            $booking_error = "Check-out date must be after check-in date.";
        } else {
            // Check if room is available for the selected dates (any user)
            $check_availability_sql = "SELECT Booking_code, User_code FROM booking 
                                     WHERE Room_code = ? AND Status = 'Booked' 
                                     AND (
                                         (CheckInDate <= ? AND CheckOutDate > ?) OR
                                         (CheckInDate < ? AND CheckOutDate >= ?) OR
                                         (CheckInDate >= ? AND CheckOutDate <= ?)
                                     )";
            $check_availability_stmt = $conn->prepare($check_availability_sql);
            $check_availability_stmt->bind_param("sssssss", $room_code, $check_in_date, $check_in_date, $check_out_date, $check_out_date, $check_in_date, $check_out_date);
            $check_availability_stmt->execute();
            $existing_booking = $check_availability_stmt->get_result()->fetch_assoc();
            
            if ($existing_booking) {
                if ($existing_booking['User_code'] == $user_code) {
                    $booking_error = "You already have a booking for this room during this time period. Please choose different dates.";
                } else {
                    $booking_error = "This room has already been booked during this time period. Please choose different dates.";
                }
            } else {
                // Generate booking code
                $booking_code = 'BK' . date('Ymd') . rand(1000, 9999);
            
            // Insert booking into database
            $sql = "INSERT INTO booking (Booking_code, User_code, Room_code, CheckInDate, CheckOutDate, NumOfGuests, Status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Booked')";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssssss", $booking_code, $user_code, $room_code, $check_in_date, $check_out_date, $num_guests);
                
                if ($stmt->execute()) {
                    $booking_message = "Booking successful! Your booking code is: " . $booking_code;
                    // Clear form data after successful booking
                    $_POST = array();
                } else {
                    $booking_error = "Error creating booking: " . $stmt->error;
                }
            } else {
                $booking_error = "Database error: " . $conn->error;
            }
            }
        }
    }
}

// Handle review submission
$review_message = '';
$review_error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_code'])) {
        $review_error = "Please login to submit a review.";
    } elseif (!$can_review) {
        $review_error = "You can only review rooms after booking and payment.";
    } else {
        $rating = $_POST['rating'];
        $comment = $_POST['comment'];
        
        if ($rating < 1 || $rating > 5) {
            $review_error = "Please select a valid rating (1-5 stars).";
        } elseif (empty($comment)) {
            $review_error = "Please provide a comment for your review.";
        } else {
            // Generate review code
            $review_code = 'RV' . date('Ymd') . rand(1000, 9999);
            // Insert review into database (có Booking_code)
            $review_insert_sql = "INSERT INTO review (Review_code, Customer_code, Room_code, Booking_code, Rating, Comment) 
                                 VALUES (?, ?, ?, ?, ?, ?)";
            $review_insert_stmt = $conn->prepare($review_insert_sql);
            if ($review_insert_stmt) {
                $review_insert_stmt->bind_param("ssssss", $review_code, $_SESSION['user_code'], $room_code, $user_has_paid_booking['Booking_code'], $rating, $comment);
                if ($review_insert_stmt->execute()) {
                    $review_message = "Review submitted successfully! Thank you for your feedback.";
                    $can_review = false; // Prevent duplicate reviews
                } else {
                    if ($review_insert_stmt->errno == 1062) {
                        $review_message = "You have already reviewed this room";
                        $can_review = false;
                    } else {
                        $review_error = "Error submitting review: " . $review_insert_stmt->error;
                    }
                }
            } else {
                $review_error = "Database error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title><?= htmlspecialchars($room['Room_name']) ?> - Harbor Lights Hotel</title>
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
    
    <style>
        .room-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        .room-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }
        
        .room-image-gallery {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .room-image-gallery .room-img {
            height: 400px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .room-details-card {
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .room-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .room-subtitle {
            font-size: 1.2rem;
            color: #667eea;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .room-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #666;
            margin-bottom: 30px;
        }
        
        .room-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .feature-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        
        .feature-icon {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .feature-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .feature-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
        }
        
        .booking-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
            position: sticky;
            top: 20px;
        }
        
        .booking-card h3 {
            color: white;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .booking-form {
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            color: rgba(255,255,255,0.9);
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            color: white;
            padding: 12px 15px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.5);
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
        }
        
        .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .book-now-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .book-now-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .price-display {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
        }
        
        .price-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .price-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin: 10px 0;
        }
        
        .price-unit {
            font-size: 1rem;
            color: rgba(255,255,255,0.8);
        }
        
        .amenities-section {
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .amenity-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 3px solid #667eea;
        }
        
        .amenity-icon {
            font-size: 1.5rem;
            color: #667eea;
            margin-right: 10px;
        }
        
        .amenity-text {
            font-weight: 600;
            color: #333;
        }
        
        .review-policy {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .review-policy h4 {
            color: white;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .review-policy ul {
            margin-bottom: 15px;
            padding-left: 20px;
        }
        
        .review-policy li {
            margin-bottom: 8px;
            color: rgba(255,255,255,0.9);
        }
        
        @media (max-width: 768px) {
            .room-title {
                font-size: 2rem;
            }
            
            .room-features {
                grid-template-columns: 1fr;
            }
            
            .amenities-grid {
                grid-template-columns: 1fr;
            }
            
            .booking-card {
                position: static;
                margin-top: 30px;
            }
        }
    </style>
  </head>
  <body>

    <?php include 'navbar.php'; ?>

    <div class="hero-wrap room-hero" style="background-image: url('images/bg_3.jpg');">
      <div class="overlay"></div>
      <div class="container">
        <div class="row no-gutters slider-text d-flex align-itemd-center justify-content-center">
          <div class="col-md-9 ftco-animate text-center d-flex align-items-end justify-content-center">
          	<div class="text">
	            <p class="breadcrumbs mb-2"><span class="mr-2"><a href="index.php">Home</a></span> <span class="mr-2"><a href="rooms.php">Rooms</a></span> <span><?= htmlspecialchars($room['Room_name']) ?></span></p>
	            <h1 class="mb-4 bread"><?= htmlspecialchars($room['Room_name']) ?></h1>
                <p class="mb-4">Experience luxury and comfort</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <section class="ftco-section">
      <div class="container">
        <div class="row">
          <div class="col-lg-8">
            <!-- Room Image Gallery -->
            <div class="room-image-gallery ftco-animate">
              <div class="single-slider owl-carousel">
                <div class="item">
                  <div class="room-img" style="background-image: url(images/<?= $room['ImageURL'] ?: 'room-1.jpg' ?>);"></div>
                </div>
                <div class="item">
                  <div class="room-img" style="background-image: url(images/<?= $room['ImageURL'] ?: 'room-2.jpg' ?>);"></div>
                </div>
                <div class="item">
                  <div class="room-img" style="background-image: url(images/<?= $room['ImageURL'] ?: 'room-3.jpg' ?>);"></div>
                </div>
              </div>
            </div>

            <!-- Room Details -->
            <div class="room-details-card ftco-animate">
              <h2 class="room-title"><?= htmlspecialchars($room['Room_name']) ?></h2>
              <p class="room-subtitle"><?= htmlspecialchars($room['Room_typename']) ?></p>
              <p class="room-description"><?= htmlspecialchars($room['Description']) ?></p>
              
              <div class="room-features">
                <div class="feature-item">
                  <div class="feature-icon"><i class="icon-users"></i></div>
                  <div class="feature-label">Capacity</div>
                  <div class="feature-value"><?= $room['Capacity'] ?> Persons</div>
                </div>
                <div class="feature-item">
                  <div class="feature-icon"><i class="icon-credit-card"></i></div>
                  <div class="feature-label">Price</div>
                  <div class="feature-value"><?= number_format($room['Room_price']) ?> VND</div>
                </div>
                <div class="feature-item">
                  <div class="feature-icon"><i class="icon-home"></i></div>
                  <div class="feature-label">Type</div>
                  <div class="feature-value"><?= htmlspecialchars($room['Room_typename']) ?></div>
                </div>
                <div class="feature-item">
                  <div class="feature-icon"><i class="icon-check-circle"></i></div>
                  <div class="feature-label">Status</div>
                  <div class="feature-value">Available</div>
                </div>
              </div>
              
              <p class="room-description">Experience the ultimate comfort and luxury in our <?= htmlspecialchars($room['Room_name']) ?>. This room is designed to provide you with the best possible stay, featuring modern amenities and elegant furnishings. Perfect for both business and leisure travelers.</p>
            </div>

            <!-- Amenities Section -->
            <div class="amenities-section ftco-animate">
              <h3 class="mb-4">Room Amenities</h3>
              <div class="amenities-grid">
                <div class="amenity-item">
                  <i class="icon-wifi amenity-icon"></i>
                  <span class="amenity-text">Free WiFi</span>
                </div>
                <div class="amenity-item">
                  <i class="icon-air-conditioner amenity-icon"></i>
                  <span class="amenity-text">Air Conditioning</span>
                </div>
                <div class="amenity-item">
                  <i class="icon-tv amenity-icon"></i>
                  <span class="amenity-text">Flat Screen TV</span>
                </div>
                <div class="amenity-item">
                  <i class="icon-bath amenity-icon"></i>
                  <span class="amenity-text">Private Bathroom</span>
                </div>
                <div class="amenity-item">
                  <i class="icon-coffee amenity-icon"></i>
                  <span class="amenity-text">Coffee Maker</span>
                </div>
                <div class="amenity-item">
                  <i class="icon-safe amenity-icon"></i>
                  <span class="amenity-text">Safe Box</span>
                </div>
                <div class="amenity-item">
                  <i class="icon-iron amenity-icon"></i>
                  <span class="amenity-text">Iron & Board</span>
                </div>
                <div class="amenity-item">
                  <i class="icon-hair-dryer amenity-icon"></i>
                  <span class="amenity-text">Hair Dryer</span>
                </div>
              </div>
            </div>

            <!-- Review Section -->
            <?php if ($can_review): ?>
              <!-- Review Form for Paid Customers -->
              <div class="review-form ftco-animate" style="background: #fff; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-top: 30px;">
                <h4><i class="icon-star mr-2"></i>Leave Your Review</h4>
                <p class="mb-3 text-muted">Share your experience with other guests</p>
                

                
                <?php if ($review_message): ?>
                                  <div class="alert alert-success" style="background: rgba(40, 167, 69, 0.2); border: 1px solid rgba(40, 167, 69, 0.3); color: #28a745; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                  <i class="icon-check-circle mr-2"></i><?= htmlspecialchars($review_message) ?>
                </div>
                <?php endif; ?>
                
                <?php if ($review_error): ?>
                                  <div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.2); border: 1px solid rgba(220, 53, 69, 0.3); color: #dc3545; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                  <i class="icon-exclamation-triangle mr-2"></i><?= htmlspecialchars($review_error) ?>
                </div>
                  

                <?php endif; ?>
                
                <form method="POST">

                  
                  <div class="form-group">
                    <label>Rating</label>
                    <div class="rating-input" style="display: flex; gap: 10px; margin-top: 10px;">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                          <label style="cursor: pointer; font-size: 24px; color: #ddd; transition: color 0.3s ease;">
                            <input type="radio" name="rating" value="<?= $i ?>" style="display: none;" required>
                            <span class="star" data-rating="<?= $i ?>" style="transition: color 0.3s ease;">★</span>
                        </label>
                      <?php endfor; ?>
                    </div>
                    <small class="text-muted">Click on the stars to rate (1-5 stars)</small>
                  </div>
                  
                  <div class="form-group">
                    <label>Your Review</label>
                    <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience with this room..." required></textarea>
                  </div>
                  
                        <button type="submit" name="submit_review" class="btn btn-warning" style="background: linear-gradient(45deg, #ffc107, #ff8c00); border: none; color: white; padding: 12px 25px; border-radius: 25px; font-weight: 600;">
                    <i class="icon-star mr-2"></i>Submit Review
                  </button>
                  

                </form>
              </div>
            <?php elseif ($has_reviewed): ?>
              <!-- Already Reviewed Message -->
              <div class="review-completed ftco-animate" style="background: #fff; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-top: 30px;">
                <h4><i class="icon-check-circle mr-2"></i>Review Submitted</h4>
                <div class="alert alert-info" style="background: rgba(23, 162, 184, 0.2); border: 1px solid rgba(23, 162, 184, 0.3); color: #17a2b8; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                  <i class="icon-info mr-2"></i>You have already reviewed this room
                </div>
                <p class="mb-0 text-muted">
                  <small>
                    <i class="icon-star"></i> Thank you for your feedback! Your review helps other guests make informed decisions.
                  </small>
                </p>
              </div>
            <?php endif; ?>
            
               <!-- Customer Reviews Section -->
            <?php if (!empty($room_reviews)): ?>
           <div class="customer-reviews ftco-animate" style="margin-top: 40px;">
                <h4><i class="icon-star mr-2"></i>Customer Reviews (<?= count($room_reviews) ?> reviews)</h4>
                <div class="reviews-container">
                  <?php foreach ($room_reviews as $review): ?>
                    <div class="review-item" style="background: #fff; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                      <div class="review-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div class="reviewer-info">
                          <strong><?= htmlspecialchars($review['Username'] ?? 'Anonymous') ?></strong>
                          <small class="text-muted d-block"><?= date('M d, Y', strtotime($review['Review_code'] ?? date('Y-m-d'))) ?></small>
                        </div>
                        <div class="rating-display">
                          <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span style="color: <?= $i <= $review['Rating'] ? '#ffc107' : '#ddd'; ?>; font-size: 18px;">★</span>
                          <?php endfor; ?>
                          <span class="rating-text" style="margin-left: 10px; font-weight: 600; color: #333;"><?= $review['Rating'] ?>/5</span>
                        </div>
                      </div>
                      <div class="review-content">
                        <p style="margin: 0; line-height: 1.6; color: #555;"><?= htmlspecialchars($review['Comment']) ?></p>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php else: ?>
              <div class="customer-reviews ftco-animate" style="margin-top: 40px;">
                <h4><i class="icon-star mr-2"></i>Customer Reviews</h4>
                <div class="alert alert-info" style="background: rgba(23, 162, 184, 0.2); border: 1px solid rgba(23, 162, 184, 0.3); color: #17a2b8; border-radius: 10px; padding: 15px;">
                  <i class="icon-info mr-2"></i>No reviews yet. Be the first to review this room!
                </div>
              </div>
            <?php endif; ?>
          </div> <!-- .col-md-8 -->
          
          <!-- Booking Sidebar -->
          <div class="col-lg-4">
            <?php if ($has_active_booking): ?>
              <!-- Already Booked Message -->
              <div class="booking-card ftco-animate" style="background: #fff; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <h3><i class="icon-check-circle mr-2"></i>Active Booking</h3>
                <div class="alert alert-info" style="background: rgba(23, 162, 184, 0.2); border: 1px solid rgba(23, 162, 184, 0.3); color: #17a2b8; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                  <i class="icon-info mr-2"></i>You already have a booking for this room
                </div>
                <p class="mb-0 text-muted">
                  <small>
                    <i class="icon-calendar"></i> You can still book this room for different dates. Check your booking status in "My Bookings" page.
                  </small>
                </p>
                <div class="text-center mt-3">
                  <a href="booking.php" class="btn btn-primary" style="background: linear-gradient(45deg, #007bff, #0056b3); border: none; color: white; padding: 10px 20px; border-radius: 25px; font-weight: 600;">
                    <i class="icon-calendar mr-2"></i>View My Bookings
                  </a>
                </div>
              </div>
            <?php else: ?>
              <div class="booking-card ftco-animate">
                <h3><i class="icon-calendar mr-2"></i>Book This Room</h3>
              
                            
                
                <?php if ($booking_message): ?>
                 <div class="alert alert-success" style="background: rgba(40, 167, 69, 0.2); border: 1px solid rgba(40, 167, 69, 0.3); color: white; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                   <i class="icon-check-circle mr-2"></i><?= htmlspecialchars($booking_message) ?>
                 </div>
               <?php endif; ?>
               
               <?php if ($booking_error): ?>
                 <div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.2); border: 1px solid rgba(220, 53, 69, 0.3); color: white; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                   <i class="icon-exclamation-triangle mr-2"></i><?= htmlspecialchars($booking_error) ?>
                 </div>
               <?php endif; ?>
               
               <form class="booking-form" method="POST">
                 <div class="form-group">
                   <label>Check-in Date</label>
                   <input type="date" name="check_in_date" id="check_in_date" class="form-control" value="<?= htmlspecialchars($_POST['check_in_date'] ?? '') ?>" required min="<?= date('Y-m-d') ?>" onchange="checkAvailability()">
                 </div>
                 
                 <div class="form-group">
                   <label>Check-out Date</label>
                   <input type="date" name="check_out_date" id="check_out_date" class="form-control" value="<?= htmlspecialchars($_POST['check_out_date'] ?? '') ?>" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" onchange="checkAvailability()">
                 </div>
                 
                 <div id="availability-message" style="margin-bottom: 15px;"></div>
                 
                 <div class="form-group">
                   <label>Number of Guests</label>
                   <select name="num_guests" class="form-control">
                     <option value="1" <?= ($_POST['num_guests'] ?? '') == '1' ? 'selected' : '' ?>>1 Person</option>
                     <option value="2" <?= ($_POST['num_guests'] ?? '') == '2' ? 'selected' : '' ?>>2 People</option>
                     <option value="3" <?= ($_POST['num_guests'] ?? '') == '3' ? 'selected' : '' ?>>3 People</option>
                     <option value="4" <?= ($_POST['num_guests'] ?? '') == '4' ? 'selected' : '' ?>>4 People</option>
                     <option value="5" <?= ($_POST['num_guests'] ?? '') == '5' ? 'selected' : '' ?>>5 People</option>
                     <option value="6" <?= ($_POST['num_guests'] ?? '') == '6' ? 'selected' : '' ?>>6 People</option>
                   </select>
                 </div>
                 
                 <div class="form-group">
                   <label>Special Requests</label>
                   <textarea name="special_requests" class="form-control" rows="3" placeholder="Any special requests?"><?= htmlspecialchars($_POST['special_requests'] ?? '') ?></textarea>
                 </div>
                 
                 <button type="submit" name="book_now" class="book-now-btn">
                   <i class="icon-check mr-2"></i>Book Now
                 </button>
               </form>
              
              <div class="text-center mt-3">
                <small style="color: rgba(255,255,255,0.8);">
                  <i class="icon-shield"></i> Secure booking guaranteed
                </small>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section> <!-- .section -->


    <footer class="ftco-footer ftco-section img" style="background-image: url(images/bg_4.jpg);">
    	<div class="overlay"></div>
      <div class="container">
        <div class="row mb-5">
          <div class="col-md">
            <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2">Harbor Lights</h2>
              <p>Welcome to Harbor Lights Hotel – your perfect destination for comfort, relaxation, and unforgettable experiences. Enjoy our modern rooms, excellent service, and prime location for both business and leisure travelers.</p>
              <ul class="ftco-footer-social list-unstyled float-md-left float-lft mt-5">
                <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
                <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
                <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
              </ul>
            </div>
          </div>
          <div class="col-md">
            <div class="ftco-footer-widget mb-4 ml-md-5">
              <h2 class="ftco-heading-2">Useful Links</h2>
              <ul class="list-unstyled">

                <li><a href="#" class="py-2 d-block">Rooms</a></li>
                <li><a href="#" class="py-2 d-block">Amenities</a></li>
                <li><a href="#" class="py-2 d-block">Gift Card</a></li>
              </ul>
            </div>
          </div>
          <div class="col-md">
             <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2">Privacy</h2>
              <ul class="list-unstyled">
                <li><a href="#" class="py-2 d-block">Career</a></li>
                <li><a href="#" class="py-2 d-block">About Us</a></li>
                <li><a href="#" class="py-2 d-block">Contact Us</a></li>
                <li><a href="#" class="py-2 d-block">Services</a></li>
              </ul>
            </div>
          </div>
          <div class="col-md">
            <div class="ftco-footer-widget mb-4">
            	<h2 class="ftco-heading-2">Have a Questions?</h2>
            	<div class="block-23 mb-3">
	              <ul>
	                <li><span class="icon icon-map-marker"></span><span class="text">203 Fake St. Mountain View, San Francisco, California, USA</span></li>
	                <li><a href="#"><span class="icon icon-phone"></span><span class="text">+2 392 3929 21</span></a></li>
	                <li><a href="#"><span class="icon icon-envelope"></span><span class="text">daotuanminh@gmail.com</span></a></li>
	              </ul>
	            </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 text-center">

            <p><!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
  Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | This template is made with <i class="icon-heart color-danger" aria-hidden="true"></i> by <a href="https://colorlib.com" target="_blank">Colorlib</a>
  <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. --></p>
          </div>
        </div>
      </div>
    </footer>
    
  

  <!-- loader -->
  <div id="ftco-loader" class="show fullscreen"><svg class="circular" width="48px" height="48px"><circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke="#eeeeee"/><circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke-miterlimit="10" stroke="#F96D00"/></svg></div>


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
  <script src="js/scrollax.min.js"></script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBVWaKrjvy3MaE7SQ74_uJiULgl1JY0H2s&sensor=false"></script>
  <script src="js/google-map.js"></script>
  <script src="js/main.js"></script>
<script src="js/custom.js"></script>
  
  <script>
    // Price calculation functionality
    const roomPrice = <?= $room['Room_price'] ?>;
    const checkInInput = document.getElementById('check_in_date');
    const checkOutInput = document.getElementById('check_out_date');
    const totalAmount = document.getElementById('total-amount');
    const totalNights = document.getElementById('total-nights');
    
    function calculateTotalPrice() {
      const checkIn = new Date(checkInInput.value);
      const checkOut = new Date(checkOutInput.value);
      
      if (checkIn && checkOut && checkOut > checkIn) {
        const timeDiff = checkOut.getTime() - checkIn.getTime();
        const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
        const totalPrice = nights * roomPrice;
        
        totalAmount.textContent = totalPrice.toLocaleString('en-US');
        totalNights.textContent = `${nights} night${nights > 1 ? 's' : ''}`;
      } else {
        totalAmount.textContent = '-';
        totalNights.textContent = '-';
      }
    }
    
    // Add event listeners
    checkInInput.addEventListener('change', function() {
        calculateTotalPrice();
        checkAvailability();
    });
    checkOutInput.addEventListener('change', function() {
        calculateTotalPrice();
        checkAvailability();
    });
    
    // Calculate on page load if dates are already set
    if (checkInInput.value && checkOutInput.value) {
      calculateTotalPrice();
    }
    
    // Availability checking function
    function checkAvailability() {
        const checkInDate = document.getElementById('check_in_date').value;
        const checkOutDate = document.getElementById('check_out_date').value;
        const availabilityMessage = document.getElementById('availability-message');
        const bookNowBtn = document.querySelector('button[name="book_now"]');
        
        if (!checkInDate || !checkOutDate) {
            availabilityMessage.innerHTML = '';
            return;
        }
        
        // Validate dates
        const today = new Date().toISOString().split('T')[0];
        if (checkInDate < today) {
            availabilityMessage.innerHTML = '<div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.2); border: 1px solid rgba(220, 53, 69, 0.3); color: white; border-radius: 10px; padding: 10px; font-size: 14px;"><i class="icon-exclamation-triangle mr-2"></i>Check-in date cannot be in the past.</div>';
            bookNowBtn.disabled = true;
            return;
        }
        
        if (checkOutDate <= checkInDate) {
            availabilityMessage.innerHTML = '<div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.2); border: 1px solid rgba(220, 53, 69, 0.3); color: white; border-radius: 10px; padding: 10px; font-size: 14px;"><i class="icon-exclamation-triangle mr-2"></i>Check-out date must be after check-in date.</div>';
            bookNowBtn.disabled = true;
            return;
        }
        
        // Show loading message
        availabilityMessage.innerHTML = '<div class="alert alert-info" style="background: rgba(23, 162, 184, 0.2); border: 1px solid rgba(23, 162, 184, 0.3); color: white; border-radius: 10px; padding: 10px; font-size: 14px;"><i class="icon-spinner mr-2"></i>Checking availability...</div>';
        
        // Make AJAX request to check availability
        fetch('check_availability.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `room_code=<?= $room_code ?>&check_in=${checkInDate}&check_out=${checkOutDate}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                availabilityMessage.innerHTML = '<div class="alert alert-success" style="background: rgba(40, 167, 69, 0.2); border: 1px solid rgba(40, 167, 69, 0.3); color: white; border-radius: 10px; padding: 10px; font-size: 14px;"><i class="icon-check-circle mr-2"></i>Room is available for selected dates!</div>';
                bookNowBtn.disabled = false;
            } else {
                availabilityMessage.innerHTML = '<div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.2); border: 1px solid rgba(220, 53, 69, 0.3); color: white; border-radius: 10px; padding: 10px; font-size: 14px;"><i class="icon-exclamation-triangle mr-2"></i>' + data.message + '</div>';
                bookNowBtn.disabled = true;
            }
        })
        .catch(error => {
            availabilityMessage.innerHTML = '<div class="alert alert-warning" style="background: rgba(255, 193, 7, 0.2); border: 1px solid rgba(255, 193, 7, 0.3); color: white; border-radius: 10px; padding: 10px; font-size: 14px;"><i class="icon-exclamation-triangle mr-2"></i>Unable to check availability. Please try again.</div>';
            bookNowBtn.disabled = false;
        });
    }
    
    // Star rating functionality
    document.addEventListener('DOMContentLoaded', function() {
      const stars = document.querySelectorAll('.star');
      const ratingInputs = document.querySelectorAll('input[name="rating"]');
      
      if (stars.length > 0) {
        stars.forEach((star, index) => {
          star.addEventListener('mouseenter', function() {
            const rating = this.getAttribute('data-rating');
            highlightStars(rating);
          });
          
          star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            const radioInput = document.querySelector(`input[name="rating"][value="${rating}"]`);
            if (radioInput) {
              radioInput.checked = true;
              highlightStars(rating);
              console.log('Selected rating:', rating); // Debug
            }
          });
        });
        
        // Reset stars when mouse leaves the rating area
        const ratingContainer = document.querySelector('.rating-input');
        if (ratingContainer) {
          ratingContainer.addEventListener('mouseleave', function() {
            const selectedRating = document.querySelector('input[name="rating"]:checked');
            if (selectedRating) {
              highlightStars(selectedRating.value);
            } else {
              resetStars();
            }
          });
        }
        
        function highlightStars(rating) {
          stars.forEach((star, index) => {
            if (index < rating) {
              star.style.color = '#ffc107';
            } else {
              star.style.color = '#ddd';
            }
          });
        }
        
        function resetStars() {
          stars.forEach(star => {
            star.style.color = '#ddd';
          });
        }
        
        // Initialize stars on page load
        const selectedRating = document.querySelector('input[name="rating"]:checked');
        if (selectedRating) {
          highlightStars(selectedRating.value);
        }
      }
    });
  </script>
    
  </body>
</html>