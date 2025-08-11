<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: contact.php");
    exit();
}

// Kết nối database
$conn = new mysqli('sql203.infinityfree.com', 'if0_39667996', '3xJyzO66bT', 'if0_39667996_asm');
if ($conn->connect_error) { die('Connection failed: ' . $conn->connect_error); }

$username = $_SESSION['username'] ?? '';
$user_info = null;
$update_message = '';

// Khởi tạo mảng lưu lỗi cho từng field
$errors = [
    'name' => '',
    'email' => '',
    'address' => '',
    'gender' => '',
    'phone' => ''
];

// Xử lý form update khi user submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Lấy và làm sạch dữ liệu từ form
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_address = trim($_POST['address']);
    $new_gender = $_POST['gender'];
    $new_phone = trim($_POST['phone']);
    
    // ===== VALIDATION ALGORITHM: NAME FIELD =====
    if (empty($new_name)) {
        // Kiểm tra name không được để trống
        $errors['name'] = "Name cannot be empty";
    } else {
        // Thuật toán kiểm tra trùng lặp name:
        // 1. Tìm tất cả user có cùng name
        // 2. Loại trừ user hiện tại (WHERE Username != current_username)
        // 3. Nếu có kết quả > 0 thì name đã tồn tại
        $name_check_sql = "SELECT COUNT(*) as count FROM user WHERE Username = ? AND Username != ?";
        $name_check_stmt = $conn->prepare($name_check_sql);
        $name_check_stmt->bind_param('ss', $new_name, $username);
        $name_check_stmt->execute();
        $name_result = $name_check_stmt->get_result()->fetch_assoc();
        if ($name_result['count'] > 0) {
            $errors['name'] = "Name already exists";
        }
    }
    
    // ===== VALIDATION ALGORITHM: EMAIL FIELD =====
    if (empty($new_email)) {
        // Kiểm tra email không được để trống
        $errors['email'] = "Email cannot be empty";
    } else {
        // Thuật toán kiểm tra email format:
        // 1. Sử dụng regex để kiểm tra format @gmail.com
        // 2. Chỉ cho phép: chữ cái, số, dấu chấm, gạch dưới, % + - trước @
        // 3. Phải kết thúc bằng @gmail.com
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $new_email)) {
            $errors['email'] = "Email must be a valid Gmail address (@gmail.com)";
        } else {
            // Thuật toán kiểm tra trùng lặp email:
            // 1. Tìm tất cả user có cùng email
            // 2. Loại trừ user hiện tại
            // 3. Nếu có kết quả > 0 thì email đã tồn tại
            $email_check_sql = "SELECT COUNT(*) as count FROM user WHERE Email = ? AND Username != ?";
            $email_check_stmt = $conn->prepare($email_check_sql);
            $email_check_stmt->bind_param('ss', $new_email, $username);
            $email_check_stmt->execute();
            $email_result = $email_check_stmt->get_result()->fetch_assoc();
            if ($email_result['count'] > 0) {
                $errors['email'] = "Email already exists";
            }
        }
    }
    
    // ===== VALIDATION ALGORITHM: ADDRESS FIELD =====
    if (empty($new_address)) {
        // Kiểm tra address không được để trống
        $errors['address'] = "Address cannot be empty";
    }
    
    // ===== VALIDATION ALGORITHM: GENDER FIELD =====
    if (empty($new_gender)) {
        // Kiểm tra gender phải được chọn
        $errors['gender'] = "Please select gender";
    }
    
    // ===== VALIDATION ALGORITHM: PHONE FIELD =====
    if (empty($new_phone)) {
        // Kiểm tra phone không được để trống
        $errors['phone'] = "Phone number cannot be empty";
    } else {
        // Thuật toán kiểm tra phone format:
        // 1. Sử dụng regex để kiểm tra format: 0xxxxxxxxx
        // 2. Bắt đầu bằng số 0
        // 3. Theo sau bởi đúng 9 chữ số
        // 4. Tổng cộng 10 chữ số
        if (!preg_match('/^0\d{9}$/', $new_phone)) {
            $errors['phone'] = "Phone number must be 10 digits starting with 0";
        } else {
            // Thuật toán kiểm tra trùng lặp phone:
            // 1. Tìm tất cả user có cùng phone number
            // 2. Loại trừ user hiện tại
            // 3. Nếu có kết quả > 0 thì phone đã tồn tại
            $phone_check_sql = "SELECT COUNT(*) as count FROM user WHERE PhoneNumber = ? AND Username != ?";
            $phone_check_stmt = $conn->prepare($phone_check_sql);
            $phone_check_stmt->bind_param('ss', $new_phone, $username);
            $phone_check_stmt->execute();
            $phone_result = $phone_check_stmt->get_result()->fetch_assoc();
            if ($phone_result['count'] > 0) {
                $errors['phone'] = "Phone number already exists";
            }
        }
    }
    
    // ===== FINAL VALIDATION: Kiểm tra tất cả lỗi =====
    // array_filter($errors) trả về mảng các phần tử không rỗng
    // Nếu mảng rỗng (không có lỗi) thì tiến hành update
    if (!array_filter($errors)) {
        // Thuật toán update profile:
        // 1. Sử dụng prepared statement để tránh SQL injection
        // 2. Update tất cả field: Username, Email, Address, Gender, PhoneNumber
        // 3. WHERE Username = current_username để update đúng user
        $update_sql = "UPDATE user SET Username = ?, Email = ?, Address = ?, Gender = ?, PhoneNumber = ? WHERE Username = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ssssss', $new_name, $new_email, $new_address, $new_gender, $new_phone, $username);
        
        if ($update_stmt->execute()) {
            $update_message = "Profile updated successfully!";
            
            // Thuật toán cập nhật session:
            // Nếu name thay đổi, cập nhật session để đồng bộ
            if ($new_name !== $username) {
                $_SESSION['username'] = $new_name;
            }
            // Refresh user info để hiển thị dữ liệu mới
            $username = $_SESSION['username'];
        } else {
            $update_message = "Error updating profile: " . $update_stmt->error;
        }
    }
}

