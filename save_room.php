<?php
$conn = new mysqli("sql203.infinityfree.com", "if0_39667996", "3xJyzO66bT", "if0_39667996_asm");
if ($conn->connect_error) die("Connection failed");

$Room_code = $_POST['Room_code'];
$Room_name = $_POST['Room_name'];
$RoomtypeID = $_POST['RoomtypeID'];
$Description = $_POST['Description'];
$Capacity = $_POST['Capacity'];
$Status = $_POST['Status'];
$ImageURL = $_POST['ImageURL'];

if ($Room_code) {
  // Cập nhật
  $stmt = $conn->prepare("UPDATE room SET Room_name=?, RoomtypeID=?, Description=?, Capacity=?, Status=?, ImageURL=? WHERE Room_code=?");
  $stmt->bind_param("sisiisi", $Room_name, $RoomtypeID, $Description, $Capacity, $Status, $ImageURL, $Room_code);
} else {
  // Thêm mới
  $stmt = $conn->prepare("INSERT INTO room (Room_name, RoomtypeID, Description, Capacity, Status, ImageURL) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("sisiss", $Room_name, $RoomtypeID, $Description, $Capacity, $Status, $ImageURL);
}
$stmt->execute();
$stmt->close();

header("Location: manage_rooms.php");
exit();
