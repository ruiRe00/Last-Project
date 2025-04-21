<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has role 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

// Get user statistics
$user_id = $_SESSION['user_id'];

// Get booked tickets count
$booked_query = "SELECT COUNT(*) as count FROM bookings WHERE user_id = ?";
$stmt = $pdo->prepare($booked_query);
$stmt->execute([$user_id]);
$booked_tickets = $stmt->fetch()['count'];

// Get active tickets count
$active_query = "SELECT COUNT(*) as count FROM bookings b 
                INNER JOIN flights f ON b.flight_id = f.id 
                WHERE b.user_id = ? AND f.departure_time > NOW()";
$stmt = $pdo->prepare($active_query);
$stmt->execute([$user_id]);
$active_tickets = $stmt->fetch()['count'];

// Get total spent
$spent_query = "SELECT COALESCE(SUM(f.price), 0) as total FROM bookings b 
               INNER JOIN flights f ON b.flight_id = f.id 
               WHERE b.user_id = ? AND b.status = 'confirmed'";
$stmt = $pdo->prepare($spent_query);
$stmt->execute([$user_id]);
$total_spent = $stmt->fetch()['total'];

// Get all airlines that have flights
$airlines_query = "SELECT DISTINCT a.id, a.airline_name 
                  FROM airlines a 
                  INNER JOIN flights f ON a.id = f.airline_id 
                  WHERE f.status = 'active'
                  ORDER BY a.airline_name";
$airlines = $pdo->query($airlines_query)->fetchAll();

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">My Dashboard</h2>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h5 class="card-title">Booked Tickets</h5>
                    <p class="card-text display-4"><?php echo $booked_tickets; ?></p>
                    <i class="fas fa-ticket-alt position-absolute top-50 end-0 me-3 opacity-25 fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Tickets</h5>
                    <p class="card-text display-4"><?php echo $active_tickets; ?></p>
                    <i class="fas fa-plane position-absolute top-50 end-0 me-3 opacity-25 fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Spent</h5>
                    <p class="card-text display-4">Rp <?php echo number_format($total_spent, 0, ',', '.'); ?></p>
                    <i class="fas fa-money-bill-wave position-absolute top-50 end-0 me-3 opacity-25 fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Flights -->
    <div class="card bg-dark text-white mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-plane-departure me-2"></i>
                Available Flights
            </h5>
        </div>
        <div class="card-body">
            <?php if (count($airlines) > 0): ?>
                <?php foreach ($airlines as $airline): ?>
                    <?php
                    // Get flights for this airline
                    $flights_query = "SELECT f.* 
                                    FROM flights f 
                                    WHERE f.airline_id = ? 
                                    AND f.status = 'active'
                                    ORDER BY f.departure_time ASC";
                    $flights_stmt = $pdo->prepare($flights_query);
                    $flights_stmt->execute([$airline['id']]);
                    $flights = $flights_stmt->fetchAll();
                    
                    if (count($flights) > 0):
                    ?>
                        <h6 class="border-bottom border-secondary pb-2 mb-3">
                            <?php echo htmlspecialchars($airline['airline_name']); ?>
                        </h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-dark table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Flight</th>
                                        <th>Route</th>
                                        <th>Schedule</th>
                                        <th>Price</th>
                                        <th>Seats</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($flights as $flight): 
                                        $now = new DateTime();
                                        $departure = new DateTime($flight['departure_time']);
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        if ($departure < $now) {
                                            $status_class = 'secondary';
                                            $status_text = 'Expired';
                                        } else if ($flight['available_seats'] <= 0) {
                                            $status_class = 'warning';
                                            $status_text = 'Sold Out';
                                        } else {
                                            $status_class = 'success';
                                            $status_text = 'Available';
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-plane me-2"></i>
                                                <?php echo htmlspecialchars($flight['flight_number']); ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span><?php echo htmlspecialchars($flight['departure_city']); ?></span>
                                                    <i class="fas fa-arrow-right mx-2"></i>
                                                    <span><?php echo htmlspecialchars($flight['arrival_city']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="far fa-calendar-alt me-2"></i>
                                                    <?php echo date('d M Y H:i', strtotime($flight['departure_time'])); ?>
                                                </div>
                                                <small class="text-muted">to</small>
                                                <div>
                                                    <i class="far fa-calendar-alt me-2"></i>
                                                    <?php echo date('d M Y H:i', strtotime($flight['arrival_time'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="fas fa-tag me-2"></i>
                                                Rp <?php echo number_format($flight['price'], 0, ',', '.'); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $flight['available_seats'] > 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo $flight['available_seats']; ?> Seats
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($departure > $now && $flight['available_seats'] > 0): ?>
                                                <form action="book_flight.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="flight_id" value="<?php echo $flight['id']; ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-ticket-alt me-1"></i> Book Now
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    <i class="fas fa-ban me-1"></i> Not Available
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No flights available at the moment.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
