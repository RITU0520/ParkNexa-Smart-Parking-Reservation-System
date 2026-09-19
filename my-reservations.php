<?php
declare(strict_types=1);

require 'config/database.php';
require 'includes/auth.php';

requireLogin();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$message = trim((string)($_GET['message'] ?? ''));
$error = trim((string)($_GET['error'] ?? ''));

/* =========================================================
   CANCEL RESERVATION
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $reservationId = (int)($_POST['reservation_id'] ?? 0);

    if ($reservationId <= 0) {
        header('Location: my-reservations.php?error=Invalid reservation.');
        exit;
    }

    try {
        $pdo->beginTransaction();

        /* Lock the reservation row so two cancellation requests cannot
           update the same booking at the same time. */
        $reservationStmt = $pdo->prepare(
            "SELECT id, location_id, start_time, status
             FROM reservations
             WHERE id = ? AND user_id = ?
             FOR UPDATE"
        );
        $reservationStmt->execute([$reservationId, $userId]);
        $reservation = $reservationStmt->fetch();

        if (!$reservation) {
            throw new RuntimeException('Reservation not found.');
        }

        if ($reservation['status'] !== 'Confirmed') {
            throw new RuntimeException('Only confirmed reservations can be cancelled.');
        }

        $start = new DateTime((string)$reservation['start_time']);
        $now = new DateTime();

        if ($start <= $now) {
            throw new RuntimeException('A reservation that has already started cannot be cancelled.');
        }

        $updateStmt = $pdo->prepare(
            "UPDATE reservations
             SET status = 'Cancelled'
             WHERE id = ? AND user_id = ? AND status = 'Confirmed'"
        );
        $updateStmt->execute([$reservationId, $userId]);

        if ($updateStmt->rowCount() !== 1) {
            throw new RuntimeException('The reservation could not be cancelled.');
        }

        /* The booking page decreases this summary count when a booking
           is created, so put one slot back when the booking is cancelled. */
        $locationStmt = $pdo->prepare(
            "UPDATE parking_locations
             SET available_slots = LEAST(total_slots, available_slots + 1)
             WHERE id = ?"
        );
        $locationStmt->execute([(int)$reservation['location_id']]);

        $pdo->commit();

        header('Location: my-reservations.php?message=Reservation cancelled successfully.');
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        header('Location: my-reservations.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

/* =========================================================
   LOAD USER RESERVATIONS
   ========================================================= */
$stmt = $pdo->prepare(
    "SELECT
        r.id,
        r.booking_code,
        r.vehicle_number,
        r.vehicle_type,
        r.start_time,
        r.end_time,
        r.amount,
        r.status,
        r.created_at,
        l.name AS location_name,
        l.address,
        l.city,
        s.slot_number,
        s.slot_type
     FROM reservations r
     INNER JOIN parking_locations l ON l.id = r.location_id
     INNER JOIN parking_slots s ON s.id = r.slot_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC, r.id DESC"
);
$stmt->execute([$userId]);
$reservations = $stmt->fetchAll();

$totalBookings = count($reservations);
$confirmedBookings = 0;
$cancelledBookings = 0;
$completedBookings = 0;

foreach ($reservations as $reservation) {
    if ($reservation['status'] === 'Confirmed') {
        $confirmedBookings++;
    } elseif ($reservation['status'] === 'Cancelled') {
        $cancelledBookings++;
    } elseif ($reservation['status'] === 'Completed') {
        $completedBookings++;
    }
}

$pageTitle = 'My Reservations | ParkNexa';
require 'includes/header.php';
?>

<section class="reservations-page">
    <div class="reservations-container">

        <div class="reservations-breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            <span>›</span>
            <strong>My Reservations</strong>
        </div>

        <div class="reservations-heading">
            <div>
                <span class="details-kicker">BOOKING HISTORY</span>
                <h1>My Reservations</h1>
                <p>View your parking bookings, open your ticket and manage upcoming reservations.</p>
            </div>
            <a href="search-parking.php" class="btn">Find Parking</a>
        </div>

        <?php if ($message !== ''): ?>
            <div class="reservation-alert reservation-alert-success">
                <span>✓</span>
                <div>
                    <strong>Done</strong>
                    <p><?= h($message) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="reservation-alert reservation-alert-error">
                <span>!</span>
                <div>
                    <strong>Could not complete the request</strong>
                    <p><?= h($error) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="reservation-stats">
            <div class="reservation-stat-card">
                <span class="reservation-stat-icon">▣</span>
                <div>
                    <small>Total Bookings</small>
                    <strong><?= $totalBookings ?></strong>
                </div>
            </div>
            <div class="reservation-stat-card">
                <span class="reservation-stat-icon active">✓</span>
                <div>
                    <small>Confirmed</small>
                    <strong><?= $confirmedBookings ?></strong>
                </div>
            </div>
            <div class="reservation-stat-card">
                <span class="reservation-stat-icon cancelled">×</span>
                <div>
                    <small>Cancelled</small>
                    <strong><?= $cancelledBookings ?></strong>
                </div>
            </div>
            <div class="reservation-stat-card">
                <span class="reservation-stat-icon completed">◆</span>
                <div>
                    <small>Completed</small>
                    <strong><?= $completedBookings ?></strong>
                </div>
            </div>
        </div>

        <?php if (!$reservations): ?>
            <div class="reservation-empty-card">
                <div class="reservation-empty-icon">🅿</div>
                <span class="details-kicker">NO BOOKINGS YET</span>
                <h2>Your reservation history is empty.</h2>
                <p>Find a parking location and reserve a slot to see your bookings here.</p>
                <a href="search-parking.php" class="btn">Find Parking</a>
            </div>
        <?php else: ?>
            <div class="reservation-list">
                <?php foreach ($reservations as $reservation): ?>
                    <?php
                    $start = new DateTime((string)$reservation['start_time']);
                    $end = new DateTime((string)$reservation['end_time']);
                    $durationMinutes = max(0, (int)round(($end->getTimestamp() - $start->getTimestamp()) / 60));
                    $durationHours = max(1, (int)ceil($durationMinutes / 60));
                    $status = (string)$reservation['status'];
                    $statusClass = match ($status) {
                        'Confirmed' => 'confirmed',
                        'Cancelled' => 'cancelled',
                        'Completed' => 'completed',
                        default => 'other',
                    };
                    $canCancel = $status === 'Confirmed' && $start > new DateTime();
                    ?>

                    <article class="reservation-card">
                        <div class="reservation-card-top">
                            <div class="reservation-code-block">
                                <span class="reservation-mini-label">BOOKING CODE</span>
                                <strong><?= h($reservation['booking_code']) ?></strong>
                            </div>

                            <span class="reservation-status <?= h($statusClass) ?>">
                                <?= $status === 'Confirmed' ? '● ' : '' ?><?= h($status) ?>
                            </span>
                        </div>

                        <div class="reservation-card-body">
                            <div class="reservation-location-block">
                                <div class="reservation-location-icon">🅿</div>
                                <div>
                                    <span class="reservation-mini-label">PARKING LOCATION</span>
                                    <h2><?= h($reservation['location_name']) ?></h2>
                                    <p><?= h($reservation['address']) ?>, <?= h($reservation['city']) ?></p>
                                </div>
                            </div>

                            <div class="reservation-grid">
                                <div class="reservation-detail">
                                    <span>Parking Slot</span>
                                    <strong><?= h($reservation['slot_number']) ?></strong>
                                    <small><?= h($reservation['slot_type']) ?></small>
                                </div>
                                <div class="reservation-detail">
                                    <span>Vehicle</span>
                                    <strong><?= h($reservation['vehicle_number']) ?></strong>
                                    <small><?= h($reservation['vehicle_type']) ?></small>
                                </div>
                                <div class="reservation-detail reservation-detail-wide">
                                    <span>Parking Time</span>
                                    <strong><?= h($start->format('d M Y')) ?></strong>
                                    <small><?= h($start->format('h:i A')) ?> – <?= h($end->format('h:i A')) ?></small>
                                </div>
                                <div class="reservation-detail">
                                    <span>Duration</span>
                                    <strong><?= $durationHours ?> hr<?= $durationHours === 1 ? '' : 's' ?></strong>
                                    <small>Reserved period</small>
                                </div>
                            </div>
                        </div>

                        <div class="reservation-card-bottom">
                            <div class="reservation-amount">
                                <span>Total Amount</span>
                                <strong>₹<?= number_format((float)$reservation['amount'], 2) ?></strong>
                            </div>

                            <div class="reservation-actions">
                                <a href="booking-confirmation.php?code=<?= urlencode((string)$reservation['booking_code']) ?>" class="btn btn-sm">
                                    View Ticket
                                </a>

                                <?php if ($canCancel): ?>
                                    <form method="post" onsubmit="return confirm('Cancel this reservation?');">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm">Cancel Booking</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require 'includes/footer.php'; ?>
