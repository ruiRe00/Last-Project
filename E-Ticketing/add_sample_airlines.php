<?php
require_once 'config/database.php';

// Check if airlines exist
$check_airlines = $pdo->query("SELECT COUNT(*) FROM airlines")->fetchColumn();

if ($check_airlines == 0) {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // First create user accounts for airlines
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'airline')");
        
        // Sample airlines data
        $airlines = [
            ['username' => 'garuda', 'name' => 'Garuda Indonesia'],
            ['username' => 'lionair', 'name' => 'Lion Air']
        ];

        foreach ($airlines as $airline) {
            // Create user account
            $stmt->execute([$airline['username'], password_hash($airline['username'], PASSWORD_DEFAULT)]);
            $user_id = $pdo->lastInsertId();

            // Create airline record
            $stmt2 = $pdo->prepare("INSERT INTO airlines (user_id, airline_name) VALUES (?, ?)");
            $stmt2->execute([$user_id, $airline['name']]);
        }

        // Commit transaction
        $pdo->commit();
        echo "<div style='color: green; margin: 20px;'>Sample airlines have been added successfully!</div>";
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        echo "<div style='color: red; margin: 20px;'>Error: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div style='color: blue; margin: 20px;'>Airlines already exist in the database.</div>";
}

// Display all airlines
echo "<h2 style='margin: 20px;'>Current Airlines</h2>";
$airlines = $pdo->query("SELECT a.*, u.username 
                        FROM airlines a 
                        JOIN users u ON a.user_id = u.id 
                        ORDER BY a.airline_name")->fetchAll();

if (count($airlines) > 0) {
    echo "<table style='width: 95%; margin: 20px; border-collapse: collapse;'>";
    echo "<tr style='background: #333; color: white;'>
            <th style='padding: 10px; text-align: left;'>Airline Name</th>
            <th style='padding: 10px; text-align: left;'>Username</th>
          </tr>";
    
    foreach ($airlines as $airline) {
        echo "<tr style='border-bottom: 1px solid #ddd;'>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($airline['airline_name']) . "</td>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($airline['username']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: orange; margin: 20px;'>No airlines found.</div>";
}
?> 