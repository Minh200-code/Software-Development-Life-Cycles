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

// ===== CUSTOMER VALIDATION ALGORITHM =====
$error_message = "";
$success_message = "";

// Handle Add or Update Customer
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']); // Thuật toán trim để loại bỏ khoảng trắng
    $role_id = isset($_POST['user_code']) ? 2 : $_POST['role_id']; // Auto set role when editing
    $email = trim($_POST['email']); // Thuật toán trim email
    $password = $_POST['password'];
    $address = trim($_POST['address']); // Thuật toán trim address
    $gender = $_POST['gender'];
    $phone_number = trim($_POST['phone_number']); // Thuật toán trim phone

    // ===== VALIDATION ALGORITHM =====
    // Thuật toán kiểm tra username không được trống
    if (empty($username)) {
        $error_message = "Username cannot be empty!";
    } else {
        // Thuật toán kiểm tra trùng username
        if (!empty($_POST['user_code'])) {
            // Update: kiểm tra trùng username với user khác (trừ user hiện tại)
            $user_code = $_POST['user_code'];
            $check_sql = "SELECT COUNT(*) as count FROM user WHERE Username = ? AND User_code != ?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                die("SQL Error: " . $conn->error . " in query: " . $check_sql);
            }
            $check_stmt->bind_param("si", $username, $user_code);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                $error_message = "Username '$username' already exists! Please choose a different username.";
            }
        } else {
            // Insert: kiểm tra trùng username với tất cả user
            $check_sql = "SELECT COUNT(*) as count FROM user WHERE Username = ?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                die("SQL Error: " . $conn->error . " in query: " . $check_sql);
            }
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                $error_message = "Username '$username' already exists! Please choose a different username.";
            }
        }
    }

    // Thuật toán kiểm tra email format và trùng email
    if (empty($error_message)) {
        if (empty($email)) {
            $error_message = "Email cannot be empty!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format! Please enter a valid email address.";
        } elseif (!preg_match('/@gmail\.com$/', $email)) {
            $error_message = "Email must be a Gmail address (@gmail.com)!";
        } else {
            // Thuật toán kiểm tra trùng email
            if (!empty($_POST['user_code'])) {
                // Update: kiểm tra trùng email với user khác (trừ user hiện tại)
                $user_code = $_POST['user_code'];
                $check_sql = "SELECT COUNT(*) as count FROM user WHERE Email = ? AND User_code != ?";
                $check_stmt = $conn->prepare($check_sql);
                if (!$check_stmt) {
                    die("SQL Error: " . $conn->error . " in query: " . $check_sql);
                }
                $check_stmt->bind_param("si", $email, $user_code);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $error_message = "Email '$email' already exists! Please choose a different email.";
                }
            } else {
                // Insert: kiểm tra trùng email với tất cả user
                $check_sql = "SELECT COUNT(*) as count FROM user WHERE Email = ?";
                $check_stmt = $conn->prepare($check_sql);
                if (!$check_stmt) {
                    die("SQL Error: " . $conn->error . " in query: " . $check_sql);
                }
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $error_message = "Email '$email' already exists! Please choose a different email.";
                }
            }
        }
    }

    // Thuật toán kiểm tra password khi thêm mới
    if (empty($error_message) && empty($_POST['user_code'])) {
        if (empty($password)) {
            $error_message = "Password cannot be empty!";
        } elseif (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters long!";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $error_message = "Password must contain at least one uppercase letter, one lowercase letter, and one number!";
        }
    }

    // Thuật toán kiểm tra phone number
    if (empty($error_message)) {
        if (empty($phone_number)) {
            $error_message = "Phone number cannot be empty!";
        } elseif (!preg_match('/^0\d{9}$/', $phone_number)) {
            $error_message = "Phone number must be 10 digits starting with 0!";
        } else {
            // Thuật toán kiểm tra trùng phone number
            if (!empty($_POST['user_code'])) {
                // Update: kiểm tra trùng phone với user khác (trừ user hiện tại)
                $user_code = $_POST['user_code'];
                $check_sql = "SELECT COUNT(*) as count FROM user WHERE PhoneNumber = ? AND User_code != ?";
                $check_stmt = $conn->prepare($check_sql);
                if (!$check_stmt) {
                    die("SQL Error: " . $conn->error . " in query: " . $check_sql);
                }
                $check_stmt->bind_param("si", $phone_number, $user_code);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $error_message = "Phone number '$phone_number' already exists! Please choose a different phone number.";
                }
            } else {
                // Insert: kiểm tra trùng phone với tất cả user
                $check_sql = "SELECT COUNT(*) as count FROM user WHERE PhoneNumber = ?";
                $check_stmt = $conn->prepare($check_sql);
                if (!$check_stmt) {
                    die("SQL Error: " . $conn->error . " in query: " . $check_sql);
                }
                $check_stmt->bind_param("s", $phone_number);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $error_message = "Phone number '$phone_number' already exists! Please choose a different phone number.";
                }
            }
        }
    }

    // Thuật toán kiểm tra address
    if (empty($error_message) && empty($address)) {
        $error_message = "Address cannot be empty!";
    }

    // Thuật toán kiểm tra gender
    if (empty($error_message) && empty($gender)) {
        $error_message = "Please select a gender!";
    }

    // ===== PROCESS ALGORITHM =====
    // Thuật toán xử lý nếu không có lỗi
    if (empty($error_message)) {
        // Hash password if not empty
        $hashed_password = "";
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        }

        if (!empty($_POST['user_code'])) {
            // Update
            $user_code = $_POST['user_code'];
            if (!empty($hashed_password)) {
                $sql = "UPDATE user SET Username=?, Role_Id=?, Email=?, Password=?, Address=?, Gender=?, PhoneNumber=? WHERE User_code=?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    die("SQL Error: " . $conn->error . " in query: " . $sql);
                }
                $stmt->bind_param("sisssssi", $username, $role_id, $email, $hashed_password, $address, $gender, $phone_number, $user_code);
            } else {
                $sql = "UPDATE user SET Username=?, Role_Id=?, Email=?, Address=?, Gender=?, PhoneNumber=? WHERE User_code=?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    die("SQL Error: " . $conn->error . " in query: " . $sql);
                }
                $stmt->bind_param("sissssi", $username, $role_id, $email, $address, $gender, $phone_number, $user_code);
            }
            $stmt->execute();
            $success_message = "Customer updated successfully!";
        } else {
            // Insert
            $sql = "INSERT INTO user (Username, Role_Id, Email, Password, Address, Gender, PhoneNumber) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("SQL Error: " . $conn->error . " in query: " . $sql);
            }
            $stmt->bind_param("sisssss", $username, $role_id, $email, $hashed_password, $address, $gender, $phone_number);
            $stmt->execute();
            $success_message = "Customer added successfully!";
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $user_code = $_GET['delete'];
    $conn->query("DELETE FROM user WHERE User_code=$user_code");
    header("Location: manage_customers.php");
    exit();
}

