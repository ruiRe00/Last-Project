<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has role 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

// Check if flight_id is provided
if (!isset($_POST['flight_id'])) {
    $_SESSION['error'] = "No flight selected.";
    header('Location: dashboard.php');
    exit();
}

$flight_id = $_POST['flight_id'];
$user_id = $_SESSION['user_id'];

// Get flight details
$stmt = $pdo->prepare("SELECT f.*, a.airline_name 
                       FROM flights f 
                       JOIN airlines a ON f.airline_id = a.id 
                       WHERE f.id = ?");
$stmt->execute([$flight_id]);
$flight = $stmt->fetch();

// Get user details
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Check if flight exists and is available
if (!$flight || $flight['status'] !== 'active' || $flight['available_seats'] <= 0) {
    $_SESSION['error'] = "Flight is not available for booking.";
    header('Location: dashboard.php');
    exit();
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Generate booking number
        $booking_number = 'BK' . date('YmdHis') . rand(100, 999);

        // Insert booking
        $stmt = $pdo->prepare("INSERT INTO bookings (booking_number, user_id, flight_id, status, payment_proof, created_at) 
                              VALUES (?, ?, ?, 'pending', ?, NOW())");
        $stmt->execute([
            $booking_number,
            $user_id,
            $flight_id,
            $_POST['payment_proof']
        ]);

        // Update available seats
        $stmt = $pdo->prepare("UPDATE flights SET available_seats = available_seats - 1 WHERE id = ?");
        $stmt->execute([$flight_id]);

        // Commit transaction
        $pdo->commit();

        $_SESSION['success'] = "Booking successful! Your booking number is " . $booking_number;
        header('Location: my_tickets.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error processing booking: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark text-white">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-ticket-alt me-2"></i>
                        Konfirmasi Pembayaran: <?php echo htmlspecialchars($user['username']); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">Flight Details</h5>
                            <table class="table table-dark">
                                <tr>
                                    <th>Airline:</th>
                                    <td><?php echo htmlspecialchars($flight['airline_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Flight Number:</th>
                                    <td><?php echo htmlspecialchars($flight['flight_number']); ?></td>
                                </tr>
                                <tr>
                                    <th>Route:</th>
                                    <td>
                                        <?php echo htmlspecialchars($flight['departure_city']); ?> →
                                        <?php echo htmlspecialchars($flight['arrival_city']); ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">Schedule & Price</h5>
                            <table class="table table-dark">
                                <tr>
                                    <th>Departure:</th>
                                    <td><?php echo date('d M Y H:i', strtotime($flight['departure_time'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Arrival:</th>
                                    <td><?php echo date('d M Y H:i', strtotime($flight['arrival_time'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Price:</th>
                                    <td class="text-success fw-bold">
                                        Rp <?php echo number_format($flight['price'], 0, ',', '.'); ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <form method="POST" class="mt-4">
                        <input type="hidden" name="flight_id" value="<?php echo $flight_id; ?>">
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Payment Details</h5>
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                Booking details for confirmation:
                            </div>
                            
                            <div class="card bg-dark border-secondary mb-4">
                                <div class="card-body">
                                    <h6 class="card-title border-bottom pb-2 mb-3">Passenger Information</h6>
                                    <table class="table table-dark mb-0">
                                        <tr>
                                            <th width="200">Booking ID</th>
                                            <td><?php echo 'BK' . date('YmdHis') . rand(100, 999); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Passenger Name</th>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Booking Date</th>
                                            <td><?php echo date('d M Y H:i'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="card bg-dark border-secondary mb-4">
                                <div class="card-body">
                                    <h6 class="card-title border-bottom pb-2 mb-3">Flight Information</h6>
                                    <table class="table table-dark mb-0">
                                        <tr>
                                            <th width="200">Airline</th>
                                            <td><?php echo htmlspecialchars($flight['airline_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Flight Number</th>
                                            <td><?php echo htmlspecialchars($flight['flight_number']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Route</th>
                                            <td>
                                                <?php echo htmlspecialchars($flight['departure_city']); ?> →
                                                <?php echo htmlspecialchars($flight['arrival_city']); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Departure Time</th>
                                            <td><?php echo date('d M Y H:i', strtotime($flight['departure_time'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Arrival Time</th>
                                            <td><?php echo date('d M Y H:i', strtotime($flight['arrival_time'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Ticket Price</th>
                                            <td class="text-success fw-bold">
                                                Rp <?php echo number_format($flight['price'], 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <h6 class="alert-heading mb-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Booking Confirmation
                                </h6>
                                <p class="mb-0">Please confirm that all the information above is correct before proceeding with the booking.</p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirmation Note</label>
                                <input type="text" class="form-control" name="payment_proof" required
                                       placeholder="Write 'Confirm: [Your Username]' to proceed">
                                <small class="text-muted">Example: Confirm: <?php echo htmlspecialchars($user['username']); ?></small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" name="confirm_booking" class="btn btn-primary">
                                <i class="fas fa-check me-2"></i>Confirm Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 