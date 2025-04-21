<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    try {
        $booking_id = $_POST['booking_id'];
        $action = $_POST['action'];
        
        if ($action === 'complete' || $action === 'reject') {
            $status = $action === 'complete' ? 'approved' : 'rejected';
            
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->execute([$status, $booking_id]);
            
            $_SESSION['success'] = "Booking has been " . ($status === 'approved' ? 'approved' : 'rejected') . " successfully!";
            header('Location: dashboard.php');
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating booking: " . $e->getMessage();
    }
}

// Get statistics
$stats = [
    'active_tickets' => 0,
    'sold_tickets' => 0,
    'total_revenue' => 0,
    'total_users' => 0
];

// Calculate statistics
$stmt = $pdo->query("SELECT 
    SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as active_tickets,
    SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as sold_tickets,
    SUM(CASE WHEN b.status = 'approved' THEN f.price ELSE 0 END) as total_revenue
    FROM bookings b
    LEFT JOIN flights f ON b.flight_id = f.id");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$stats['active_tickets'] = $result['active_tickets'] ?? 0;
$stats['sold_tickets'] = $result['sold_tickets'] ?? 0;
$stats['total_revenue'] = $result['total_revenue'] ?? 0;

// Get total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$stats['total_users'] = $stmt->fetch()['count'] ?? 0;

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Admin Dashboard</h1>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h5 class="card-title text-muted">Active Tickets</h5>
                    <h2 class="display-6"><?php echo number_format($stats['active_tickets']); ?></h2>
                    <div class="text-warning mt-2">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h5 class="card-title text-muted">Sold Tickets</h5>
                    <h2 class="display-6"><?php echo number_format($stats['sold_tickets']); ?></h2>
                    <div class="text-success mt-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Revenue</h5>
                    <h2 class="display-6">Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></h2>
                    <div class="text-success mt-2">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Users</h5>
                    <h2 class="display-6"><?php echo number_format($stats['total_users']); ?></h2>
                    <div class="text-info mt-2">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="card bg-dark text-white">
        <div class="card-header">
            <h5 class="card-title mb-0">Recent Bookings</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>User</th>
                            <th>Flight</th>
                            <th>Route</th>
                            <th>Schedule</th>
                            <th>Payment Proof</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT b.*, u.username, f.flight_number, f.price,
                                           f.departure_city, f.arrival_city, f.departure_time,
                                           a.airline_name
                                           FROM bookings b 
                                           JOIN users u ON b.user_id = u.id 
                                           JOIN flights f ON b.flight_id = f.id 
                                           JOIN airlines a ON f.airline_id = a.id
                                           WHERE b.status = 'pending'
                                           ORDER BY b.created_at DESC
                                           LIMIT 10");
                        
                        while ($booking = $stmt->fetch()) {
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['booking_number']); ?></td>
                                <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['airline_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($booking['flight_number']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['arrival_city']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($booking['departure_time'])); ?></td>
                                <td>
                                    <?php if ($booking['payment_proof']): ?>
                                        <button type="button" class="btn btn-sm btn-info" 
                                                onclick="viewPaymentProof('<?php echo htmlspecialchars($booking['booking_number']); ?>', '<?php echo htmlspecialchars($booking['username']); ?>', '<?php echo htmlspecialchars($booking['flight_number']); ?>', '<?php echo htmlspecialchars($booking['airline_name']); ?>', '<?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['arrival_city']); ?>', '<?php echo date('d M Y H:i', strtotime($booking['departure_time'])); ?>', '<?php echo $booking['price']; ?>', '<?php echo ucfirst($booking['status']); ?>', '<?php echo htmlspecialchars($booking['payment_proof']); ?>', '<?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?>')">
                                            <i class="fas fa-receipt"></i> View
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-warning">No Proof</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="action" value="complete" 
                                                class="btn btn-sm btn-success" 
                                                onclick="return confirm('Are you sure you want to approve this booking?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" 
                                                class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Are you sure you want to reject this booking?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentProofModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card bg-dark border-secondary">
                            <div class="card-body">
                                <h6 class="card-title border-bottom border-secondary pb-2">
                                    <i class="fas fa-user me-2"></i>User Information
                                </h6>
                                <div class="mb-2">
                                    <small class="text-muted">Username</small>
                                    <p class="mb-2" id="modalUsername"></p>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Booking Number</small>
                                    <p class="mb-2" id="modalBookingNumber"></p>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Booking Date</small>
                                    <p class="mb-2" id="modalBookingDate"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-dark border-secondary">
                            <div class="card-body">
                                <h6 class="card-title border-bottom border-secondary pb-2">
                                    <i class="fas fa-plane me-2"></i>Flight Information
                                </h6>
                                <div class="mb-2">
                                    <small class="text-muted">Airline</small>
                                    <p class="mb-2" id="modalAirline"></p>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Flight Number</small>
                                    <p class="mb-2" id="modalFlightNumber"></p>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Route</small>
                                    <p class="mb-2" id="modalRoute"></p>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Schedule</small>
                                    <p class="mb-2" id="modalSchedule"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card bg-dark border-secondary">
                            <div class="card-body">
                                <h6 class="card-title border-bottom border-secondary pb-2">
                                    <i class="fas fa-receipt me-2"></i>Payment Information
                                </h6>
                                <div class="mb-2">
                                    <small class="text-muted">Amount</small>
                                    <p class="mb-2" id="modalAmount"></p>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Status</small>
                                    <p class="mb-2"><span id="modalStatus" class="badge"></span></p>
                                </div>
                                <div class="text-center mt-3">
                                    <img id="modalPaymentProof" src="" alt="Payment Proof" class="img-fluid rounded" style="max-height: 200px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewPaymentProof(bookingNumber, username, flightNumber, airline, route, schedule, amount, status, proof, bookingDate) {
    document.getElementById('modalUsername').textContent = username;
    document.getElementById('modalBookingNumber').textContent = bookingNumber;
    document.getElementById('modalBookingDate').textContent = bookingDate;
    document.getElementById('modalAirline').textContent = airline;
    document.getElementById('modalFlightNumber').textContent = flightNumber;
    document.getElementById('modalRoute').textContent = route;
    document.getElementById('modalSchedule').textContent = schedule;
    document.getElementById('modalAmount').textContent = 'Rp ' + amount;
    
    const statusBadge = document.getElementById('modalStatus');
    statusBadge.textContent = status;
    statusBadge.className = 'badge bg-' + (status === 'Pending' ? 'warning' : 
                                         status === 'Approved' ? 'success' : 'danger');
    
    if (proof) {
        document.getElementById('modalPaymentProof').src = '../uploads/payment_proofs/' + proof;
    }
    
    new bootstrap.Modal(document.getElementById('paymentProofModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>