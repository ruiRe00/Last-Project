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

// Handle flight actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("INSERT INTO flights (airline_id, flight_number, departure_city, arrival_city, 
                                         departure_time, arrival_time, price, available_seats, status) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->execute([
                        $airline_id,
                        $_POST['flight_number'],
                        $_POST['departure_city'],
                        $_POST['arrival_city'],
                        $_POST['departure_time'],
                        $_POST['arrival_time'],
                        $_POST['price'],
                        $_POST['available_seats']
                    ]);
                    $_SESSION['success'] = "Flight added successfully!";
                    break;

                case 'edit':
                    $stmt = $pdo->prepare("UPDATE flights SET flight_number = ?, departure_city = ?, arrival_city = ?, 
                                         departure_time = ?, arrival_time = ?, price = ?, available_seats = ? 
                                         WHERE id = ? AND airline_id = ?");
                    $stmt->execute([
                        $_POST['flight_number'],
                        $_POST['departure_city'],
                        $_POST['arrival_city'],
                        $_POST['departure_time'],
                        $_POST['arrival_time'],
                        $_POST['price'],
                        $_POST['available_seats'],
                        $_POST['flight_id'],
                        $airline_id
                    ]);
                    $_SESSION['success'] = "Flight updated successfully!";
                    break;

                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM flights WHERE id = ? AND airline_id = ?");
                    $stmt->execute([$_POST['flight_id'], $airline_id]);
                    $_SESSION['success'] = "Flight deleted successfully!";
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        
        header('Location: manage_flights.php');
        exit();
    }
}

include '../includes/header.php';

// Fetch all flights for this airline
$stmt = $pdo->prepare("SELECT * FROM flights WHERE airline_id = ? ORDER BY departure_time");
$stmt->execute([$airline_id]);
$flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Manage Flights</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFlightModal">
            <i class="fas fa-plus"></i> Add Flight
        </button>
    </div>

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

    <div class="card">
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flights as $flight): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($flight['flight_number']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($flight['departure_city']); ?> →
                                    <?php echo htmlspecialchars($flight['arrival_city']); ?>
                                </td>
                                <td>
                                    <div><?php echo date('M d, Y H:i', strtotime($flight['departure_time'])); ?></div>
                                    <small class="text-muted">to</small>
                                    <div><?php echo date('M d, Y H:i', strtotime($flight['arrival_time'])); ?></div>
                                </td>
                                <td>Rp <?php echo number_format($flight['price'], 0, ',', '.'); ?></td>
                                <td><?php echo $flight['available_seats']; ?></td>
                                <td>
                                    <?php
                                    $now = new DateTime();
                                    $departure = new DateTime($flight['departure_time']);
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    if ($departure < $now) {
                                        $status_class = 'secondary';
                                        $status_text = 'Expired';
                                    } else {
                                        $status_class = $flight['status'] === 'active' ? 'success' : 'danger';
                                        $status_text = ucfirst($flight['status']);
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($departure > $now): ?>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="editFlight(<?php echo htmlspecialchars(json_encode($flight)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteFlight(<?php echo $flight['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">No actions available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Flight Modal -->
<div class="modal fade" id="addFlightModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Flight</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Flight Number</label>
                        <input type="text" class="form-control" name="flight_number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Departure City</label>
                        <input type="text" class="form-control" name="departure_city" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Arrival City</label>
                        <input type="text" class="form-control" name="arrival_city" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Departure Time</label>
                        <input type="datetime-local" class="form-control" name="departure_time" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Arrival Time</label>
                        <input type="datetime-local" class="form-control" name="arrival_time" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (Rp)</label>
                        <input type="number" class="form-control" name="price" required min="0" step="1000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Available Seats</label>
                        <input type="number" class="form-control" name="available_seats" required min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Flight</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Flight Modal -->
<div class="modal fade" id="editFlightModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Flight</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="flight_id" id="edit_flight_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Flight Number</label>
                        <input type="text" class="form-control" name="flight_number" id="edit_flight_number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Departure City</label>
                        <input type="text" class="form-control" name="departure_city" id="edit_departure_city" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Arrival City</label>
                        <input type="text" class="form-control" name="arrival_city" id="edit_arrival_city" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Departure Time</label>
                        <input type="datetime-local" class="form-control" name="departure_time" id="edit_departure_time" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Arrival Time</label>
                        <input type="datetime-local" class="form-control" name="arrival_time" id="edit_arrival_time" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (Rp)</label>
                        <input type="number" class="form-control" name="price" id="edit_price" required min="0" step="1000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Available Seats</label>
                        <input type="number" class="form-control" name="available_seats" id="edit_available_seats" required min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toISOString().slice(0, 16);
}

function editFlight(flight) {
    // Set values in edit modal
    document.getElementById('edit_flight_id').value = flight.id;
    document.getElementById('edit_flight_number').value = flight.flight_number;
    document.getElementById('edit_departure_city').value = flight.departure_city;
    document.getElementById('edit_arrival_city').value = flight.arrival_city;
    document.getElementById('edit_departure_time').value = formatDateTime(flight.departure_time);
    document.getElementById('edit_arrival_time').value = formatDateTime(flight.arrival_time);
    document.getElementById('edit_price').value = flight.price;
    document.getElementById('edit_available_seats').value = flight.available_seats;

    // Show modal
    const editModal = new bootstrap.Modal(document.getElementById('editFlightModal'));
    editModal.show();
}

function deleteFlight(flightId) {
    if (confirm('Are you sure you want to delete this flight?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="flight_id" value="${flightId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
