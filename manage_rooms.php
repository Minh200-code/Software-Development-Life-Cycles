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

// ===== ROOM NAME VALIDATION ALGORITHM =====
$error_message = "";
$success_message = "";

// Handle Add or Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_name = trim($_POST['room_name']); // Thuật toán trim để loại bỏ khoảng trắng
    $roomtypeid = $_POST['roomtypeid'];
    $description = $_POST['description'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    // ===== VALIDATION ALGORITHM =====
    // Thuật toán kiểm tra tên phòng không được trống
    if (empty($room_name)) {
        $error_message = "Room name cannot be empty!";
    } else {
        // Thuật toán kiểm tra trùng tên phòng
        if (!empty($_POST['room_id'])) {
            // Update: kiểm tra trùng tên với phòng khác (trừ phòng hiện tại)
            $room_id = $_POST['room_id'];
            $check_sql = "SELECT COUNT(*) as count FROM room WHERE Room_name = ? AND Room_code != ?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                die("SQL Error: " . $conn->error . " in query: " . $check_sql);
            }
            $check_stmt->bind_param("si", $room_name, $room_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                $error_message = "Room name '$room_name' already exists! Please choose a different name.";
            }
        } else {
            // Insert: kiểm tra trùng tên với tất cả phòng
            $check_sql = "SELECT COUNT(*) as count FROM room WHERE Room_name = ?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                die("SQL Error: " . $conn->error . " in query: " . $check_sql);
            }
            $check_stmt->bind_param("s", $room_name);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                $error_message = "Room name '$room_name' already exists! Please choose a different name.";
            }
        }
    }

    // ===== PROCESS ALGORITHM =====
    // Thuật toán xử lý nếu không có lỗi
    if (empty($error_message)) {
        // Handle file upload
        $image_name = "";
        if (!empty($_FILES["image"]["name"])) {
            $image_name = uniqid() . "_" . basename($_FILES["image"]["name"]);
            $target_dir = "images/";
            $target_file = $target_dir . $image_name;
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        }

        if (!empty($_POST['room_id'])) {
            // Update
            $room_id = $_POST['room_id'];
            if ($image_name != "") {
                $sql = "UPDATE room SET Room_name=?, RoomtypeID=?, Description=?, Capacity=?, Status=?, ImageURL=? WHERE Room_code=?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    die("SQL Error: " . $conn->error . " in query: " . $sql);
                }
                $stmt->bind_param("sisissi", $room_name, $roomtypeid, $description, $capacity, $status, $image_name, $room_id);
            } else {
                $sql = "UPDATE room SET Room_name=?, RoomtypeID=?, Description=?, Capacity=?, Status=? WHERE Room_code=?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    die("SQL Error: " . $conn->error . " in query: " . $sql);
                }
                $stmt->bind_param("sisisi", $room_name, $roomtypeid, $description, $capacity, $status, $room_id);
            }
            $stmt->execute();
            $success_message = "Room updated successfully!";
        } else {
            // Insert
            $sql = "INSERT INTO room (Room_name, RoomtypeID, Description, Capacity, Status, ImageURL) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("SQL Error: " . $conn->error . " in query: " . $sql);
            }
            $stmt->bind_param("sisiss", $room_name, $roomtypeid, $description, $capacity, $status, $image_name);
            $stmt->execute();
            $success_message = "Room added successfully!";
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $room_id = $_GET['delete'];
    $conn->query("DELETE FROM room WHERE Room_code=$room_id");
    header("Location: manage_rooms.php");
    exit();
}

// Get data
$rooms = $conn->query("SELECT r.*, rt.Room_typename, rt.Room_price FROM room r JOIN roomtypeid rt ON r.RoomtypeID = rt.RoomtypeID");

$roomtypes = $conn->query("SELECT * FROM roomtypeid");

