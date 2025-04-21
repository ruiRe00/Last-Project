<?php
require_once 'config/database.php';

// Add sample flights if none exist
$check_flights = $pdo->query("SELECT COUNT(*) FROM flights")->fetchColumn();

if ($check_flights == 0) {
    // First, get airline IDs
    $airlines = $pdo->query("SELECT id, airline_name FROM airlines")->fetchAll();
    
    if (count($airlines) > 0) {
        // Sample flight data
        $sample_flights = [
            // Flights for first airline
            [
                'flight_number' => 'RF101',
                'departure_city' => 'Jakarta',
                'arrival_city' => 'Surabaya',
                'departure_time' => date('Y-m-d H:i:s', strtotime('+1 day 08:00')),
                'arrival_time' => date('Y-m-d H:i:s', strtotime('+1 day 09:30')),
                'price' => 800000,
                'available_seats' => 100,
                'status' => 'active'
            ],
            [
                'flight_number' => 'RF102',
                'departure_city' => 'Surabaya',
                'arrival_city' => 'Bali',
                'departure_time' => date('Y-m-d H:i:s', strtotime('+1 day 10:00')),
                'arrival_time' => date('Y-m-d H:i:s', strtotime('+1 day 11:30')),
                'price' => 1000000,
                'available_seats' => 80,
                'status' => 'active'
            ],
            // Flights for second airline
            [
                'flight_number' => 'RF201',
                'departure_city' => 'Jakarta',
                'arrival_city' => 'Medan',
                'departure_time' => date('Y-m-d H:i:s', strtotime('+2 days 09:00')),
                'arrival_time' => date('Y-m-d H:i:s', strtotime('+2 days 11:30')),
                'price' => 1500000,
                'available_seats' => 120,
                'status' => 'active'
            ],
            [
                'flight_number' => 'RF202',
                'departure_city' => 'Medan',
                'arrival_city' => 'Jakarta',
                'departure_time' => date('Y-m-d H:i:s', strtotime('+2 days 14:00')),
                'arrival_time' => date('Y-m-d H:i:s', strtotime('+2 days 16:30')),
                'price' => 1500000,
                'available_seats' => 120,
                'status' => 'active'
            ]
        ];

        // Insert sample flights
        $stmt = $pdo->prepare("INSERT INTO flights (airline_id, flight_number, departure_city, arrival_city, 
                              departure_time, arrival_time, price, available_seats, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($airlines as $index => $airline) {
            // Add 2 flights per airline
            $start = $index * 2;
            for ($i = 0; $i < 2; $i++) {
                if (isset($sample_flights[$start + $i])) {
                    $flight = $sample_flights[$start + $i];
                    $stmt->execute([
                        $airline['id'],
                        $flight['flight_number'],
                        $flight['departure_city'],
                        $flight['arrival_city'],
                        $flight['departure_time'],
                        $flight['arrival_time'],
                        $flight['price'],
                        $flight['available_seats'],
                        $flight['status']
                    ]);
                }
            }
        }
        echo "Sample flights have been added successfully!";
    } else {
        echo "Please add airlines first!";
    }
} else {
    echo "Flights already exist in the database.";
}

// Display all flights
$flights = $pdo->query("SELECT f.*, a.airline_name 
                       FROM flights f 
                       JOIN airlines a ON f.airline_id = a.id 
                       WHERE f.status = 'active' 
                       AND f.departure_time > NOW() 
                       ORDER BY f.departure_time")->fetchAll();

echo "<pre>";
print_r($flights);
echo "</pre>";
?> 