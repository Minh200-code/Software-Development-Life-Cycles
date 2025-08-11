<?php
header('Content-Type: application/json');

// Database connection configuration
$servername = "sql203.infinityfree.com";
$dbusername = "if0_39667996";
$dbpassword = "3xJyzO66bT";
$dbname = "if0_39667996_asm";

$conn = mysqli_connect($servername, $dbusername, $dbpassword, $dbname);
if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['exists' => false];
    
    // Check username
    if (isset($_POST['username'])) {
        $username = trim($_POST['username']);
        if (!empty($username)) {
            $check_username = "SELECT COUNT(*) as count FROM user WHERE Username = ?";
            $stmt = $conn->prepare($check_username);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $response = ['exists' => $row['count'] > 0];
        }
    }
    
    // Check email
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        if (!empty($email)) {
            $check_email = "SELECT COUNT(*) as count FROM user WHERE Email = ?";
            $stmt = $conn->prepare($check_email);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $response = ['exists' => $row['count'] > 0];
        }
    }
    
    // Check phone
    if (isset($_POST['phone'])) {
        $phone = trim($_POST['phone']);
        if (!empty($phone)) {
            $check_phone = "SELECT COUNT(*) as count FROM user WHERE Phonenumber = ?";
            $stmt = $conn->prepare($check_phone);
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $response = ['exists' => $row['count'] > 0];
        }
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?> 