<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'admin_approved' WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
    }
    $stmt->execute([$booking_id]);
    
    header('Location: confirm_payments.php');
    exit();
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Confirm Payments</h1>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>User</th>
                            <th>Flight</th>
                            <th>Amount</th>
                            <th>Payment Proof</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT b.*, u.username, u.full_name, f.flight_number, f.price 
                                           FROM bookings b 
                                           JOIN users u ON b.user_id = u.id 
                                           JOIN flights f ON b.flight_id = f.id 
                                           WHERE b.status = 'pending' 
                                           ORDER BY b.created_at DESC");
                        while ($booking = $stmt->fetch()) {
                            ?>
                            <tr>
                                <td><?php echo $booking['booking_number']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['username']); ?>
                                    <small class="d-block text-muted"><?php echo htmlspecialchars($booking['full_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['flight_number']); ?></td>
                                <td>$<?php echo number_format($booking['price'], 2); ?></td>
                                <td>
                                    <?php if ($booking['payment_proof']): ?>
                                        <button class="btn btn-sm btn-info" onclick="viewPaymentProof('<?php echo htmlspecialchars($booking['payment_proof']); ?>')">
                                            View Proof
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">No proof uploaded</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this payment?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this payment?')">
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

<!-- Payment Proof Modal -->
<div class="modal fade" id="paymentProofModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="paymentProofImage" src="" alt="Payment Proof" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<script>
function viewPaymentProof(imagePath) {
    document.getElementById('paymentProofImage').src = '../uploads/payment_proofs/' + imagePath;
    new bootstrap.Modal(document.getElementById('paymentProofModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
