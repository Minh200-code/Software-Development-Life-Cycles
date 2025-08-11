<?php
// ===== SESSION MANAGEMENT ALGORITHM =====
// Khởi tạo session để lưu trữ thông tin user
session_start();

// ===== CACHE CONTROL ALGORITHM =====
// Thuật toán ngăn chặn cache để đảm bảo dữ liệu luôn mới nhất
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ===== USER AUTHENTICATION ALGORITHM =====
// Thuật toán xác thực user và lấy thông tin từ database
if (isset($_SESSION['username'])) {
    // Thuật toán kết nối database với error handling
    $conn = new mysqli('sql203.infinityfree.com', 'if0_39667996', '3xJyzO66bT', 'if0_39667996_asm');
    if ($conn->connect_error) { die('Connection failed: ' . $conn->connect_error); }
    
    $username = $_SESSION['username'];
    
    // Thuật toán lấy thông tin user:
    // 1. Sử dụng prepared statement để tránh SQL injection
    // 2. Lấy User_code và Role_Id từ database
    // 3. Cập nhật session với thông tin mới nhất
    $stmt = $conn->prepare('SELECT User_code, Role_Id FROM user WHERE Username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Thuật toán cập nhật session:
        // 1. Lưu User_code vào session
        // 2. Lưu Role_Id vào session
        // 3. Gán cho biến local để sử dụng
        $_SESSION['user_code'] = $row['User_code'];
        $_SESSION['role_id'] = $row['Role_Id'];
        $user_code = $row['User_code'];
        
        // ===== ROLE-BASED ACCESS CONTROL ALGORITHM =====
        // Thuật toán kiểm tra quyền truy cập:
        // 1. Role_Id = 2: Customer (cho phép truy cập)
        // 2. Role_Id khác: Redirect về login
        if ($row['Role_Id'] != 2) {
            header('Location: contact.php');
            exit();
        }
    } else {
        // Thuật toán xử lý user không tồn tại
        // Redirect về login nếu không tìm thấy user
        header('Location: contact.php');
        exit();
    }
    $conn->close();
} else {
    // Thuật toán xử lý chưa login
    // Redirect về login nếu chưa có session
    header('Location: contact.php');
    exit();
}

// ===== DATABASE CONNECTION ALGORITHM =====
// Thuật toán kết nối database cho các operations
$conn = new mysqli('sql203.infinityfree.com', 'if0_39667996', '3xJyzO66bT', 'if0_39667996_asm');
if ($conn->connect_error) { die('Connection failed: ' . $conn->connect_error); }

// ===== MESSAGE HANDLING ALGORITHM =====
// Khởi tạo biến lưu thông báo cho các operations
$update_message = '';
$delete_message = '';

