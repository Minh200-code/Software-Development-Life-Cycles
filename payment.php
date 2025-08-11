<?php
// ===== SESSION MANAGEMENT ALGORITHM =====
// Khởi tạo session để lưu trữ thông tin user
session_start();

// ===== DATABASE CONNECTION ALGORITHM =====
// Cấu hình kết nối database
$servername = "sql203.infinityfree.com";
$username = "if0_39667996";
$password = "3xJyzO66bT";
$database = "if0_39667996_asm";

// Thuật toán kết nối database với error handling
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ===== VARIABLE INITIALIZATION ALGORITHM =====
// Khởi tạo biến lưu thông tin booking và user
$booking = null;
$user_code = $_SESSION['user_code'] ?? null;

// ===== PAYMENT PROCESSING ALGORITHM =====
// Kiểm tra khi user submit form payment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Thuật toán lấy dữ liệu từ form
    $booking_code = $_POST['booking_code'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $payment_date = date("Y-m-d");
    $status = "Completed";

    // ===== BOOKING VALIDATION ALGORITHM =====
    // Thuật toán kiểm tra booking tồn tại:
    // 1. Sử dụng prepared statement để tránh SQL injection
    // 2. Tìm booking theo Booking_code
    // 3. Lấy thông tin User_code, Room_code, dates, guests, status
    $stmt = $conn->prepare("SELECT User_code, Room_code, CheckInDate, CheckOutDate, NumOfGuests, Status FROM booking WHERE Booking_code = ?");
    $stmt->bind_param("s", $booking_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        echo "Booking does not exist.";
        exit();
    }
    $booking = $result->fetch_assoc();

    // ===== OWNERSHIP VALIDATION ALGORITHM =====
    // Thuật toán kiểm tra quyền sở hữu:
    // 1. So sánh User_code của booking với user hiện tại
    // 2. Chỉ cho phép owner của booking thanh toán
    if ($booking['User_code'] != $user_code) {
        echo "You do not have permission to pay for this booking.";
        exit();
    }

    // ===== PAYMENT DUPLICATE CHECK ALGORITHM =====
    // Thuật toán kiểm tra đã thanh toán chưa:
    // 1. Tìm payment cho booking này
    // 2. Nếu đã có payment thì không cho phép thanh toán lại
    $stmt = $conn->prepare("SELECT 1 FROM payment WHERE Booking_code = ?");
    $stmt->bind_param("s", $booking_code);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "You have already paid for this booking.";
        exit();
    }

    // ===== PRICE CALCULATION ALGORITHM =====
    // Thuật toán tính giá phòng:
    // 1. Join bảng room và roomtypeid để lấy giá
    // 2. Lấy Room_price từ roomtypeid table
    $stmt = $conn->prepare("SELECT rt.Room_price FROM room r JOIN roomtypeid rt ON r.RoomtypeID = rt.RoomtypeID WHERE r.Room_code = ?");
    $stmt->bind_param("s", $booking['Room_code']);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
    $price_per_night = $room['Room_price'];

    // ===== NIGHT CALCULATION ALGORITHM =====
    // Thuật toán tính số đêm:
    // 1. Tạo DateTime objects cho check-in và check-out
    // 2. Tính khoảng cách giữa 2 ngày
    // 3. Đảm bảo ít nhất 1 đêm
    $check_in = new DateTime($booking['CheckInDate']);
    $check_out = new DateTime($booking['CheckOutDate']);
    $interval = $check_in->diff($check_out);
    $nights = $interval->days;
    if ($nights < 1) $nights = 1; // at least 1 night

    // ===== TOTAL AMOUNT CALCULATION ALGORITHM =====
    // Thuật toán tính tổng tiền:
    // 1. Nhân giá mỗi đêm với số đêm
    // 2. Lưu vào biến amount
    $amount = $price_per_night * $nights;

    // ===== PAYMENT INSERTION ALGORITHM =====
    // Thuật toán insert payment:
    // 1. Sử dụng prepared statement để tránh SQL injection
    // 2. Insert thông tin payment vào database
    // 3. Nếu thành công thì update booking status
        $stmt = $conn->prepare("INSERT INTO payment (Booking_code, Payment_method, Amount, Payment_date, Status) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("SQL Error: " . $conn->error . " in payment insert query");
    }
    $stmt->bind_param("ssdss", $booking_code, $payment_method, $amount, $payment_date, $status);
    if ($stmt->execute()) {
        // ===== BOOKING STATUS UPDATE ALGORITHM =====
        // Thuật toán update booking status:
        // 1. Update Status = 'Paid' cho booking
        // 2. Chỉ update booking đã thanh toán thành công
        $stmt2 = $conn->prepare("UPDATE booking SET Status = 'Paid' WHERE Booking_code = ?");
        if (!$stmt2) {
            die("SQL Error: " . $conn->error . " in booking update query");
        }
        $stmt2->bind_param("s", $booking_code);
        $stmt2->execute();

        echo "Payment successful!";
    } else {
        echo "Error: " . $stmt->error;
    }
} else {
    // ===== GET BOOKING CODE ALGORITHM =====
    // Thuật toán lấy booking_code từ URL parameter
    $booking_code = $_GET['booking'] ?? '';
}

