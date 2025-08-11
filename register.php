<?php 
// ===== SESSION MANAGEMENT ALGORITHM =====
// Khởi tạo session để lưu trữ thông tin user
session_start();

// ===== DATABASE CONNECTION ALGORITHM =====
// Cấu hình kết nối database
$servername = "sql203.infinityfree.com";
$dbusername = "if0_39667996";
$dbpassword = "3xJyzO66bT";
$dbname = "if0_39667996_asm";

// Thuật toán kết nối database với error handling
$conn = mysqli_connect($servername, $dbusername, $dbpassword, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ===== ERROR HANDLING ALGORITHM =====
// Khởi tạo biến lưu thông báo chung
$message = "";

// Thuật toán khởi tạo mảng lỗi cho từng field
// Mỗi field có một key riêng để hiển thị lỗi cụ thể
$errors = [
    'username' => '',
    'email' => '',
    'password' => '',
    'address' => '',
    'gender' => '',
    'phone' => '',
];

// ===== FORM PROCESSING ALGORITHM =====
// Kiểm tra khi user submit form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Thuật toán làm sạch dữ liệu đầu vào
    // trim() loại bỏ khoảng trắng đầu cuối
    $Username = trim($_POST["username"]);
    $Email = trim($_POST["email"]);
    $Password = trim($_POST["password"]);
    $Address = trim($_POST["address"]);
    $Gender = trim($_POST["gender"]);
    $PhoneNumber = trim($_POST["phone"]);

    // ===== VALIDATION ALGORITHM: USERNAME FIELD =====
    if (empty($Username)) {
        // Kiểm tra username không được để trống
        $errors['username'] = "Username cannot be empty";
    } else {
        // Thuật toán kiểm tra trùng lặp username:
        // 1. Sử dụng prepared statement để tránh SQL injection
        // 2. Tìm tất cả user có cùng username
        // 3. Nếu có kết quả > 0 thì username đã tồn tại
        $check_username = "SELECT * FROM user WHERE Username = ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param("s", $Username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['username'] = "Username already exists";
        }
    }

    // ===== VALIDATION ALGORITHM: EMAIL FIELD =====
    if (empty($Email)) {
        // Kiểm tra email không được để trống
        $errors['email'] = "Email cannot be empty";
    } else {
        // Thuật toán kiểm tra email format:
        // 1. Sử dụng regex để kiểm tra format @gmail.com
        // 2. Chỉ cho phép: chữ cái, số, dấu chấm, gạch dưới, % + - trước @
        // 3. Phải kết thúc bằng @gmail.com
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $Email)) {
            $errors['email'] = "Email must be a valid Gmail address (@gmail.com)";
        } else {
            // Thuật toán kiểm tra trùng lặp email:
            // 1. Tìm tất cả user có cùng email
            // 2. Nếu có kết quả > 0 thì email đã tồn tại
            $check_email = "SELECT * FROM user WHERE Email = ?";
            $stmt = $conn->prepare($check_email);
            $stmt->bind_param("s", $Email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors['email'] = "Email already exists";
            }
        }
    }

    // ===== VALIDATION ALGORITHM: PASSWORD FIELD =====
    if (empty($Password)) {
        // Kiểm tra password không được để trống
        $errors['password'] = "Password cannot be empty";
    } else {
        // Thuật toán kiểm tra password complexity:
        // 1. (?=.*[A-Z]) - Phải có ít nhất 1 chữ hoa
        // 2. (?=.*[a-z]) - Phải có ít nhất 1 chữ thường
        // 3. (?=.*\d) - Phải có ít nhất 1 số
        // 4. (?!.*[A-Z].*[A-Z]) - Không được có 2 chữ hoa liên tiếp
        // 5. [A-Za-z\d]{6,} - Chỉ cho phép chữ và số, tối thiểu 6 ký tự
        if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?!.*[A-Z].*[A-Z])[A-Za-z\d]{6,}$/', $Password)) {
            $errors['password'] = "Password must contain: letters and numbers, maximum 1 uppercase letter, no special characters, minimum 6 characters";
        }
    }

    // ===== VALIDATION ALGORITHM: ADDRESS FIELD =====
    if (empty($Address)) {
        // Kiểm tra address không được để trống
        $errors['address'] = "Address cannot be empty";
    }

    // ===== VALIDATION ALGORITHM: GENDER FIELD =====
    if (empty($Gender)) {
        // Kiểm tra gender phải được chọn
        $errors['gender'] = "Please select gender";
    }

    // ===== VALIDATION ALGORITHM: PHONE FIELD =====
    if (empty($PhoneNumber)) {
        // Kiểm tra phone không được để trống
        $errors['phone'] = "Phone number cannot be empty";
    } else {
        // Thuật toán kiểm tra phone format:
        // 1. Sử dụng regex để kiểm tra format: 0xxxxxxxxx
        // 2. Bắt đầu bằng số 0
        // 3. Theo sau bởi đúng 9 chữ số
        // 4. Tổng cộng 10 chữ số
        if (!preg_match('/^0\d{9}$/', $PhoneNumber)) {
            $errors['phone'] = "Phone number must be 10 digits starting with 0";
        } else {
            // Thuật toán kiểm tra trùng lặp phone:
            // 1. Tìm tất cả user có cùng phone number
            // 2. Nếu có kết quả > 0 thì phone đã tồn tại
            $check_phone = "SELECT * FROM user WHERE Phonenumber = ?";
            $stmt = $conn->prepare($check_phone);
            $stmt->bind_param("s", $PhoneNumber);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors['phone'] = "Phone number already exists";
            }
        }
    }

    // ===== FINAL VALIDATION ALGORITHM =====
    // array_filter($errors) trả về mảng các phần tử không rỗng
    // Nếu mảng rỗng (không có lỗi) thì tiến hành đăng ký
    if (!array_filter($errors)) {
        // Thuật toán đăng ký user:
        // 1. Set Role_Id = 2 (Customer role)
        // 2. Sử dụng prepared statement để tránh SQL injection
        // 3. Insert tất cả thông tin user vào database
        $Role_Id = 2;

        $sql = "INSERT INTO user (Username, Role_Id, Email, Password, Address, Gender, Phonenumber)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssss", $Username, $Role_Id, $Email, $Password, $Address, $Gender, $PhoneNumber);

        if ($stmt->execute()) {
            // Thuật toán redirect sau khi đăng ký thành công
            header("Location: contact.php");
            exit();
        } else {
            $message = "<div class='error-message'>Registration failed: " . $stmt->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Registration - Harbor Lights</title>
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
  
  <!-- ===== CUSTOM CSS ALGORITHM ===== -->
  <style>
    /* Thuật toán styling cho error messages */
    .error-message { 
      color: #dc3545; 
      font-size: 0.9rem; 
      margin-top: 5px; 
      display: block;
    }
    
    /* Thuật toán styling cho validation states */
    .form-control.is-valid {
      border-color: #28a745;
      box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    .form-control.is-invalid {
      border-color: #dc3545;
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    /* Thuật toán spacing cho form groups */
    .form-group {
      margin-bottom: 1.5rem;
    }
    
    /* Thuật toán styling cho form container */
    .contact-form {
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- ===== HERO SECTION ALGORITHM ===== -->
<div class="hero-wrap" style="background-image: url('images/bg_3.jpg');">
  <div class="overlay"></div>
  <div class="container">
    <div class="row no-gutters slider-text justify-content-center">
      <div class="col-md-9 text-center d-flex align-items-end justify-content-center">
        <div class="text">
          <h1 class="mb-4 bread">Register</h1>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== REGISTRATION FORM ALGORITHM ===== -->
<section class="ftco-section contact-section bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-7">
        <!-- Thuật toán hiển thị message -->
        <?= $message ?>
        
        <!-- Thuật toán form với validation -->
        <form method="POST" class="bg-white p-5 contact-form">
          <h3 class="mb-4 text-center">Create a new account</h3>

          <!-- ===== FORM FIELD: USERNAME ===== -->
          <div class="form-group">
            <!-- Thuật toán hiển thị value: Ưu tiên POST data -->
            <input type="text" name="username" class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>" 
                   placeholder="Username" value="<?= $_POST['username'] ?? '' ?>">
            <!-- Thuật toán hiển thị error message -->
            <?php if ($errors['username']) echo "<div class='error-message'>{$errors['username']}</div>"; ?>
          </div>

          <!-- ===== FORM FIELD: EMAIL ===== -->
          <div class="form-group">
            <input type="text" name="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" 
                   placeholder="Email" value="<?= $_POST['email'] ?? '' ?>">
            <?php if ($errors['email']) echo "<div class='error-message'>{$errors['email']}</div>"; ?>
          </div>

          <!-- ===== FORM FIELD: PASSWORD ===== -->
          <div class="form-group">
            <input type="password" name="password" class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" 
                   placeholder="Password">
            <?php if ($errors['password']) echo "<div class='error-message'>{$errors['password']}</div>"; ?>
          </div>

          <!-- ===== FORM FIELD: ADDRESS ===== -->
          <div class="form-group">
            <input type="text" name="address" class="form-control <?= !empty($errors['address']) ? 'is-invalid' : '' ?>" 
                   placeholder="Address" value="<?= $_POST['address'] ?? '' ?>">
            <?php if ($errors['address']) echo "<div class='error-message'>{$errors['address']}</div>"; ?>
          </div>

          <!-- ===== FORM FIELD: GENDER ===== -->
          <div class="form-group">
            <select name="gender" class="form-control <?= !empty($errors['gender']) ? 'is-invalid' : '' ?>">
              <option value="">Select Gender</option>
              <!-- Thuật toán selected option: Ưu tiên POST data -->
              <option value="Male" <?= ($_POST['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
              <option value="Female" <?= ($_POST['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
              <option value="Undisclosed" <?= ($_POST['gender'] ?? '') == 'Undisclosed' ? 'selected' : '' ?>>Undisclosed</option>
            </select>
            <?php if ($errors['gender']) echo "<div class='error-message'>{$errors['gender']}</div>"; ?>
          </div>

          <!-- ===== FORM FIELD: PHONE ===== -->
          <div class="form-group">
            <input type="text" name="phone" class="form-control <?= !empty($errors['phone']) ? 'is-invalid' : '' ?>" 
                   placeholder="Phone Number" value="<?= $_POST['phone'] ?? '' ?>">
            <?php if ($errors['phone']) echo "<div class='error-message'>{$errors['phone']}</div>"; ?>
          </div>

          <!-- ===== FORM SUBMIT ALGORITHM ===== -->
          <div class="form-group text-center">
            <input type="submit" value="Register" class="btn btn-primary py-3 px-5">
          </div>
          <p class="text-center">Already have an account? <a href="contact.php">Login here</a></p>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ===== FOOTER ALGORITHM ===== -->
<footer class="ftco-footer ftco-section img" style="background-image: url(images/bg_4.jpg);">
  <div class="overlay"></div>
  <!-- Giữ nguyên như giao diện Harbor Lights -->
</footer>

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

<!-- ===== JAVASCRIPT ALGORITHM: Real-time Validation ===== -->
<script>
// Thuật toán khởi tạo validation khi DOM load xong
document.addEventListener('DOMContentLoaded', function() {
    // Thuật toán lấy các input elements
    const usernameInput = document.querySelector('input[name="username"]');
    const emailInput = document.querySelector('input[name="email"]');
    const passwordInput = document.querySelector('input[name="password"]');
    const phoneInput = document.querySelector('input[name="phone"]');
    
    // ===== ALGORITHM: Username Real-time Validation =====
    let usernameTimeout; // Debounce timer
    usernameInput.addEventListener('input', function() {
        const username = this.value.trim();
        let error = '';
        
        // Thuật toán debounce: Clear timeout cũ trước khi set timeout mới
        clearTimeout(usernameTimeout);
        
        if (username.length === 0) {
            // Kiểm tra trống
            error = 'Username cannot be empty';
            this.classList.remove('is-valid', 'is-invalid');
        } else {
            // Thuật toán AJAX validation với debounce:
            // 1. Chờ 500ms sau khi user ngừng gõ
            // 2. Gửi request kiểm tra trùng lặp
            // 3. Cập nhật UI dựa trên kết quả
            usernameTimeout = setTimeout(() => {
                fetch('check_duplicate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'username=' + encodeURIComponent(username)
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
                        errorDiv.textContent = 'Username already exists';
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
    
    // ===== ALGORITHM: Email Real-time Validation =====
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
    
    // ===== ALGORITHM: Password Real-time Validation =====
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let error = '';
        
        if (password.length === 0) {
            // Kiểm tra trống
            error = 'Password cannot be empty';
            this.classList.remove('is-valid', 'is-invalid');
        } else if (!/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?!.*[A-Z].*[A-Z])[A-Za-z\d]{6,}$/.test(password)) {
            // Thuật toán regex validation cho password complexity
            error = 'Password must contain: letters and numbers, maximum 1 uppercase letter, no special characters, minimum 6 characters';
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        } else {
            // Password hợp lệ
            error = '';
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        }
        
        // Hiển thị error message
        let errorDiv = this.parentNode.querySelector('.error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            this.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = error;
    });
    
    // ===== ALGORITHM: Phone Real-time Validation =====
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