// ===== ALGORITHM: Lấy thông tin user từ database =====
if ($username) {
    // Thuật toán lấy user info:
    // 1. Sử dụng prepared statement để tránh SQL injection
    // 2. Tìm user theo Username
    // 3. Lấy tất cả thông tin user
    $sql = "SELECT * FROM user WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
}

// ===== FALLBACK ALGORITHM: Đảm bảo luôn có dữ liệu hiển thị =====
// Nếu không lấy được user_info từ database, tạo dữ liệu mặc định
if (!$user_info) {
    $user_info = [
        'User_code' => $_SESSION['user_code'] ?? 'N/A',
        'Username' => $_SESSION['username'] ?? '',
        'Email' => 'N/A',
        'Address' => 'N/A',
        'Gender' => 'N/A',
        'PhoneNumber' => 'N/A',
        'Role_Id' => $_SESSION['role_id'] ?? 2
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Profile - Harbor Lights Hotel</title>
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
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/custom.css">
    <style>
        /* ===== CSS ALGORITHM: Styling cho validation states ===== */
        .error-message { 
            color: #dc3545; 
            font-size: 0.9rem; 
            margin-top: 5px; 
            display: block;
        }
        .form-control.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="hero-wrap profile-hero" style="background-image: url('images/bg_3.jpg');">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text d-flex align-itemd-center justify-content-center">
            <div class="col-md-9 ftco-animate text-center d-flex align-items-end justify-content-center">
                <div class="text">
                    <p class="breadcrumbs mb-2"><span class="mr-2"><a href="index.php">Home</a></span> <span>Profile</span></p>
                    <h1 class="mb-4 bread">User Profile</h1>
                    <p class="mb-4">Your account information</p>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="ftco-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($update_message): ?>
                    <div class="alert <?= strpos($update_message, 'successfully') !== false ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($update_message) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="icon-person"></i>
                        </div>
                        <h3><?= htmlspecialchars($user_info['Username']) ?></h3>
                        <p class="mb-0">
                            <span class="role-badge">
                                <?= ($user_info['Role_Id'] == 1) ? 'Administrator' : 'Customer' ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="profile-body">
                        <div class="info-item">
                            <span class="info-label">User Code</span>
                            <span class="info-value"><?= htmlspecialchars(isset($user_info['User_code']) ? $user_info['User_code'] : 'N/A') ?></span>
                        </div>
                        
                        <form method="POST" class="profile-form">
                            <!-- ===== FORM FIELD: NAME ===== -->
                            <div class="form-group">
                                <label for="name">Name</label>
                                <!-- Thuật toán hiển thị value: Ưu tiên POST data, fallback về database data -->
                                <input type="text" id="name" name="name" class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['name'] ?? (isset($user_info['Username']) ? $user_info['Username'] : '')) ?>" required>
                                <?php if ($errors['name']) echo "<div class='error-message'>{$errors['name']}</div>"; ?>
                            </div>
                            
                            <!-- ===== FORM FIELD: EMAIL ===== -->
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? (isset($user_info['Email']) ? $user_info['Email'] : '')) ?>" 
                                       pattern="[a-z0-9._%+-]+@gmail\.com$" required>
                                <small class="form-text text-muted">Must be a valid @gmail.com address</small>
                                <?php if ($errors['email']) echo "<div class='error-message'>{$errors['email']}</div>"; ?>
                            </div>
                            
                            <!-- ===== FORM FIELD: ADDRESS ===== -->
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" class="form-control <?= !empty($errors['address']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['address'] ?? (isset($user_info['Address']) ? $user_info['Address'] : '')) ?>" required>
                                <?php if ($errors['address']) echo "<div class='error-message'>{$errors['address']}</div>"; ?>
                            </div>
                            
                            <!-- ===== FORM FIELD: GENDER ===== -->
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" class="form-control <?= !empty($errors['gender']) ? 'is-invalid' : '' ?>" required>
                                    <option value="">Select Gender</option>
                                    <!-- Thuật toán selected option: Ưu tiên POST data, fallback về database data -->
                                    <option value="Male" <?= ($_POST['gender'] ?? (isset($user_info['Gender']) ? $user_info['Gender'] : '')) == 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($_POST['gender'] ?? (isset($user_info['Gender']) ? $user_info['Gender'] : '')) == 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Undisclosed" <?= ($_POST['gender'] ?? (isset($user_info['Gender']) ? $user_info['Gender'] : '')) == 'Undisclosed' ? 'selected' : '' ?>>Undisclosed</option>
                                </select>
                                <?php if ($errors['gender']) echo "<div class='error-message'>{$errors['gender']}</div>"; ?>
                            </div>
                            
                            <!-- ===== FORM FIELD: PHONE ===== -->
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control <?= !empty($errors['phone']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['phone'] ?? (isset($user_info['PhoneNumber']) ? $user_info['PhoneNumber'] : '')) ?>" 
                                       pattern="0\d{9}" required>
                                <small class="form-text text-muted">Must be 10 digits starting with '0'</small>
                                <?php if ($errors['phone']) echo "<div class='error-message'>{$errors['phone']}</div>"; ?>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="icon-save mr-2"></i>Update Profile
                                </button>
                                <a href="logout.php" class="btn btn-secondary">
                                    <i class="icon-logout mr-2"></i>Logout
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
<script src="js/custom.js"></script>

