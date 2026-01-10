<?php
include("../main_connection.php");


$db_name = "rest_m1_trs"; // ✅ pick the DB you want

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name]; // ✅ get the correct DB connection

$date = $_GET['date'] ?? '';
if (!$date) {
  echo json_encode([]);
  exit;
}

$stmt = $conn->prepare(
  "SELECT name, contact, start_time, end_time, size, type, status 
   FROM reservations 
   WHERE DATE(reservation_date) = ? 
   ORDER BY start_time DESC"
);

$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
  $reservations[] = $row;
}

header('Content-Type: application/json');
echo json_encode($reservations);
?>
