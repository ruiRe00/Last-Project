<?php
session_start();
require_once '../config/database.php';

// Check if user is airline
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'airline') {
    header('Location: ../auth/login.php');
    exit();
}

// Get airline ID
$stmt = $pdo->prepare("SELECT id FROM airlines WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$airline = $stmt->fetch();
$airline_id = $airline['id'];

// Get statistics
$stats = [
    'total_tickets_sold' => 0,
    'total_customers' => 0,
    'active_tickets' => 0,
    'total_revenue' => 0
];

// Get total tickets sold
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings b 
                       JOIN flights f ON b.flight_id = f.id 
                       WHERE f.airline_id = ? AND b.status = 'completed'");
$stmt->execute([$airline_id]);
$stats['total_tickets_sold'] = $stmt->fetchColumn();

// Get total unique customers
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT b.user_id) FROM bookings b 
                       JOIN flights f ON b.flight_id = f.id 
                       WHERE f.airline_id = ? AND b.status = 'completed'");
$stmt->execute([$airline_id]);
$stats['total_customers'] = $stmt->fetchColumn();

// Get active tickets
$stmt = $pdo->prepare("SELECT COUNT(*) FROM flights 
                       WHERE airline_id = ? AND status = 'active'");
$stmt->execute([$airline_id]);
$stats['active_tickets'] = $stmt->fetchColumn();

// Get total revenue
$stmt = $pdo->prepare("SELECT SUM(f.price) FROM bookings b 
                       JOIN flights f ON b.flight_id = f.id 
                       WHERE f.airline_id = ? AND b.status = 'completed'");
$stmt->execute([$airline_id]);
$stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

// Get all flights for this airline
$stmt = $pdo->prepare("SELECT * FROM flights WHERE airline_id = ? ORDER BY departure_time");
$stmt->execute([$airline_id]);
$flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Airline Dashboard</h1>
    
    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <h5 class="card-title text-muted">Tickets Sold</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['total_tickets_sold']); ?></h2>
                    <div class="text-success mt-2">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Customers</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['total_customers']); ?></h2>
                    <div class="text-primary mt-2">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <h5 class="card-title text-muted">Active Tickets</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['active_tickets']); ?></h2>
                    <div class="text-info mt-2">
                        <i class="fas fa-plane"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Revenue</h5>
                    <h2 class="mb-0">Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></h2>
                    <div class="text-success mt-2">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flight Schedules -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Flight Schedules</h5>
            <a href="manage_flights.php" class="btn btn-primary btn-sm">
                <i class="fas fa-cog"></i> Manage Flights
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Flight Number</th>
                            <th>Route</th>
                            <th>Schedule</th>
                            <th>Price</th>
                            <th>Available Seats</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($flights) > 0): ?>
                            <?php foreach ($flights as $flight): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-plane-departure me-2"></i>
                                        <?php echo htmlspecialchars($flight['flight_number']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($flight['departure_city']); ?> 
                                        <i class="fas fa-arrow-right mx-2"></i>
                                        <?php echo htmlspecialchars($flight['arrival_city']); ?>
                                    </td>
                                    <td>
                                        <div><?php echo date('M d, Y H:i', strtotime($flight['departure_time'])); ?></div>
                                        <small class="text-muted">to</small>
                                        <div><?php echo date('M d, Y H:i', strtotime($flight['arrival_time'])); ?></div>
                                    </td>
                                    <td>Rp <?php echo number_format($flight['price'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $flight['available_seats'] > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $flight['available_seats']; ?> seats
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $flight['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($flight['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No flights scheduled yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