// ===== FORM PROCESSING ALGORITHM =====
// Kiểm tra khi user submit form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ===== BOOKING UPDATE ALGORITHM =====
    if (isset($_POST['update_booking'])) {
        $booking_code = $_POST['booking_code'];
        $new_checkin = $_POST['checkin_date'];
        $new_checkout = $_POST['checkout_date'];
        $new_guests = $_POST['num_guests'];
        
        // ===== DATE VALIDATION ALGORITHM =====
        // Thuật toán kiểm tra tính hợp lệ của ngày:
        // 1. Tạo DateTime objects để so sánh
        // 2. Kiểm tra check-in không được trong quá khứ
        // 3. Kiểm tra check-out phải sau check-in
        $checkin_date = new DateTime($new_checkin);
        $checkout_date = new DateTime($new_checkout);
        $today = new DateTime();
        $today->setTime(0, 0, 0); // Reset time to start of day
        
        if ($checkin_date < $today) {
            $update_message = "Check-in date cannot be in the past.";
        } elseif ($checkout_date <= $checkin_date) {
            $update_message = "Check-out date must be after check-in date.";
        } else {
            // ===== OVERLAP DETECTION ALGORITHM =====
            // Thuật toán kiểm tra trùng lặp booking:
            // 1. Tìm tất cả booking của cùng phòng
            // 2. Loại trừ booking hiện tại đang update
            // 3. Kiểm tra overlap với các booking khác
            // 4. Chỉ kiểm tra booking có status 'Booked'
            // Trước tiên, lấy Room_code của booking hiện tại
            $room_sql = "SELECT Room_code FROM booking WHERE Booking_code = ? AND User_code = ?";
            $room_stmt = $conn->prepare($room_sql);
            if (!$room_stmt) {
                die("SQL Error: " . $conn->error . " in query: " . $room_sql);
            }
            $room_stmt->bind_param("ss", $booking_code, $user_code);
            $room_stmt->execute();
            $room_result = $room_stmt->get_result();
            
            if ($room_result->num_rows == 0) {
                $update_message = "Booking not found or you don't have permission to edit it.";
            } else {
                $room_data = $room_result->fetch_assoc();
                $room_code = $room_data['Room_code'];
                
                $overlap_sql = "SELECT COUNT(*) as count FROM booking 
                               WHERE Room_code = ? 
                               AND Booking_code != ?
                               AND Status = 'Booked'
                               AND ((CheckInDate <= ? AND CheckOutDate > ?) 
                                    OR (CheckInDate < ? AND CheckOutDate >= ?)
                                    OR (CheckInDate >= ? AND CheckOutDate <= ?))";
                $overlap_stmt = $conn->prepare($overlap_sql);
                if (!$overlap_stmt) {
                    die("SQL Error: " . $conn->error . " in query: " . $overlap_sql);
                }
                $overlap_stmt->bind_param("ssssssss", $room_code, $booking_code, 
                                        $new_checkout, $new_checkin, $new_checkout, $new_checkin, 
                                        $new_checkin, $new_checkout);
            $overlap_stmt->execute();
            $overlap_result = $overlap_stmt->get_result()->fetch_assoc();
            
            if ($overlap_result['count'] > 0) {
                $update_message = "Selected dates overlap with another booking for this room. Please choose different dates.";
            } else {
                // ===== BOOKING UPDATE ALGORITHM =====
                // Thuật toán update booking:
                // 1. Sử dụng prepared statement để tránh SQL injection
                // 2. Update CheckInDate, CheckOutDate, NumOfGuests
                // 3. Chỉ update booking của user hiện tại
                $update_sql = "UPDATE booking SET CheckInDate = ?, CheckOutDate = ?, NumOfGuests = ? 
                              WHERE Booking_code = ? AND User_code = ?";
                $update_stmt = $conn->prepare($update_sql);
                if (!$update_stmt) {
                    die("SQL Error: " . $conn->error . " in query: " . $update_sql);
                }
                $update_stmt->bind_param("sssss", $new_checkin, $new_checkout, $new_guests, $booking_code, $user_code);
                
                if ($update_stmt->execute()) {
                    $update_message = "Booking updated successfully!";
                } else {
                    $update_message = "Error updating booking: " . $update_stmt->error;
                }
            }
        }
        }
    } 
    
    // ===== BOOKING DELETE ALGORITHM =====
    elseif (isset($_POST['delete_booking'])) {
        $booking_code = $_POST['booking_code'];
        
        // ===== PAYMENT CHECK ALGORITHM =====
        // Thuật toán kiểm tra payment trước khi xóa:
        // 1. Tìm tất cả payment cho booking này
        // 2. Nếu có payment thì không cho phép xóa
        $payment_check_sql = "SELECT COUNT(*) as count FROM payment WHERE Booking_code = ?";
        $payment_check_stmt = $conn->prepare($payment_check_sql);
        if (!$payment_check_stmt) {
            die("SQL Error: " . $conn->error . " in query: " . $payment_check_sql);
        }
        $payment_check_stmt->bind_param("s", $booking_code);
        $payment_check_stmt->execute();
        $payment_result = $payment_check_stmt->get_result()->fetch_assoc();
        
        if ($payment_result['count'] > 0) {
            $delete_message = "Cannot delete booking that has been paid for.";
        } else {
            // ===== BOOKING DELETE EXECUTION ALGORITHM =====
            // Thuật toán xóa booking:
            // 1. Chỉ xóa booking của user hiện tại
            // 2. Chỉ xóa booking chưa thanh toán
            $delete_sql = "DELETE FROM booking WHERE Booking_code = ? AND User_code = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            if (!$delete_stmt) {
                die("SQL Error: " . $conn->error . " in query: " . $delete_sql);
            }
            $delete_stmt->bind_param("ss", $booking_code, $user_code);
            
            if ($delete_stmt->execute()) {
                $delete_message = "Booking deleted successfully!";
            } else {
                $delete_message = "Error deleting booking: " . $delete_stmt->error;
            }
        }
    }
}

