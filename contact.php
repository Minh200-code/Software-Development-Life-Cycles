
<?php 
// ===== SESSION MANAGEMENT ALGORITHM =====
// Khởi tạo session để lưu trữ thông tin user sau khi login
session_start();

// ===== DATABASE CONNECTION ALGORITHM =====
// Thuật toán kết nối database với error handling
$conn = mysqli_connect("sql203.infinityfree.com", "if0_39667996", "3xJyzO66bT", "if0_39667996_asm");
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
    'password' => '',
];

// ===== LOGIN PROCESSING ALGORITHM =====
// Kiểm tra khi user submit form login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Thuật toán làm sạch dữ liệu đầu vào
    // trim() loại bỏ khoảng trắng đầu cuối
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // ===== VALIDATION ALGORITHM: FORM FIELDS =====
    // Kiểm tra username không được để trống
    if (empty($username)) $errors['username'] = "Username is required.";
    // Kiểm tra password không được để trống
    if (empty($password)) $errors['password'] = "Password is required.";

    // ===== FINAL VALIDATION ALGORITHM =====
    // array_filter($errors) trả về mảng các phần tử không rỗng
    // Nếu mảng rỗng (không có lỗi) thì tiến hành login
    if (!array_filter($errors)) {
        // ===== AUTHENTICATION ALGORITHM =====
        // Thuật toán xác thực user:
        // 1. Tìm user theo username trong database
        // 2. Kiểm tra password có khớp không
        // 3. Nếu khớp thì tạo session và redirect
        $query = "SELECT * FROM user WHERE Username = '$username'";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) == 1) {
            // Thuật toán kiểm tra password:
            // 1. Lấy thông tin user từ database
            // 2. So sánh password nhập với password trong database
            $user = mysqli_fetch_assoc($result);
            if ($password === $user['Password']) {
                // ===== SESSION CREATION ALGORITHM =====
                // Thuật toán tạo session sau khi login thành công:
                // 1. Lưu username vào session
                // 2. Lưu role_id để phân quyền
                // 3. Lưu user_code để tham chiếu
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role_id'] = $user['Role_Id'];
                $_SESSION['user_code'] = $user['User_code'];

                // ===== ROLE-BASED REDIRECTION ALGORITHM =====
                // Thuật toán redirect dựa trên role:
                // 1. Role_Id = 1: Administrator
                // 2. Role_Id = 2: Customer
                // 3. Cả hai đều redirect về index.php
                if ($user['Role_Id'] == 1 || $user['Role_Id'] == 2) {
                    header("Location: index.php");
                    exit();
                }
            } else {
                // Thuật toán hiển thị lỗi password sai
                $message = "<div class='error-message'>Incorrect password.</div>";
            }
        } else {
            // Thuật toán hiển thị lỗi username không tồn tại
            $message = "<div class='error-message'>Username not found.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Login - Harborlights</title>
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
  
  <!-- ===== CUSTOM CSS ALGORITHM ===== -->
  <style>
    /* Thuật toán styling cho error messages */
    .error-message { color: red; font-size: 0.9rem; margin-top: 5px; }
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
          <h1 class="mb-4 bread">Login</h1>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== LOGIN FORM ALGORITHM ===== -->
<section class="ftco-section contact-section bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <!-- Thuật toán hiển thị message -->
        <?= $message ?>
        
        <!-- Thuật toán form login với validation -->
        <form action="" method="POST" class="bg-white p-5 contact-form">
          <h3 class="mb-4 text-center">Login to your account</h3>
          
          <!-- ===== FORM FIELD: USERNAME ===== -->
          <div class="form-group">
            <!-- Thuật toán hiển thị value: Ưu tiên POST data -->
            <input type="text" name="username" class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>" 
                   placeholder="Username" value="<?= $_POST['username'] ?? '' ?>">
            <!-- Thuật toán hiển thị error message -->
            <?php if ($errors['username']) echo "<div class='error-message'>{$errors['username']}</div>"; ?>
          </div>
          
          <!-- ===== FORM FIELD: PASSWORD ===== -->
          <div class="form-group">
            <input type="password" name="password" class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" 
                   placeholder="Password">
            <?php if ($errors['password']) echo "<div class='error-message'>{$errors['password']}</div>"; ?>
          </div>
          
          <!-- ===== FORM SUBMIT ALGORITHM ===== -->
          <div class="form-group text-center">
            <input type="submit" value="Login" class="btn btn-primary py-3 px-5">
          </div>
          <p class="text-center">Don't have an account? <a href="register.php">Register here</a></p>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ===== FOOTER ALGORITHM ===== -->
<footer class="ftco-footer ftco-section img" style="background-image: url(images/bg_4.jpg);">
  <div class="overlay"></div>
  <div class="container">
    <div class="row mb-5">
      <div class="col-md">
        <div class="ftco-footer-widget mb-4">
          <h2 class="ftco-heading-2">Harbor Lights</h2>
          <p>Experience luxury and comfort at its finest. Your perfect stay awaits.</p>
          <ul class="ftco-footer-social list-unstyled float-md-left float-lft mt-5">
            <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
            <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
            <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
          </ul>
        </div>
      </div>
      <div class="col-md">
        <div class="ftco-footer-widget mb-4 ml-md-5">
          <h2 class="ftco-heading-2">Quick Links</h2>
          <ul class="list-unstyled">
            <li><a href="index.php" class="py-2 d-block">Home</a></li>
            <li><a href="about.php" class="py-2 d-block">About</a></li>
            <li><a href="rooms.php" class="py-2 d-block">Rooms</a></li>
            <li><a href="restaurant.php" class="py-2 d-block">Restaurant</a></li>
          </ul>
        </div>
      </div>
      <div class="col-md">
        <div class="ftco-footer-widget mb-4">
          <h2 class="ftco-heading-2">Contact Info</h2>
          <div class="block-23 mb-3">
            <ul>
              <li><span class="icon icon-map-marker"></span><span class="text">123 Harbor Street, Coastal City</span></li>
              <li><a href="#"><span class="icon icon-phone"></span><span class="text">+1 234 567 8900</span></a></li>
              <li><a href="#"><span class="icon icon-envelope"></span><span class="text">daotuanminh@gmail.com</span></a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12 text-center">
        <p>Copyright &copy; 2024 Harbor Lights Hotel. All rights reserved.</p>
      </div>
    </div>
  </div>
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

</body>
</html>
