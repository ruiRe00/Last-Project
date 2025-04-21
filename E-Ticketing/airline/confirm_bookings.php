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

// Handle booking confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
    }
    $stmt->execute([$booking_id]);
    
    // If approved, decrease available seats
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE flights SET available_seats = available_seats - 1 
                             WHERE id = (SELECT flight_id FROM bookings WHERE id = ?)");
        $stmt->execute([$booking_id]);
    }
    
    header('Location: confirm_bookings.php');
    exit();
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Confirm Bookings</h1>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Passenger Details</th>
                            <th>Flight</th>
                            <th>Contact Info</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT b.*, u.username, u.full_name, u.email, u.phone, u.address, 
                                             f.flight_number, f.departure_city, f.arrival_city, f.departure_time 
                                             FROM bookings b 
                                             JOIN users u ON b.user_id = u.id 
                                             JOIN flights f ON b.flight_id = f.id 
                                             WHERE f.airline_id = ? AND b.status = 'admin_approved' 
                                             ORDER BY b.created_at DESC");
                        $stmt->execute([$airline_id]);
                        while ($booking = $stmt->fetch()) {
                            ?>
                            <tr>
                                <td><?php echo $booking['booking_number']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['full_name']); ?></strong>
                                    <div class="text-muted"><?php echo htmlspecialchars($booking['address']); ?></div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['flight_number']); ?></strong>
                                    <div><?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($booking['departure_time'])); ?></small>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($booking['email']); ?></div>
                                    <div><?php echo htmlspecialchars($booking['phone']); ?></div>
                                </td>
                                <td><span class="badge bg-info">Admin Approved</span></td>
                                <td><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this booking?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this booking?')">
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

<?php include '../includes/footer.php'; ?>
