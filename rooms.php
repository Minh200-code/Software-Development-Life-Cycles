<?php
session_start();

// Database connection
$conn = new mysqli("sql203.infinityfree.com", "if0_39667996", "3xJyzO66bT", "if0_39667996_asm");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Build search query
$where_conditions = ["r.Status = 1"];
$params = [];
$param_types = "";

// Room type filter
if (!empty($_GET['room_type'])) {
    $where_conditions[] = "rt.Room_typename = ?";
    $params[] = $_GET['room_type'];
    $param_types .= "s";
}

// Price range filter
if (!empty($_GET['price_range'])) {
    $price_range = $_GET['price_range'];
    if ($price_range == '0-500000') {
        $where_conditions[] = "rt.Room_price <= 500000";
    } elseif ($price_range == '500000-1000000') {
        $where_conditions[] = "rt.Room_price BETWEEN 500000 AND 1000000";
    } elseif ($price_range == '1000000-2000000') {
        $where_conditions[] = "rt.Room_price BETWEEN 1000000 AND 2000000";
    } elseif ($price_range == '2000000+') {
        $where_conditions[] = "rt.Room_price > 2000000";
    }
}

// Capacity filter
if (!empty($_GET['capacity'])) {
    $capacity = $_GET['capacity'];
    if ($capacity == '4+') {
        $where_conditions[] = "r.Capacity >= 4";
    } else {
        $where_conditions[] = "r.Capacity = ?";
        $params[] = $capacity;
        $param_types .= "i";
    }
}

// Search by name
if (!empty($_GET['search'])) {
    $where_conditions[] = "r.Room_name LIKE ?";
    $params[] = '%' . $_GET['search'] . '%';
    $param_types .= "s";
}

$sql = "SELECT r.*, rt.Room_typename, rt.Room_price 
        FROM room r 
        LEFT JOIN roomtypeid rt ON r.RoomtypeID = rt.RoomtypeID 
        WHERE " . implode(" AND ", $where_conditions) . " 
        ORDER BY r.Room_code";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Harborlights - Free Bootstrap 4 Template by Colorlib</title>
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

