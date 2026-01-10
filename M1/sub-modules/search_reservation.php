<?php
include '../../connection.php';
header('Content-Type: application/json');

$name = trim($_POST['name'] ?? '');

if (!$name) {
  echo json_encode(['success' => false, 'error' => 'No name provided']);
  exit;
}

$stmt = $conn->prepare("SELECT * FROM reservations WHERE name LIKE ? LIMIT 1");
$search = "%$name%";
$stmt->bind_param('s', $search);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  echo json_encode([
    'success' => true,
    'reservation' => [
      'id' => $row['reservation_id'],
      'name' => $row['name'],
      'reservation_date' => $row['reservation_date'],
      'start_time' => $row['start_time'],
      'guests' => $row['guests'],
      'status' => $row['status'],
      'notes' => $row['notes']
    ]
  ]);
} else {
  echo json_encode(['success' => false]);
}
?>
