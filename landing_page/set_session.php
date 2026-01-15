<?php
// set_session.php
session_start();

if (isset($_GET['terms_accepted'])) {
    $_SESSION['terms_accepted'] = true;
    echo json_encode(['success' => true]);
    exit();
}

if (isset($_GET['hotel_guest_selected'])) {
    $_SESSION['hotel_guest_selected'] = $_GET['hotel_guest_selected'];
    echo json_encode(['success' => true]);
    exit();
}

echo json_encode(['success' => false]);
?>