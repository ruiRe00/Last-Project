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
                'departure_time' => date('Y-m-d H:i:s', strtotime('+7 days 08:00')),
                'arrival_time' => date('Y-m-d H:i:s', strtotime('+7 days 09:30')),
                'price' => 800000,
                'available_seats' => 100,
                'status' => 'active'
            ],
            [
                'flight_number' => 'RF102',
                'departure_city' => 'Surabaya',
                'arrival_city' => 'Bali',
                'departure_time' => date('Y-m-d H:i:s', strtotime('+8 days 10:00')),
                'arrival_time' => date('Y-m-d H:i:s', strtotime('+8 days 11:30')),
                'price' => 1000000,
                'available_seats' => 80,
                'status' => 'active'
            ],
            // Flights for second airline
            [
                'flight_number' => 'RF201',
                'departure_city' => 'Jakarta',
                'arrival_city' => 'Medan',
                'departure_time' => date('Y-m-d H:i:s', strtotime('+9 days 09:00')),
                'arrival_time' => date('Y-m-d H:i:s', strtotime('+9 days 11:30')),
                'price' => 1500000,
                'available_seats' => 120,
                'status' => 'active'
            ],
            [
                'flight_number' => 'RF202',
                'departure_city' => 'Medan',
                'arrival_city' => 'Jakarta',
                'departure_time' => date('Y-m-d H:i:s', strtotime('+10 days 14:00')),
                'arrival_time' => date('Y-m-d H:i:s', strtotime('+10 days 16:30')),
                'price' => 1500000,
                'available_seats' => 120,
                'status' => 'active'
            ]
        ];

        try {
            // Start transaction
            $pdo->beginTransaction();

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

            // Commit transaction
            $pdo->commit();
            echo "<div style='color: green; margin: 20px;'>Sample flights have been added successfully!</div>";
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            echo "<div style='color: red; margin: 20px;'>Error: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div style='color: orange; margin: 20px;'>Please add airlines first!</div>";
    }
} else {
    echo "<div style='color: blue; margin: 20px;'>Flights already exist in the database.</div>";
}

// Display all active flights
echo "<h2 style='margin: 20px;'>Current Active Flights</h2>";
$flights = $pdo->query("SELECT f.*, a.airline_name 
                       FROM flights f 
                       JOIN airlines a ON f.airline_id = a.id 
                       WHERE f.status = 'active' 
                       AND f.departure_time > NOW() 
                       ORDER BY airline_name, departure_time")->fetchAll();

if (count($flights) > 0) {
    echo "<table style='width: 95%; margin: 20px; border-collapse: collapse;'>";
    echo "<tr style='background: #333; color: white;'>
            <th style='padding: 10px; text-align: left;'>Airline</th>
            <th style='padding: 10px; text-align: left;'>Flight Number</th>
            <th style='padding: 10px; text-align: left;'>Route</th>
            <th style='padding: 10px; text-align: left;'>Schedule</th>
            <th style='padding: 10px; text-align: right;'>Price</th>
            <th style='padding: 10px; text-align: center;'>Available Seats</th>
          </tr>";
    
    foreach ($flights as $flight) {
        echo "<tr style='border-bottom: 1px solid #ddd;'>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($flight['airline_name']) . "</td>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($flight['flight_number']) . "</td>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($flight['departure_city']) . " → " . htmlspecialchars($flight['arrival_city']) . "</td>";
        echo "<td style='padding: 10px;'>" . date('d M Y H:i', strtotime($flight['departure_time'])) . "<br><small>to " . date('d M Y H:i', strtotime($flight['arrival_time'])) . "</small></td>";
        echo "<td style='padding: 10px; text-align: right;'>Rp " . number_format($flight['price'], 0, ',', '.') . "</td>";
        echo "<td style='padding: 10px; text-align: center;'>" . $flight['available_seats'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: orange; margin: 20px;'>No active flights found.</div>";
}
?> 