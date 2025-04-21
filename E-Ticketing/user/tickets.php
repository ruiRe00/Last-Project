<?php
session_start();
require_once '../config/database.php';
include '../includes/header.php';

// Check if user is logged in and has role 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

// Get all available airlines
$airlines_query = "SELECT DISTINCT a.airline_name, a.id as airline_id 
                  FROM airlines a 
                  INNER JOIN users u ON a.user_id = u.id 
                  WHERE u.role = 'airline'
                  ORDER BY a.airline_name";
$airlines_stmt = $pdo->query($airlines_query);
$airlines = $airlines_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get flights based on selected airline
$selected_airline = isset($_GET['airline_id']) ? $_GET['airline_id'] : null;
$flights = [];

if ($selected_airline) {
    $flights_query = "SELECT f.*, a.airline_name 
                     FROM flights f 
                     INNER JOIN airlines a ON f.airline_id = a.id 
                     WHERE f.airline_id = ? AND f.departure_time > NOW()
                     ORDER BY f.departure_time";
    $flights_stmt = $pdo->prepare($flights_query);
    $flights_stmt->execute([$selected_airline]);
    $flights = $flights_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container mt-4">
    <div class="row">
        <!-- Airlines List -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Available Airlines</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($airlines as $airline): ?>
                            <a href="?airline_id=<?php echo $airline['airline_id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo ($selected_airline == $airline['airline_id']) ? 'active' : ''; ?>">
                                <i class="fas fa-plane-departure me-2"></i>
                                <?php echo htmlspecialchars($airline['airline_name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flights List -->
        <div class="col-md-9">
            <?php if ($selected_airline): ?>
                <?php if (count($flights) > 0): ?>
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach ($flights as $flight): ?>
                            <div class="col">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <?php echo htmlspecialchars($flight['origin']); ?> 
                                            <i class="fas fa-arrow-right mx-2"></i> 
                                            <?php echo htmlspecialchars($flight['destination']); ?>
                                        </h5>
                                        <div class="card-text">
                                            <div class="mb-2">
                                                <i class="fas fa-calendar me-2"></i>
                                                <?php echo date('d M Y', strtotime($flight['departure_time'])); ?>
                                            </div>
                                            <div class="mb-2">
                                                <i class="fas fa-clock me-2"></i>
                                                <?php echo date('H:i', strtotime($flight['departure_time'])); ?>
                                            </div>
                                            <div class="mb-2">
                                                <i class="fas fa-tag me-2"></i>
                                                Price: Rp <?php echo number_format($flight['price'], 0, ',', '.'); ?>
                                            </div>
                                            <div class="mb-2">
                                                <i class="fas fa-chair me-2"></i>
                                                Available Seats: <?php echo $flight['available_seats']; ?>
                                            </div>
                                        </div>
                                        <?php if ($flight['available_seats'] > 0): ?>
                                            <form action="book_flight.php" method="POST" class="mt-3">
                                                <input type="hidden" name="flight_id" value="<?php echo $flight['id']; ?>">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-ticket-alt me-2"></i>
                                                    Book Now
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100" disabled>
                                                <i class="fas fa-ban me-2"></i>
                                                Sold Out
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No flights available for this airline at the moment.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please select an airline to view available flights.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