// ===== BOOKING RETRIEVAL ALGORITHM =====
// Thuật toán lấy danh sách booking của user:
// 1. Join với bảng room và roomtype để lấy thông tin phòng
// 2. Join với bảng payment để kiểm tra trạng thái thanh toán
// 3. Chỉ lấy booking của user hiện tại
// 4. Sắp xếp theo ngày tạo mới nhất
$bookings_sql = "SELECT b.*, r.Room_name, rt.Room_typename, rt.Room_price, 
                        CASE WHEN p.Booking_code IS NOT NULL THEN 'Paid' ELSE 'Unpaid' END as PaymentStatus
                 FROM booking b
                 JOIN room r ON b.Room_code = r.Room_code
                 JOIN roomtypeid rt ON r.RoomtypeID = rt.RoomtypeID
                 LEFT JOIN payment p ON b.Booking_code = p.Booking_code
                 WHERE b.User_code = ?
                 ORDER BY b.CheckInDate DESC";

$bookings_stmt = $conn->prepare($bookings_sql);
if (!$bookings_stmt) {
    die("SQL Error: " . $conn->error . " in query: " . $bookings_sql);
}
$bookings_stmt->bind_param("s", $user_code);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Bookings - Harbor Lights Hotel</title>
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
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- ===== HERO SECTION ALGORITHM ===== -->
<div class="hero-wrap booking-hero" style="background-image: url('images/bg_3.jpg');">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text d-flex align-itemd-center justify-content-center">
            <div class="col-md-9 ftco-animate text-center d-flex align-items-end justify-content-center">
                <div class="text">
                    <p class="breadcrumbs mb-2"><span class="mr-2"><a href="index.php">Home</a></span> <span>My Bookings</span></p>
                    <h1 class="mb-4 bread">My Bookings</h1>
                    <p class="mb-4">Manage your hotel reservations</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== BOOKINGS SECTION ALGORITHM ===== -->