// Get data - exclude admin users (role_id = 1)
$customers = $conn->query("SELECT u.*, r.Role_name FROM user u LEFT JOIN role r ON u.Role_Id = r.Role_Id WHERE u.Role_Id != 1 ORDER BY u.User_code DESC");

$customer_edit = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM user WHERE User_code=$edit_id");
    $customer_edit = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Customer Management</title>
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
    <a href="manage_reviews.php">Manage Reviews</a>
    <a href="manage_customers.php" class="active">Manage Customers</a>
  </div>

  <div class="main-content">
    <h2>Customer Management</h2>

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
    <form method="POST">
        <input type="hidden" name="user_code" value="<?= $customer_edit['User_code'] ?? '' ?>">

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required value="<?= $customer_edit['Username'] ?? '' ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Role</label>
                    <select name="role_id" class="form-control" required <?= isset($customer_edit) ? 'style="display: none;"' : '' ?>>
                        <?php
                        // Get roles from database
                        $roles = $conn->query("SELECT * FROM role WHERE Role_Id != 1 ORDER BY Role_Id");
                        while ($role = $roles->fetch_assoc()):
                        ?>
                            <option value="<?= $role['Role_Id'] ?>" <?= (!isset($customer_edit) && $role['Role_Id'] == 2) || (isset($customer_edit['Role_Id']) && $customer_edit['Role_Id'] == $role['Role_Id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['Role_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= $customer_edit['Email'] ?? '' ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Password <?= isset($customer_edit) ? '(Leave blank to keep current)' : '' ?></label>
                    <input type="password" name="password" class="form-control" <?= !isset($customer_edit) ? 'required' : '' ?> <?= isset($customer_edit) ? 'style="display: none;"' : '' ?>>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= $customer_edit['Address'] ?? '' ?></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-control">
                        <option value="Male" <?= isset($customer_edit['Gender']) && $customer_edit['Gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= isset($customer_edit['Gender']) && $customer_edit['Gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= isset($customer_edit['Gender']) && $customer_edit['Gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" class="form-control" required value="<?= $customer_edit['PhoneNumber'] ?? '' ?>" placeholder="Enter 10-digit phone number starting with 0">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><?= isset($customer_edit) ? 'Update Customer' : 'Add Customer' ?></button>
        <?php if (isset($customer_edit)): ?>
            <a href="manage_customers.php" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
    </form>

    <!-- Customer List -->
    <h3 class="mt-5">Customer List</h3>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>User Code</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Gender</th>
                    <th>Phone Number</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($customers && $customers->num_rows > 0): ?>
                    <?php while ($customer = $customers->fetch_assoc()): ?>
                        <tr>
                            <td><?= $customer['User_code'] ?></td>
                            <td><?= htmlspecialchars($customer['Username']) ?></td>
                            <td>
                                <span class="badge badge-primary"><?= htmlspecialchars($customer['Role_name']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($customer['Email']) ?></td>
                            <td><?= htmlspecialchars($customer['Address']) ?></td>
                            <td><?= htmlspecialchars($customer['Gender']) ?></td>
                            <td><?= htmlspecialchars($customer['PhoneNumber']) ?></td>
                            <td>
                                <a href="?edit=<?= $customer['User_code'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?delete=<?= $customer['User_code'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this customer?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No customers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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
