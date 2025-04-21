<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing transaction ID']);
    exit();
}

// Get transaction details
try {
    $stmt = $pdo->prepare("SELECT b.*, f.flight_number, f.price, f.departure_city, 
                          f.arrival_city, f.departure_time, a.airline_name 
                          FROM bookings b 
                          JOIN flights f ON b.flight_id = f.id 
                          JOIN airlines a ON f.airline_id = a.id 
                          WHERE b.id = ? AND b.user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        exit();
    }

    // Format the data
    $status_class = [
        'pending' => 'warning',
        'admin_approved' => 'info',
        'airline_approved' => 'primary',
        'completed' => 'success',
        'rejected' => 'danger'
    ][$transaction['status']];

    $response = [
        'booking_number' => $transaction['booking_number'],
        'flight_number' => htmlspecialchars($transaction['flight_number']),
        'departure_city' => htmlspecialchars($transaction['departure_city']),
        'arrival_city' => htmlspecialchars($transaction['arrival_city']),
        'departure_time' => date('M d, Y H:i', strtotime($transaction['departure_time'])),
        'airline_name' => htmlspecialchars($transaction['airline_name']),
        'price' => $transaction['price'],
        'status' => ucfirst($transaction['status']),
        'status_class' => $status_class,
        'created_at' => date('M d, Y H:i', strtotime($transaction['created_at']))
    ];

    echo json_encode($response);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit();
}
