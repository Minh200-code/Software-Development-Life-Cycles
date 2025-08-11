<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("sql203.infinityfree.com", "if0_39667996", "3xJyzO66bT", "if0_39667996_asm");
if ($conn->connect_error) {
    echo json_encode(['available' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get parameters
$room_code = $_POST['room_code'] ?? '';
$check_in = $_POST['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? '';

if (!$room_code || !$check_in || !$check_out) {
    echo json_encode(['available' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Validate dates
$today = date('Y-m-d');
if ($check_in < $today) {
    echo json_encode(['available' => false, 'message' => 'Check-in date cannot be in the past']);
    exit();
}

if ($check_out <= $check_in) {
    echo json_encode(['available' => false, 'message' => 'Check-out date must be after check-in date']);
    exit();
}

// Check if room is available for the selected dates
$check_availability_sql = "SELECT Booking_code, User_code FROM booking 
                         WHERE Room_code = ? AND Status = 'Booked' 
                         AND (
                             (CheckInDate <= ? AND CheckOutDate > ?) OR
                             (CheckInDate < ? AND CheckOutDate >= ?) OR
                             (CheckInDate >= ? AND CheckOutDate <= ?)
                         )";
$check_availability_stmt = $conn->prepare($check_availability_sql);
if (!$check_availability_stmt) {
    echo json_encode(['available' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}
$check_availability_stmt->bind_param("sssssss", $room_code, $check_in, $check_in, $check_out, $check_out, $check_in, $check_out);
$check_availability_stmt->execute();
$existing_booking = $check_availability_stmt->get_result()->fetch_assoc();

if ($existing_booking) {
    // Check if it's the current user's booking
    session_start();
    $current_user = $_SESSION['user_code'] ?? '';
    
    if ($existing_booking['User_code'] == $current_user) {
        echo json_encode(['available' => false, 'message' => 'You already have a booking for this room during this time period. Please choose different dates.']);
    } else {
        echo json_encode(['available' => false, 'message' => 'This room has already been booked during this time period. Please choose different dates.']);
    }
} else {
    echo json_encode(['available' => true, 'message' => 'Room is available']);
}

$conn->close();
?> 