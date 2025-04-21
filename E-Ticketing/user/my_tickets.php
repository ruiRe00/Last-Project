<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle payment proof upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id']) && isset($_FILES['payment_proof'])) {
    $booking_id = $_POST['booking_id'];
    $file = $_FILES['payment_proof'];
    
    if ($file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'payment_' . $booking_id . '_' . time() . '.' . $ext;
        $target_path = '../uploads/payment_proofs/' . $filename;
        
        // Create directory if it doesn't exist
        if (!file_exists('../uploads/payment_proofs')) {
            mkdir('../uploads/payment_proofs', 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $stmt = $pdo->prepare("UPDATE bookings SET payment_proof = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$filename, $booking_id, $_SESSION['user_id']]);
            
            header('Location: my_tickets.php');
            exit();
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="h3 mb-4">My Tickets</h1>

    <!-- Pending Tickets -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Pending Tickets</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Flight Details</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT b.*, f.flight_number, f.departure_city, f.arrival_city, 
                                             f.departure_time, f.price, a.airline_name 
                                             FROM bookings b 
                                             JOIN flights f ON b.flight_id = f.id 
                                             JOIN airlines a ON f.airline_id = a.id 
                                             WHERE b.user_id = ? AND b.status != 'completed' AND b.status != 'rejected' 
                                             ORDER BY b.created_at DESC");
                        $stmt->execute([$_SESSION['user_id']]);
                        while ($booking = $stmt->fetch()) {
                            $status_class = match($booking['status']) {
                                'pending' => 'success',
                                'admin_approved' => 'info',
                                'airline_approved' => 'primary',
                                'completed' => 'success',
                                'rejected' => 'danger',
                                default => 'warning'
                            };
                            
                            $status_text = match($booking['status']) {
                                'pending' => 'Complete',
                                'admin_approved' => 'Admin Approved',
                                'airline_approved' => 'Airline Approved',
                                'completed' => 'Complete',
                                'rejected' => 'Rejected',
                                default => 'Pending'
                            };
                            ?>
                            <tr>
                                <td><?php echo $booking['booking_number']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['flight_number']); ?></strong>
                                    <div><?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($booking['departure_time'])); ?></small>
                                    <div class="text-muted"><?php echo htmlspecialchars($booking['airline_name']); ?></div>
                                </td>
                                <td>Rp <?php echo number_format($booking['price'], 0, ',', '.'); ?></td>
                                <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td>
                                    <?php if ($booking['payment_proof']): ?>
                                        <button class="btn btn-sm btn-info" onclick="viewPaymentProof('<?php echo htmlspecialchars($booking['payment_proof']); ?>')">
                                            View Proof
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-primary" onclick="uploadPayment('<?php echo $booking['id']; ?>')">
                                            Upload Payment
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Completed Tickets -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Completed Tickets</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Flight Details</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT b.*, f.flight_number, f.departure_city, f.arrival_city, 
                                             f.departure_time, f.price, a.airline_name 
                                             FROM bookings b 
                                             JOIN flights f ON b.flight_id = f.id 
                                             JOIN airlines a ON f.airline_id = a.id 
                                             WHERE b.user_id = ? AND (b.status = 'completed' OR b.status = 'rejected') 
                                             ORDER BY b.created_at DESC");
                        $stmt->execute([$_SESSION['user_id']]);
                        while ($booking = $stmt->fetch()) {
                            $status_class = match($booking['status']) {
                                'pending' => 'success',
                                'admin_approved' => 'info',
                                'airline_approved' => 'primary',
                                'completed' => 'success',
                                'rejected' => 'danger',
                                default => 'warning'
                            };
                            
                            $status_text = match($booking['status']) {
                                'pending' => 'Complete',
                                'admin_approved' => 'Admin Approved',
                                'airline_approved' => 'Airline Approved',
                                'completed' => 'Complete',
                                'rejected' => 'Rejected',
                                default => 'Pending'
                            };
                            ?>
                            <tr>
                                <td><?php echo $booking['booking_number']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['flight_number']); ?></strong>
                                    <div><?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($booking['departure_time'])); ?></small>
                                    <div class="text-muted"><?php echo htmlspecialchars($booking['airline_name']); ?></div>
                                </td>
                                <td>Rp <?php echo number_format($booking['price'], 0, ',', '.'); ?></td>
                                <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td>
                                    <?php if ($booking['status'] === 'completed'): ?>
                                        <button class="btn btn-sm btn-success" onclick="viewTicket('<?php echo $booking['id']; ?>', 
                                            '<?php echo htmlspecialchars($booking['booking_number']); ?>', 
                                            '<?php echo htmlspecialchars($booking['flight_number']); ?>', 
                                            '<?php echo htmlspecialchars($booking['departure_city']); ?>', 
                                            '<?php echo htmlspecialchars($booking['arrival_city']); ?>', 
                                            '<?php echo date('M d, Y H:i', strtotime($booking['departure_time'])); ?>', 
                                            '<?php echo htmlspecialchars($booking['airline_name']); ?>', 
                                            '<?php echo number_format($booking['price'], 0, ',', '.'); ?>')">
                                            <i class="fas fa-eye"></i> View Ticket
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Payment Upload Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Payment Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="booking_id" id="bookingId">
                    <div class="mb-3">
                        <label class="form-label">Payment Proof (Image)</label>
                        <input type="file" class="form-control" name="payment_proof" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Proof Modal -->
<div class="modal fade" id="proofModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="proofImage" src="" alt="Payment Proof" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Ticket View Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">E-Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="ticket-container p-4" style="border: 2px dashed #1e3c72; border-radius: 15px;">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="ticket-title mb-4">Flight Ticket</h4>
                            <div class="ticket-details">
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Booking Number:</strong></div>
                                    <div class="col-sm-8" id="ticketBookingNumber"></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Flight Number:</strong></div>
                                    <div class="col-sm-8" id="ticketFlightNumber"></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Route:</strong></div>
                                    <div class="col-sm-8">
                                        <span id="ticketDepartureCity"></span> → 
                                        <span id="ticketArrivalCity"></span>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Departure:</strong></div>
                                    <div class="col-sm-8" id="ticketDepartureTime"></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Airline:</strong></div>
                                    <div class="col-sm-8" id="ticketAirline"></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Price:</strong></div>
                                    <div class="col-sm-8">Rp <span id="ticketPrice"></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="qr-placeholder mt-4" style="background: #f8f9fa; height: 150px; width: 150px; margin: auto; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                                <i class="fas fa-qrcode fa-5x text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>
    </div>
</div>

<script>
function uploadPayment(bookingId) {
    document.getElementById('bookingId').value = bookingId;
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function viewPaymentProof(filename) {
    document.getElementById('proofImage').src = '../uploads/payment_proofs/' + filename;
    new bootstrap.Modal(document.getElementById('proofModal')).show();
}

function viewTicket(bookingId, bookingNumber, flightNumber, departureCity, arrivalCity, departureTime, airline, price) {
    // Populate ticket modal with data
    document.getElementById('ticketBookingNumber').textContent = bookingNumber;
    document.getElementById('ticketFlightNumber').textContent = flightNumber;
    document.getElementById('ticketDepartureCity').textContent = departureCity;
    document.getElementById('ticketArrivalCity').textContent = arrivalCity;
    document.getElementById('ticketDepartureTime').textContent = departureTime;
    document.getElementById('ticketAirline').textContent = airline;
    document.getElementById('ticketPrice').textContent = price;

    // Show the modal
    new bootstrap.Modal(document.getElementById('ticketModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