<script>
// ===== JAVASCRIPT ALGORITHM: Real-time Validation =====
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.querySelector('input[name="name"]');
    const emailInput = document.querySelector('input[name="email"]');
    const phoneInput = document.querySelector('input[name="phone"]');
    
    // ===== ALGORITHM: Name Validation =====
    let nameTimeout; // Debounce timer
    nameInput.addEventListener('input', function() {
        const name = this.value.trim();
        let error = '';
        
        // Thuật toán debounce: Clear timeout cũ trước khi set timeout mới
        clearTimeout(nameTimeout);
        
        if (name.length === 0) {
            // Kiểm tra trống
            error = 'Name cannot be empty';
            this.classList.remove('is-valid', 'is-invalid');
        } else {
            // Thuật toán AJAX validation với debounce:
            // 1. Chờ 500ms sau khi user ngừng gõ
            // 2. Gửi request kiểm tra trùng lặp
            // 3. Cập nhật UI dựa trên kết quả
            nameTimeout = setTimeout(() => {
                fetch('check_duplicate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'username=' + encodeURIComponent(name)
                })
                .then(response => response.json())
                .then(data => {
                    // Thuật toán cập nhật error message:
                    // 1. Tìm hoặc tạo error div
                    // 2. Cập nhật nội dung và CSS classes
                    let errorDiv = this.parentNode.querySelector('.error-message');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'error-message';
                        this.parentNode.appendChild(errorDiv);
                    }
                    
                    if (data.exists) {
                        errorDiv.textContent = 'Name already exists';
                        this.classList.add('is-invalid');
                        this.classList.remove('is-valid');
                    } else {
                        errorDiv.textContent = '';
                        this.classList.add('is-valid');
                        this.classList.remove('is-invalid');
                    }
                });
            }, 500);
        }
        
        // Thuật toán hiển thị error message tức thì
        let errorDiv = this.parentNode.querySelector('.error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            this.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = error;
    });
    
    // ===== ALGORITHM: Email Validation =====
    let emailTimeout; // Debounce timer
    emailInput.addEventListener('input', function() {
        const email = this.value.trim();
        let error = '';
        
        clearTimeout(emailTimeout);
        
        if (email.length === 0) {
            // Kiểm tra trống
            error = 'Email cannot be empty';
            this.classList.remove('is-valid', 'is-invalid');
        } else if (!/^[a-zA-Z0-9._%+-]+@gmail\.com$/.test(email)) {
            // Thuật toán regex validation:
            // 1. Kiểm tra format email với regex
            // 2. Chỉ cho phép @gmail.com
            error = 'Email must be a valid Gmail address (@gmail.com)';
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        } else {
            // Thuật toán AJAX validation với debounce
            emailTimeout = setTimeout(() => {
                fetch('check_duplicate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'email=' + encodeURIComponent(email)
                })
                .then(response => response.json())
                .then(data => {
                    let errorDiv = this.parentNode.querySelector('.error-message');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'error-message';
                        this.parentNode.appendChild(errorDiv);
                    }
                    
                    if (data.exists) {
                        errorDiv.textContent = 'Email already exists';
                        this.classList.add('is-invalid');
                        this.classList.remove('is-valid');
                    } else {
                        errorDiv.textContent = '';
                        this.classList.add('is-valid');
                        this.classList.remove('is-invalid');
                    }
                });
            }, 500);
        }
        
        // Hiển thị error message tức thì
        let errorDiv = this.parentNode.querySelector('.error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            this.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = error;
    });
    
    // ===== ALGORITHM: Phone Validation =====
    let phoneTimeout; // Debounce timer
    phoneInput.addEventListener('input', function() {
        const phone = this.value.trim();
        let error = '';
        
        clearTimeout(phoneTimeout);
        
        if (phone.length === 0) {
            // Kiểm tra trống
            error = 'Phone number cannot be empty';
            this.classList.remove('is-valid', 'is-invalid');
        } else if (!/^0\d{9}$/.test(phone)) {
            // Thuật toán regex validation:
            // 1. Kiểm tra format phone với regex
            // 2. Bắt đầu bằng 0, theo sau bởi 9 chữ số
            error = 'Phone number must be 10 digits starting with 0';
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        } else {
            // Thuật toán AJAX validation với debounce
            phoneTimeout = setTimeout(() => {
                fetch('check_duplicate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'phone=' + encodeURIComponent(phone)
                })
                .then(response => response.json())
                .then(data => {
                    let errorDiv = this.parentNode.querySelector('.error-message');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'error-message';
                        this.parentNode.appendChild(errorDiv);
                    }
                    
                    if (data.exists) {
                        errorDiv.textContent = 'Phone number already exists';
                        this.classList.add('is-invalid');
                        this.classList.remove('is-valid');
                    } else {
                        errorDiv.textContent = '';
                        this.classList.add('is-valid');
                        this.classList.remove('is-invalid');
                    }
                });
            }, 500);
        }
        
        // Hiển thị error message tức thì
        let errorDiv = this.parentNode.querySelector('.error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            this.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = error;
    });
});
</script>

</body>
</html>
