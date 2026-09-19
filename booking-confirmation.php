<?php
declare(strict_types=1);

require 'config/database.php';
require 'includes/auth.php';

requireLogin();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$bookingCode = strtoupper(trim((string)($_GET['code'] ?? '')));
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($bookingCode === '' || $userId <= 0) {
    header('Location: my-reservations.php');
    exit;
}

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
        l.hourly_rate,
        l.opening_time,
        l.closing_time,
        s.slot_number,
        s.slot_type,
        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone
     FROM reservations r
     INNER JOIN parking_locations l ON l.id = r.location_id
     INNER JOIN parking_slots s ON s.id = r.slot_id
     INNER JOIN users u ON u.id = r.user_id
     WHERE r.booking_code = ?
       AND r.user_id = ?
     LIMIT 1"
);

$stmt->execute([$bookingCode, $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(404);
    $pageTitle = 'Booking Not Found | ParkNexa';
    require 'includes/header.php';
    ?>
    <section class="ticket-page">
        <div class="ticket-container">
            <div class="ticket-empty-card">
                <div class="ticket-empty-icon">?</div>
                <span class="details-kicker">BOOKING</span>
                <h1>Booking not found</h1>
                <p>The booking code is invalid or the reservation does not belong to your account.</p>
                <a href="my-reservations.php" class="btn">Go to My Reservations</a>
            </div>
        </div>
    </section>
    <?php
    require 'includes/footer.php';
    exit;
}

$start = new DateTime($booking['start_time']);
$end = new DateTime($booking['end_time']);
$durationMinutes = max(0, (int)round(($end->getTimestamp() - $start->getTimestamp()) / 60));
$durationHours = max(1, (int)ceil($durationMinutes / 60));
$status = (string)$booking['status'];

$pageTitle = 'Booking Confirmation | ParkNexa';
require 'includes/header.php';
?>

<section class="ticket-page">
    <div class="ticket-container">

        <div class="ticket-breadcrumb">
            <a href="my-reservations.php">My Reservations</a>
            <span>›</span>
            <strong>Booking Confirmation</strong>
        </div>

        <div class="ticket-success-banner">
            <div class="ticket-success-icon">✓</div>
            <div>
                <span>RESERVATION CONFIRMED</span>
                <h1>Your parking slot is reserved.</h1>
                <p>Keep this booking code handy when you arrive at the parking facility.</p>
            </div>
        </div>

        <article class="booking-ticket" id="printTicket">

            <header class="ticket-header">
                <div>
                    <span class="ticket-kicker">PARKNEXA PARKING PASS</span>
                    <h2>Booking Confirmation</h2>
                    <p><?= h($booking['location_name']) ?></p>
                </div>

                <div class="ticket-code-box">
                    <span>BOOKING CODE</span>
                    <strong><?= h($booking['booking_code']) ?></strong>
                </div>
            </header>

            <div class="ticket-divider"></div>

            <div class="ticket-main-grid">
                <section class="ticket-section">
                    <span class="ticket-section-label">PARKING DETAILS</span>

                    <div class="ticket-detail-grid">
                        <div class="ticket-detail-item">
                            <span>Location</span>
                            <strong><?= h($booking['location_name']) ?></strong>
                        </div>

                        <div class="ticket-detail-item">
                            <span>City</span>
                            <strong><?= h($booking['city']) ?></strong>
                        </div>

                        <div class="ticket-detail-item ticket-detail-wide">
                            <span>Address</span>
                            <strong><?= h($booking['address']) ?></strong>
                        </div>

                        <div class="ticket-detail-item">
                            <span>Parking Slot</span>
                            <strong><?= h($booking['slot_number']) ?></strong>
                            <small><?= h($booking['slot_type']) ?></small>
                        </div>

                        <div class="ticket-detail-item">
                            <span>Operating Hours</span>
                            <strong>
                                <?= h(substr($booking['opening_time'], 0, 5)) ?> –
                                <?= h(substr($booking['closing_time'], 0, 5)) ?>
                            </strong>
                        </div>
                    </div>
                </section>

                <section class="ticket-section">
                    <span class="ticket-section-label">VEHICLE DETAILS</span>

                    <div class="ticket-detail-grid">
                        <div class="ticket-detail-item">
                            <span>Vehicle Number</span>
                            <strong><?= h($booking['vehicle_number']) ?></strong>
                        </div>

                        <div class="ticket-detail-item">
                            <span>Vehicle Type</span>
                            <strong><?= h($booking['vehicle_type']) ?></strong>
                        </div>

                        <div class="ticket-detail-item">
                            <span>Customer</span>
                            <strong><?= h($booking['customer_name']) ?></strong>
                        </div>

                        <div class="ticket-detail-item">
                            <span>Contact</span>
                            <strong><?= h($booking['customer_phone']) ?></strong>
                        </div>
                    </div>
                </section>
            </div>

            <div class="ticket-timeline">
                <div class="ticket-time-block">
                    <span>START</span>
                    <strong><?= h($start->format('d M Y')) ?></strong>
                    <b><?= h($start->format('h:i A')) ?></b>
                </div>

                <div class="ticket-arrow">→</div>

                <div class="ticket-time-block">
                    <span>END</span>
                    <strong><?= h($end->format('d M Y')) ?></strong>
                    <b><?= h($end->format('h:i A')) ?></b>
                </div>

                <div class="ticket-duration">
                    <span>DURATION</span>
                    <strong><?= $durationHours ?> hour<?= $durationHours === 1 ? '' : 's' ?></strong>
                </div>
            </div>

            <div class="ticket-summary-row">
                <div>
                    <span>Hourly Rate</span>
                    <strong>₹<?= number_format((float)$booking['hourly_rate'], 2) ?>/hr</strong>
                </div>
                <div>
                    <span>Booking Status</span>
                    <strong class="ticket-status <?= strtolower($status) === 'confirmed' ? 'confirmed' : 'other' ?>">
                        <?= h($status) ?>
                    </strong>
                </div>
                <div class="ticket-total">
                    <span>Total Amount</span>
                    <strong>₹<?= number_format((float)$booking['amount'], 2) ?></strong>
                </div>
            </div>

            <footer class="ticket-footer-note">
                <div>
                    <strong>Arrival tip</strong>
                    <span>Show this booking code at the parking facility and park only in your assigned slot.</span>
                </div>
                <div class="ticket-code-mini">
                    <?= h($booking['booking_code']) ?>
                </div>
            </footer>
        </article>

        <div class="ticket-actions no-print">
            <button class="btn" type="button" onclick="window.print()">Print Ticket</button>
            <a class="btn btn-outline" href="my-reservations.php">My Reservations</a>
            <a class="btn btn-outline" href="search-parking.php">Find Another Parking</a>
        </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.title = 'Booking Confirmation | ParkNexa';
});
</script>

<?php require 'includes/footer.php'; ?>
