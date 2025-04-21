<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Transaction History</h1>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Flight Details</th>
                            <th>Airline</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT b.*, f.flight_number, f.price, 
                                             f.departure_city, f.arrival_city, f.departure_time,
                                             a.airline_name
                                             FROM bookings b 
                                             JOIN flights f ON b.flight_id = f.id 
                                             JOIN airlines a ON f.airline_id = a.id
                                             WHERE b.user_id = ?
                                             ORDER BY b.created_at DESC");
                        $stmt->execute([$_SESSION['user_id']]);
                        
                        while ($transaction = $stmt->fetch()) {
                            $status_class = match($transaction['status']) {
                                'pending' => 'success',
                                'admin_approved' => 'info',
                                'airline_approved' => 'primary',
                                'completed' => 'success',
                                'rejected' => 'danger',
                                default => 'warning'
                            };
                            $status_text = match($transaction['status']) {
                                'pending' => 'Complete',
                                'admin_approved' => 'Admin Approved',
                                'airline_approved' => 'Airline Approved',
                                'completed' => 'Complete',
                                'rejected' => 'Rejected',
                                default => 'Pending'
                            };
                            ?>
                            <tr>
                                <td><?php echo $transaction['booking_number']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($transaction['flight_number']); ?></strong>
                                    <div><?php echo htmlspecialchars($transaction['departure_city']); ?> → <?php echo htmlspecialchars($transaction['arrival_city']); ?></div>
                                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($transaction['departure_time'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['airline_name']); ?></td>
                                <td>Rp <?php echo number_format($transaction['price'], 0, ',', '.'); ?></td>
                                <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
