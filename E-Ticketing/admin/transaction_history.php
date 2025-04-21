<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Get transaction statistics
$stats = [
    'total_transactions' => 0,
    'total_revenue' => 0,
    'pending_transactions' => 0,
    'completed_transactions' => 0,
    'rejected_transactions' => 0
];

// Calculate statistics
$stmt = $pdo->query("SELECT 
                        COUNT(*) as total_transactions,
                        SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_transactions,
                        SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as completed_transactions,
                        SUM(CASE WHEN b.status = 'approved' THEN f.price ELSE 0 END) as total_revenue,
                        SUM(CASE WHEN b.status = 'rejected' THEN 1 ELSE 0 END) as rejected_transactions
                     FROM bookings b
                     JOIN flights f ON b.flight_id = f.id");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$stats['total_transactions'] = $result['total_transactions'] ?? 0;
$stats['pending_transactions'] = $result['pending_transactions'] ?? 0;
$stats['completed_transactions'] = $result['completed_transactions'] ?? 0;
$stats['total_revenue'] = $result['total_revenue'] ?? 0;
$stats['rejected_transactions'] = $result['rejected_transactions'] ?? 0;

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Transaction History</h1>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Transactions</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['total_transactions']); ?></h2>
                    <div class="text-success mt-2">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Revenue</h5>
                    <h2 class="mb-0">Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></h2>
                    <div class="text-success mt-2">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-muted">Pending Transactions</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['pending_transactions']); ?></h2>
                    <div class="text-warning mt-2">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-muted">Completed Transactions</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['completed_transactions']); ?></h2>
                    <div class="text-info mt-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">All Transactions</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>User</th>
                            <th>Flight Details</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
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
                                           ORDER BY b.created_at DESC");
                        
                        while ($transaction = $stmt->fetch()) {
                            $status_class = match($transaction['status']) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'pending' => 'success',
                                default => 'warning'
                            };
                            $status_text = match($transaction['status']) {
                                'approved' => 'Complete',
                                'rejected' => 'Rejected',
                                'pending' => 'Complete',
                                default => 'Pending'
                            };
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['booking_number']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($transaction['airline_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($transaction['flight_number']); ?></small><br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($transaction['departure_city']); ?> → 
                                        <?php echo htmlspecialchars($transaction['arrival_city']); ?>
                                    </small>
                                </td>
                                <td>Rp <?php echo number_format($transaction['price'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y H:i', strtotime($transaction['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="viewBookingDetails('<?php echo htmlspecialchars($transaction['booking_number']); ?>', 
                                                     '<?php echo htmlspecialchars($transaction['username']); ?>', 
                                                     '<?php echo htmlspecialchars($transaction['flight_number']); ?>', 
                                                     '<?php echo htmlspecialchars($transaction['airline_name']); ?>', 
                                                     '<?php echo htmlspecialchars($transaction['departure_city']); ?> → <?php echo htmlspecialchars($transaction['arrival_city']); ?>', 
                                                     '<?php echo date('d M Y H:i', strtotime($transaction['departure_time'])); ?>', 
                                                     '<?php echo number_format($transaction['price'], 0, ',', '.'); ?>', 
                                                     '<?php echo $status_text; ?>', 
                                                     '<?php echo htmlspecialchars($transaction['payment_proof'] ?? ''); ?>', 
                                                     '<?php echo date('d M Y H:i', strtotime($transaction['created_at'])); ?>')">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1">
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
function viewBookingDetails(bookingNumber, username, flightNumber, airline, route, schedule, amount, status, proof, bookingDate) {
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
                                         status === 'Complete' ? 'success' : 'danger');
    
    if (proof) {
        document.getElementById('modalPaymentProof').src = '../uploads/payment_proofs/' + proof;
    }
    
    new bootstrap.Modal(document.getElementById('bookingDetailsModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