<?php include 'navbar.php'; ?>
    <!-- END nav -->
		<div class="hero-wrap" style="background-image: url('images/bg_3.jpg');">
      <div class="overlay"></div>
      <div class="container">
        <div class="row no-gutters slider-text d-flex align-itemd-center justify-content-center">
          <div class="col-md-9 ftco-animate text-center d-flex align-items-end justify-content-center">
          	<div class="text">
	            <p class="breadcrumbs mb-2"><span class="mr-2"><a href="index.php">Home</a></span> <span>Rooms</span></p>
	            <h1 class="mb-4 bread">Rooms</h1>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Section -->
    <section class="ftco-section bg-light">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-8">
            <div class="search-card bg-white p-4 rounded shadow-sm">
              <h4 class="text-center mb-4"><i class="icon-search mr-2"></i>Search Rooms</h4>
              <form method="GET" action="rooms.php">
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="small text-muted">Room Type</label>
                      <select name="room_type" class="form-control">
                        <option value="">All Types</option>
                        <?php
                        $type_sql = "SELECT DISTINCT Room_typename FROM roomtypeid ORDER BY Room_typename";
                        $type_result = $conn->query($type_sql);
                        while ($type = $type_result->fetch_assoc()) {
                          $selected = (isset($_GET['room_type']) && $_GET['room_type'] == $type['Room_typename']) ? 'selected' : '';
                          echo "<option value='" . htmlspecialchars($type['Room_typename']) . "' $selected>" . htmlspecialchars($type['Room_typename']) . "</option>";
                        }
                        ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="small text-muted">Price Range</label>
                      <select name="price_range" class="form-control">
                        <option value="">All Prices</option>
                        <option value="0-500000" <?= (isset($_GET['price_range']) && $_GET['price_range'] == '0-500000') ? 'selected' : '' ?>>Under 500,000 VND</option>
                        <option value="500000-1000000" <?= (isset($_GET['price_range']) && $_GET['price_range'] == '500000-1000000') ? 'selected' : '' ?>>500,000 - 1,000,000 VND</option>
                        <option value="1000000-2000000" <?= (isset($_GET['price_range']) && $_GET['price_range'] == '1000000-2000000') ? 'selected' : '' ?>>1,000,000 - 2,000,000 VND</option>
                        <option value="2000000+" <?= (isset($_GET['price_range']) && $_GET['price_range'] == '2000000+') ? 'selected' : '' ?>>Over 2,000,000 VND</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="small text-muted">Capacity</label>
                      <select name="capacity" class="form-control">
                        <option value="">Any Capacity</option>
                        <option value="1" <?= (isset($_GET['capacity']) && $_GET['capacity'] == '1') ? 'selected' : '' ?>>1 Person</option>
                        <option value="2" <?= (isset($_GET['capacity']) && $_GET['capacity'] == '2') ? 'selected' : '' ?>>2 Persons</option>
                        <option value="3" <?= (isset($_GET['capacity']) && $_GET['capacity'] == '3') ? 'selected' : '' ?>>3 Persons</option>
                        <option value="4+" <?= (isset($_GET['capacity']) && $_GET['capacity'] == '4+') ? 'selected' : '' ?>>4+ Persons</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="row mt-3">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="small text-muted">Search by Name</label>
                      <input type="text" name="search" class="form-control" placeholder="Room name..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group d-flex align-items-end">
                      <button type="submit" class="btn btn-primary w-100">
                        <i class="icon-search mr-2"></i>Search Rooms
                      </button>
                    </div>
                  </div>
                </div>
                <?php if (isset($_GET['room_type']) || isset($_GET['price_range']) || isset($_GET['capacity']) || isset($_GET['search'])): ?>
                <div class="row mt-3">
                  <div class="col-12 text-center">
                    <a href="rooms.php" class="btn btn-outline-secondary btn-sm">
                      <i class="icon-refresh mr-2"></i>Clear Filters
                    </a>
                  </div>
                </div>
                <?php endif; ?>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="ftco-section ftco-no-pb ftco-room">
    	<div class="container-fluid px-0">
    		<div class="row no-gutters justify-content-center mb-5 pb-3">
          <div class="col-md-7 heading-section text-center ftco-animate">
          	<span class="subheading">Harbor Lights Rooms</span>
            <h2 class="mb-4">Hotel Master's Rooms</h2>
          </div>
        </div>  
    		<div class="row no-gutters">
    			<?php 
    			$counter = 0;
    			if ($result && $result->num_rows > 0): 
    				while ($room = $result->fetch_assoc()): 
    					$counter++;
    					$isEven = $counter % 2 == 0;
    					$imageUrl = $room['ImageURL'] ? 'images/' . $room['ImageURL'] : 'images/room-' . ($counter % 6 + 1) . '.jpg';
    			?>
    			<div class="col-lg-6">
    				<div class="room-wrap d-md-flex ftco-animate">
    					<a href="rooms-single.php?room=<?= $room['Room_code'] ?>" class="img <?= $isEven ? 'order-md-last' : '' ?>" style="background-image: url(<?= $imageUrl ?>);"></a>
    					<div class="half <?= $isEven ? 'right-arrow' : 'left-arrow' ?> d-flex align-items-center">
    						<div class="text p-4 text-center">
    							<p class="star mb-0">
    								<span class="ion-ios-star"></span>
    								<span class="ion-ios-star"></span>
    								<span class="ion-ios-star"></span>
    								<span class="ion-ios-star"></span>
    								<span class="ion-ios-star"></span>
    							</p>
    							<p class="mb-0">
    								<span class="price mr-1"><?= number_format($room['Room_price']) ?> VND</span> 
    								<span class="per">per night</span>
    							</p>
    							<h3 class="mb-3">
    								<a href="rooms-single.php?room=<?= $room['Room_code'] ?>"><?= htmlspecialchars($room['Room_name']) ?></a>
    							</h3>
    							<?php if ($room['Description']): ?>
    								<p class="mb-2"><small class="text-muted"><?= htmlspecialchars(substr($room['Description'], 0, 100)) ?><?= strlen($room['Description']) > 100 ? '...' : '' ?></small></p>
    							<?php endif; ?>
    							<p class="mb-2"><small class="text-muted">Capacity: <?= $room['Capacity'] ?> persons</small></p>
    							<p class="pt-1">
    								<a href="rooms-single.php?room=<?= $room['Room_code'] ?>" class="btn-custom px-3 py-2 rounded">
    									View Details <span class="icon-long-arrow-right"></span>
    								</a>
    							</p>
    						</div>
    					</div>
    				</div>
    			</div>
    			<?php 
    				endwhile; 
    			else: 
    			?>
    			<div class="col-12">
    				<div class="text-center py-5">
    					<h3>No rooms available at the moment</h3>
    					<p class="text-muted">Please check back later for available rooms.</p>
    				</div>
    			</div>
    			<?php endif; ?>
    		</div>
    	</div>
    </section>


    <footer class="ftco-footer ftco-section img" style="background-image: url(images/bg_4.jpg);">
    	<div class="overlay"></div>
      <div class="container">
        <div class="row mb-5">
          <div class="col-md">
            <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2">Harbor Lights</h2>
              <p>Enjoy a relaxing stay with modern amenities and friendly service at Harbor Lights Hotel. Your comfort is our priority.</p>
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
    
  </body>
</html>