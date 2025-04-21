<?php
session_start();
require_once '../config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if user is airline
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'airline') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing booking number']);
    exit();
}

try {
    // Get airline ID
    $stmt = $pdo->prepare("SELECT id FROM airlines WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $airline = $stmt->fetch();
    
    if (!$airline) {
        error_log("Airline not found for user_id: " . $_SESSION['user_id']);
        http_response_code(404);
        echo json_encode(['error' => 'Airline not found']);
        exit();
    }

    $airline_id = $airline['id'];
    $booking_number = $_GET['id'];

    error_log("Fetching details for booking_number: " . $booking_number . " and airline_id: " . $airline_id);

    // Get detailed transaction information
    $query = "SELECT 
        b.*,
        u.username,
        f.flight_number,
        f.price,
        f.departure_city,
        f.arrival_city,
        f.departure_time,
        f.arrival_time,
        a.airline_name
    FROM bookings b 
    JOIN users u ON b.user_id = u.id
    JOIN flights f ON b.flight_id = f.id 
    JOIN airlines a ON f.airline_id = a.id
    WHERE b.booking_number = :booking_number 
    AND f.airline_id = :airline_id";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':booking_number', $booking_number);
    $stmt->bindParam(':airline_id', $airline_id);
    $stmt->execute();
    
    error_log("Query executed. Checking for results...");
    
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        error_log("No transaction found for booking_number: " . $booking_number);
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        exit();
    }

    error_log("Transaction found. Processing status...");

    // Format the data
    $status_class = 'secondary'; // default status
    $status_text = ucfirst($transaction['status'] ?? 'Unknown');

    switch($transaction['status']) {
        case 'pending':
            $status_class = 'warning';
            $status_text = 'Pending';
            break;
        case 'approved':
            $status_class = 'success';
            $status_text = 'Complete';
            break;
        case 'rejected':
            $status_class = 'danger';
            $status_text = 'Rejected';
            break;
    }

    $response = [
        'booking_number' => $transaction['booking_number'],
        'username' => htmlspecialchars($transaction['username']),
        'flight_number' => htmlspecialchars($transaction['flight_number']),
        'departure_city' => htmlspecialchars($transaction['departure_city']),
        'arrival_city' => htmlspecialchars($transaction['arrival_city']),
        'departure_time' => date('M d, Y H:i', strtotime($transaction['departure_time'])),
        'arrival_time' => date('M d, Y H:i', strtotime($transaction['arrival_time'])),
        'price' => number_format($transaction['price'], 0, ',', '.'),
        'status' => $status_text,
        'status_class' => $status_class,
        'created_at' => date('M d, Y H:i', strtotime($transaction['created_at'])),
        'payment_proof' => htmlspecialchars($transaction['payment_proof'] ?? ''),
        'notes' => htmlspecialchars($transaction['notes'] ?? '')
    ];

    error_log("Sending response for booking_number: " . $booking_number);
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database Error in get_transaction_details.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred. Please try again later.',
        'debug' => $e->getMessage() // Only in development
    ]);
    exit();
} catch (Exception $e) {
    error_log("General Error in get_transaction_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred. Please try again later.']);
    exit();
}
