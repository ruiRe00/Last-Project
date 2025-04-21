<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing transaction ID']);
    exit();
}

try {
    // Get detailed transaction information
    $stmt = $pdo->prepare("SELECT 
        b.id,
        b.booking_number,
        b.status,
        b.seats_booked,
        b.payment_proof,
        b.notes,
        b.created_at,
        u.username,
        u.full_name,
        u.email,
        u.phone,
        f.flight_number,
        f.price,
        f.departure_city,
        f.arrival_city,
        f.departure_time,
        f.arrival_time,
        a.airline_name,
        a.logo as airline_logo,
        (f.price * b.seats_booked) as total_price
    FROM bookings b 
    JOIN users u ON b.user_id = u.id
    JOIN flights f ON b.flight_id = f.id 
    JOIN airlines a ON f.airline_id = a.id 
    WHERE b.id = ?");
    
    $stmt->execute([$_GET['id']]);
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
        'username' => htmlspecialchars($transaction['username']),
        'full_name' => htmlspecialchars($transaction['full_name']),
        'email' => htmlspecialchars($transaction['email']),
        'phone' => htmlspecialchars($transaction['phone']),
        'flight_number' => htmlspecialchars($transaction['flight_number']),
        'departure_city' => htmlspecialchars($transaction['departure_city']),
        'arrival_city' => htmlspecialchars($transaction['arrival_city']),
        'departure_time' => date('M d, Y H:i', strtotime($transaction['departure_time'])),
        'arrival_time' => date('M d, Y H:i', strtotime($transaction['arrival_time'])),
        'airline_name' => htmlspecialchars($transaction['airline_name']),
        'price' => number_format($transaction['price'], 0, ',', '.'),
        'seats_booked' => $transaction['seats_booked'],
        'total_amount' => number_format($transaction['price'] * $transaction['seats_booked'], 0, ',', '.'),
        'status' => ucfirst($transaction['status']),
        'status_class' => $status_class,
        'created_at' => date('M d, Y H:i', strtotime($transaction['created_at'])),
        'payment_proof' => $transaction['payment_proof'],
        'notes' => htmlspecialchars($transaction['notes'])
    ];

    echo json_encode($response);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit();
}