$room_edit = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM room WHERE Room_code=$edit_id");
    $room_edit = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Room Management</title>
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
    <a href="manage_rooms.php" class="active">Manage Rooms</a>
    <a href="manage_reviews.php">Manage Reviews</a>
    <a href="manage_customers.php">Manage Customers</a>
  </div>

  <div class="main-content">
    <h2>Room Management</h2>

    <!-- ===== MESSAGE DISPLAY ALGORITHM ===== -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="card">
        <div class="card-header">
            <h4><?= isset($room_edit) ? 'Edit Room' : 'Add New Room' ?></h4>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="room_id" value="<?= $room_edit['Room_code'] ?? '' ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Room Name</strong></label>
                            <input type="text" name="room_name" class="form-control" required value="<?= $room_edit['Room_name'] ?? '' ?>" placeholder="Enter room name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Room Type</strong></label>
                            <select name="roomtypeid" class="form-control" required>
                                <option value="">Select Room Type</option>
                                <?php while ($row = $roomtypes->fetch_assoc()): ?>
                                    <option value="<?= $row['RoomtypeID'] ?>" <?= isset($room_edit['RoomtypeID']) && $room_edit['RoomtypeID'] == $row['RoomtypeID'] ? 'selected' : '' ?>>
                                        <?= $row['Room_typename'] ?> - <?= number_format($row['Room_price']) ?> VND
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Capacity</strong></label>
                            <input type="number" name="capacity" class="form-control" required value="<?= $room_edit['Capacity'] ?? '' ?>" placeholder="Number of persons" min="1">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Status</strong></label>
                            <select name="status" class="form-control">
                                <option value="1" <?= !isset($room_edit) || (isset($room_edit['Status']) && $room_edit['Status'] == 1) ? 'selected' : '' ?>>Available</option>
                                <option value="0" <?= isset($room_edit['Status']) && $room_edit['Status'] == 0 ? 'selected' : '' ?>>Unavailable</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><strong>Description</strong></label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Enter room description"><?= $room_edit['Description'] ?? '' ?></textarea>
                </div>

                <div class="form-group">
                    <label><strong>Room Image</strong></label>
                    <input type="file" name="image" class="form-control-file" accept="image/*">
                    <small class="form-text text-muted">Upload a high-quality image for the room (JPG, PNG, GIF)</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= isset($room_edit) ? 'Update Room' : 'Add Room' ?>
                    </button>
                    <?php if (isset($room_edit)): ?>
                        <a href="manage_rooms.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Room List -->
    <h3 class="mt-5">Room List</h3>
    <div class="row">
        <?php if ($rooms && $rooms->num_rows > 0): ?>
            <?php while ($room = $rooms->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-img-top-container" style="height: 200px; overflow: hidden;">
                            <?php if ($room['ImageURL']): ?>
                                <img src="images/<?= $room['ImageURL'] ?>" class="card-img-top" style="width: 100%; height: 100%; object-fit: cover;" alt="<?= htmlspecialchars($room['Room_name']) ?>">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 100%;">
                                    <span class="text-muted">No Image</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($room['Room_name']) ?></h5>
                            <p class="card-text">
                                <strong>Type:</strong> <?= htmlspecialchars($room['Room_typename']) ?><br>
                                <strong>Price:</strong> <?= number_format($room['Room_price']) ?> VND<br>
                                <strong>Capacity:</strong> <?= $room['Capacity'] ?> persons<br>
                                <strong>Status:</strong> 
                                <?php if ($room['Status'] == 1): ?>
                                    <span class="badge badge-success">Available</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Unavailable</span>
                                <?php endif; ?>
                            </p>
                            <?php if ($room['Description']): ?>
                                <p class="card-text">
                                    <strong>Description:</strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($room['Description']) ?></small>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group w-100" role="group">
                                <a href="?edit=<?= $room['Room_code'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="?delete=<?= $room['Room_code'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this room?')">Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    No rooms found. Add your first room using the form above.
                </div>
            </div>
        <?php endif; ?>
    </div>
  </div>
</div>

<!-- ===== JAVASCRIPT ALGORITHM: Auto-hide Messages ===== -->
<script>
// Thuật toán tự động ẩn thông báo sau 5 giây
document.addEventListener('DOMContentLoaded', function() {
    // Thuật toán tìm tất cả alert messages
    const alerts = document.querySelectorAll('.alert');
    
    // Thuật toán set timeout để ẩn từng alert
    alerts.forEach(function(alert) {
        setTimeout(function() {
            // Thuật toán fade out và remove alert
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000); // 5 giây
    });
});
</script>

<script src="js/custom.js"></script>
</body>
</html> 