// ===== BOOKING INFORMATION RETRIEVAL ALGORITHM =====
// Thuật toán lấy thông tin booking để hiển thị:
// 1. Join với bảng room và roomtypeid để lấy thông tin phòng
// 2. Lấy tất cả thông tin cần thiết cho payment form
// 3. Sử dụng prepared statement để tránh SQL injection
if ($booking_code) {
    $sql = "SELECT b.*, r.Room_name, r.RoomtypeID, rt.Room_typename, rt.Room_price
            FROM booking b
            LEFT JOIN room r ON b.Room_code = r.Room_code
            LEFT JOIN roomtypeid rt ON r.RoomtypeID = rt.RoomtypeID
            WHERE b.Booking_code = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error: " . $conn->error . " in query: " . $sql);
    }
    $stmt->bind_param("s", $booking_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
}

// ===== PAYMENT STATUS CHECK ALGORITHM =====
// Thuật toán kiểm tra trạng thái thanh toán:
// 1. Tìm payment cho booking này
// 2. Xác định đã thanh toán hay chưa
$payment_status = "Unpaid";
if ($booking_code) {
    $payment_check_sql = "SELECT COUNT(*) as count FROM payment WHERE Booking_code = ?";
    $payment_check_stmt = $conn->prepare($payment_check_sql);
    if (!$payment_check_stmt) {
        die("SQL Error: " . $conn->error . " in payment check query");
    }
    $payment_check_stmt->bind_param("s", $booking_code);
    $payment_check_stmt->execute();
    $payment_result = $payment_check_stmt->get_result()->fetch_assoc();
    
    if ($payment_result['count'] > 0) {
        $payment_status = "Paid";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment - Harbor Lights Hotel</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <!-- ===== CSS LOADING ALGORITHM ===== -->
    <!-- Load các file CSS theo thứ tự ưu tiên -->
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
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/custom.css">
    <style>
        /* ===== PAYMENT METHODS STYLING ===== */
        .payment-options {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-option {
            flex: 1;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-option:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        
        .payment-option input[type="radio"] {
            display: none;
        }
        
        .payment-option input[type="radio"]:checked + label {
            color: #007bff;
        }
        
        .payment-option input[type="radio"]:checked ~ .payment-option {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        
        .payment-option label {
            display: block;
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }
        
        .payment-option i {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
        }
        
        .payment-details {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        
        .qr-code-placeholder {
            padding: 30px;
            border: 2px dashed #007bff;
            border-radius: 10px;
            background-color: white;
            margin: 20px 0;
        }
        
        .payment-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .payment-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .payment-body {
            padding: 30px;
        }
        
        .booking-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .booking-info p {
            margin-bottom: 8px;
        }
        
        .total-amount {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }
        
        .payment-actions {
            margin-top: 30px;
            text-align: center;
        }
        
        .payment-status.paid {
            text-align: center;
            padding: 40px;
            background: #d4edda;
            border-radius: 8px;
            color: #155724;
        }
        
        .payment-status.paid i {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- ===== HERO SECTION ALGORITHM ===== -->
<div class="hero-wrap payment-hero" style="background-image: url('images/bg_3.jpg');">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text d-flex align-itemd-center justify-content-center">
            <div class="col-md-9 ftco-animate text-center d-flex align-items-end justify-content-center">
                <div class="text">
                    <p class="breadcrumbs mb-2"><span class="mr-2"><a href="index.php">Home</a></span> <span>Payment</span></p>
                    <h1 class="mb-4 bread">Payment</h1>
                    <p class="mb-4">Complete your booking payment</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== PAYMENT SECTION ALGORITHM ===== -->
<section class="ftco-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($booking): ?>
                    <!-- ===== PAYMENT FORM ALGORITHM ===== -->
                    <div class="payment-card">
                        <div class="payment-header">
                            <h3><i class="icon-credit-card mr-2"></i>Payment Details</h3>
                        </div>
                        
                        <div class="payment-body">
                            <!-- ===== BOOKING INFORMATION ALGORITHM ===== -->
                            <div class="booking-summary">
                                <h5>Booking Summary</h5>
                                <div class="booking-info">
                                    <p><strong>Room:</strong> <?= htmlspecialchars($booking['Room_name']) ?></p>
                                    <p><strong>Type:</strong> <?= htmlspecialchars($booking['Room_typename']) ?></p>
                                    <p><strong>Check-in:</strong> <?= date('M d, Y', strtotime($booking['CheckInDate'])) ?></p>
                                    <p><strong>Check-out:</strong> <?= date('M d, Y', strtotime($booking['CheckOutDate'])) ?></p>
                                    <p><strong>Guests:</strong> <?= htmlspecialchars($booking['NumOfGuests']) ?></p>
                                    <p><strong>Price per Night:</strong> $<?= number_format($booking['Room_price'], 2) ?></p>
                                    
                                    <?php
                                    // ===== NIGHT CALCULATION ALGORITHM =====
                                    // Thuật toán tính số đêm cho hiển thị
                                    $check_in = new DateTime($booking['CheckInDate']);
                                    $check_out = new DateTime($booking['CheckOutDate']);
                                    $interval = $check_in->diff($check_out);
                                    $nights = $interval->days;
                                    if ($nights < 1) $nights = 1;
                                    ?>
                                    <p><strong>Number of Nights:</strong> <?= $nights ?></p>
                                    <p><strong>Total Amount:</strong> <span class="total-amount">$<?= number_format($booking['Room_price'] * $nights, 2) ?></span></p>
                                </div>
                            </div>
                            
                            <!-- ===== PAYMENT STATUS ALGORITHM ===== -->
                            <?php if ($payment_status == "Paid"): ?>
                                <!-- Thuật toán hiển thị đã thanh toán -->
                                <div class="payment-status paid">
                                    <i class="icon-check-circle"></i>
                                    <h4>Payment Completed</h4>
                                    <p>This booking has already been paid for.</p>
                                    <a href="booking.php" class="btn btn-primary">View My Bookings</a>
                                </div>
                            <?php else: ?>
                                <!-- ===== PAYMENT FORM ALGORITHM ===== -->
                                <!-- Thuật toán hiển thị form thanh toán -->
                                <form method="POST" class="payment-form">
                                    <input type="hidden" name="booking_code" value="<?= htmlspecialchars($booking['Booking_code']) ?>">
                                    
                                    <div class="payment-method">
                                        <h5>Payment Method</h5>
                                        <div class="form-group">
                                            <div class="payment-options">
                                                <div class="payment-option">
                                                    <input type="radio" id="cash" name="payment_method" value="Cash">
                                                    <label for="cash">
                                                        <i class="icon-money"></i>
                                                        Cash
                                                    </label>
                                                </div>
                                                <div class="payment-option">
                                                    <input type="radio" id="credit_card" name="payment_method" value="Credit Card">
                                                    <label for="credit_card">
                                                        <i class="icon-credit-card"></i>
                                                        Credit Card
                                                    </label>
                                                </div>
                                                <div class="payment-option">
                                                    <input type="radio" id="qr_code" name="payment_method" value="QR Code">
                                                    <label for="qr_code">
                                                        <i class="icon-qrcode"></i>
                                                        QR Code
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    

                                    
                                    <div class="payment-actions">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="icon-credit-card mr-2"></i>Pay now $<?= number_format($booking['Room_price'] * $nights, 2) ?>
                                        </button>
                                        <a href="booking.php" class="btn btn-secondary">Exit</a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- ===== ERROR STATE ALGORITHM ===== -->
                    <!-- Thuật toán hiển thị lỗi khi không tìm thấy booking -->
                    <div class="error-state text-center">
                        <i class="icon-exclamation-triangle mb-3"></i>
                        <h4>Booking Not Found</h4>
                        <p>The booking you're looking for doesn't exist or you don't have permission to access it.</p>
                        <a href="booking.php" class="btn btn-primary">View My Bookings</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ===== JAVASCRIPT LOADING ALGORITHM ===== -->
<!-- Load các file JS theo thứ tự dependency -->
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
<script src="js/main.js"></script>
<script src="js/custom.js"></script>

<!-- ===== JAVASCRIPT ALGORITHM: Payment Method Selection ===== -->
<script>
// Thuật toán khởi tạo payment method selection
document.addEventListener('DOMContentLoaded', function() {

    

    
    // ===== FORM SUBMISSION ALGORITHM =====
    // Thuật toán xử lý form submission:
    // 1. Hiển thị loading state
    // 2. Submit form
    document.querySelector('.payment-form').addEventListener('submit', function(e) {
        // Thuật toán hiển thị loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="icon-spinner mr-2"></i>Đang xử lý...';
        submitBtn.disabled = true;
    });
});
</script>

</body>
</html> 