<section class="ftco-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Thuật toán hiển thị messages -->
                <?php if ($update_message): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($update_message) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if ($delete_message): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($delete_message) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- ===== BOOKINGS DISPLAY ALGORITHM ===== -->
                <?php if ($bookings_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Room</th>
                                    <th>Booking Code</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Guests</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($booking['Room_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($booking['Room_typename']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($booking['Booking_code']) ?></td>
                                        <td><?= date('M d, Y', strtotime($booking['CheckInDate'])) ?></td>
                                        <td><?= date('M d, Y', strtotime($booking['CheckOutDate'])) ?></td>
                                        <td><?= htmlspecialchars($booking['NumOfGuests']) ?></td>
                                        <td>$<?= number_format($booking['Room_price'], 2) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $booking['PaymentStatus'] == 'Paid' ? 'success' : 'warning' ?>">
                                                <?= $booking['PaymentStatus'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($booking['PaymentStatus'] == 'Unpaid'): ?>
                                                <button class="btn btn-primary btn-sm" onclick="editBooking('<?= $booking['Booking_code'] ?>', '<?= date('Y-m-d', strtotime($booking['CheckInDate'])) ?>', '<?= date('Y-m-d', strtotime($booking['CheckOutDate'])) ?>', <?= $booking['NumOfGuests'] ?>)">
                                                    <i class="icon-edit mr-1"></i>Edit
                                                </button>
                                                
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this booking?')">
                                                    <input type="hidden" name="booking_code" value="<?= $booking['Booking_code'] ?>">
                                                    <button type="submit" name="delete_booking" class="btn btn-danger btn-sm">
                                                        <i class="icon-trash mr-1"></i>Delete
                                                    </button>
                                                </form>
                                                
                                                <a href="payment.php?booking=<?= $booking['Booking_code'] ?>" class="btn btn-success btn-sm">
                                                    <i class="icon-credit-card mr-1"></i>Pay Now
                                                </a>
                                            <?php else: ?>
                                                <a href="rooms-single.php?room=<?= $booking['Room_code'] ?>" class="btn btn-info btn-sm">
                                                    <i class="icon-star mr-1"></i>Review Room
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- ===== EMPTY STATE ALGORITHM ===== -->
                    <div class="text-center">
                        <div class="empty-bookings">
                            <i class="icon-calendar-empty mb-3"></i>
                            <h4>No Bookings Found</h4>
                            <p>You haven't made any bookings yet.</p>
                            <a href="rooms.php" class="btn btn-primary">Browse Rooms</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ===== EDIT BOOKING MODAL ALGORITHM ===== -->
<div class="modal fade" id="editBookingModal" tabindex="-1" role="dialog" aria-labelledby="editBookingModalLabel" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBookingModalLabel">Edit Booking</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="booking_code" id="edit_booking_code">
                    
                    <div class="form-group">
                        <label for="edit_checkin_date">Check-in Date</label>
                        <input type="date" class="form-control" id="edit_checkin_date" name="checkin_date" required>
                        <small class="form-text text-muted">Select a future date for check-in</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_checkout_date">Check-out Date</label>
                        <input type="date" class="form-control" id="edit_checkout_date" name="checkout_date" required>
                        <small class="form-text text-muted">Select a date after check-in</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_num_guests">Number of Guests</label>
                        <input type="number" class="form-control" id="edit_num_guests" name="num_guests" min="1" max="10" required>
                        <small class="form-text text-muted">Maximum 10 guests per room</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_booking" class="btn btn-primary">Update Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

<!-- ===== CSS ALGORITHM: Modal Fix ===== -->
<style>
/* Thuật toán fix modal z-index và backdrop */
#editBookingModal {
    z-index: 9999 !important;
}

#editBookingModal .modal-dialog {
    z-index: 10000 !important;
}

#editBookingModal .modal-content {
    z-index: 10001 !important;
}

/* Đảm bảo backdrop không bị mờ quá */
.modal-backdrop {
    z-index: 9998 !important;
    opacity: 0.5 !important;
}

/* Đảm bảo modal có thể tương tác */
.modal.show {
    display: block !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
}
</style>

<!-- ===== JAVASCRIPT ALGORITHM: Edit Booking Modal ===== -->
<script>
// Thuật toán khởi tạo edit booking modal
function editBooking(bookingCode, checkinDate, checkoutDate, numGuests) {
    // Thuật toán populate modal với dữ liệu booking:
    // 1. Set booking code vào hidden field
    // 2. Set check-in date vào date input
    // 3. Set check-out date vào date input
    // 4. Set number of guests vào number input
    // 5. Show modal
    document.getElementById('edit_booking_code').value = bookingCode;
    document.getElementById('edit_checkin_date').value = checkinDate;
    document.getElementById('edit_checkout_date').value = checkoutDate;
    document.getElementById('edit_num_guests').value = numGuests;
    
    // Sử dụng jQuery để show modal với cấu hình đơn giản
    $('#editBookingModal').modal('show');
}

// Thuật toán đảm bảo modal hoạt động đúng
$(document).ready(function() {
    // Đảm bảo modal có thể đóng được
    $('#editBookingModal').on('hidden.bs.modal', function () {
        // Reset form khi đóng modal
        $(this).find('form')[0].reset();
    });
    
    // Đảm bảo modal có thể tương tác
    $('#editBookingModal').on('shown.bs.modal', function () {
        // Focus vào input đầu tiên
        $('#edit_checkin_date').focus();
    });
    
    // Thuật toán validation ngày trong modal
    $('#edit_checkin_date, #edit_checkout_date').on('change', function() {
        const checkinDate = new Date($('#edit_checkin_date').val());
        const checkoutDate = new Date($('#edit_checkout_date').val());
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Reset time to start of day
        
        // Kiểm tra check-in không được trong quá khứ
        if (checkinDate < today) {
            alert('Check-in date cannot be in the past.');
            $(this).val('');
            return;
        }
        
        // Kiểm tra check-out phải sau check-in
        if (checkinDate && checkoutDate && checkoutDate <= checkinDate) {
            alert('Check-out date must be after check-in date.');
            $('#edit_checkout_date').val('');
            return;
        }
    });
    
    // Thuật toán validation form trước khi submit
    $('#editBookingModal form').on('submit', function(e) {
        const checkinDate = new Date($('#edit_checkin_date').val());
        const checkoutDate = new Date($('#edit_checkout_date').val());
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (checkinDate < today) {
            e.preventDefault();
            alert('Please select a valid check-in date (not in the past).');
            $('#edit_checkin_date').focus();
            return false;
        }
        
        if (checkoutDate <= checkinDate) {
            e.preventDefault();
            alert('Please select a valid check-out date (must be after check-in).');
            $('#edit_checkout_date').focus();
            return false;
        }
        
        return true;
    });
});

// Thuật toán auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    // Thuật toán tự động ẩn alerts sau 5 giây
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                // Fallback cho Bootstrap 4
                $(alert).fadeOut();
            }
        });
    }, 5000);
});
</script>

</body>
</html> 