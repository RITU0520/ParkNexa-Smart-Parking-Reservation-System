<?php
declare(strict_types=1);

require 'config/database.php';
require 'includes/auth.php';

requireLogin();

$locationId = (int)($_GET['id'] ?? $_POST['location_id'] ?? 0);

if ($locationId <= 0) {
    header('Location: search-parking.php');
    exit;
}

/* =========================================================
   HELPERS
   ========================================================= */

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function makeBookingCode(PDO $pdo): string
{
    do {
        $code = 'PK' . strtoupper(bin2hex(random_bytes(4)));

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM reservations WHERE booking_code = ?'
        );
        $stmt->execute([$code]);

    } while ((int)$stmt->fetchColumn() > 0);

    return $code;
}


/* =========================================================
   LOAD LOCATION
   ========================================================= */

$stmt = $pdo->prepare(
    "SELECT *
     FROM parking_locations
     WHERE id = ?
       AND status = 'Active'
     LIMIT 1"
);

$stmt->execute([$locationId]);
$location = $stmt->fetch();

if (!$location) {
    http_response_code(404);
    exit('Parking location not found.');
}


/* =========================================================
   DATE/TIME INPUTS
   ========================================================= */

$defaultStart = date('Y-m-d\TH:i');
$defaultEnd = date('Y-m-d\TH:i', time() + 3600);

$startInput = $_GET['start_time'] ?? $_POST['start_time'] ?? $defaultStart;
$endInput   = $_GET['end_time'] ?? $_POST['end_time'] ?? $defaultEnd;

$startInput = trim((string)$startInput);
$endInput   = trim((string)$endInput);

$startDateTime = DateTime::createFromFormat('Y-m-d\TH:i', $startInput);
$endDateTime   = DateTime::createFromFormat('Y-m-d\TH:i', $endInput);

$availabilityError = '';

if (!$startDateTime || !$endDateTime) {
    $startDateTime = new DateTime();
    $endDateTime = (clone $startDateTime)->modify('+1 hour');
}

if ($endDateTime <= $startDateTime) {
    $availabilityError = 'End time must be after start time.';
}


/* =========================================================
   LOAD SLOTS + CURRENT AVAILABILITY
   ========================================================= */

$slotStmt = $pdo->prepare(
    "SELECT *
     FROM parking_slots
     WHERE location_id = ?
     ORDER BY slot_number ASC"
);

$slotStmt->execute([$locationId]);
$slots = $slotStmt->fetchAll();

$occupiedSlotIds = [];

if ($availabilityError === '') {
    $overlapStmt = $pdo->prepare(
        "SELECT slot_id
         FROM reservations
         WHERE location_id = ?
           AND status = 'Confirmed'
           AND start_time < ?
           AND end_time > ?"
    );

    $overlapStmt->execute([
        $locationId,
        $endDateTime->format('Y-m-d H:i:s'),
        $startDateTime->format('Y-m-d H:i:s')
    ]);

    $occupiedSlotIds = array_map(
        'intval',
        $overlapStmt->fetchAll(PDO::FETCH_COLUMN)
    );
}


/* =========================================================
   RESERVATION SUBMISSION
   ========================================================= */

$bookingError = '';
$bookingSuccess = '';
$bookingCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $slotId = (int)($_POST['slot_id'] ?? 0);
    $vehicleNumber = strtoupper(trim((string)($_POST['vehicle_number'] ?? '')));
    $vehicleType = trim((string)($_POST['vehicle_type'] ?? ''));

    $allowedVehicleTypes = ['Car', 'Bike', 'SUV', 'Other'];

    if ($slotId <= 0) {
        $bookingError = 'Please select a parking slot.';
    } elseif ($vehicleNumber === '') {
        $bookingError = 'Please enter your vehicle number.';
    } elseif (!preg_match('/^[A-Z0-9][A-Z0-9 -]{2,19}$/', $vehicleNumber)) {
        $bookingError = 'Enter a valid vehicle number.';
    } elseif (!in_array($vehicleType, $allowedVehicleTypes, true)) {
        $bookingError = 'Please select a valid vehicle type.';
    } elseif (!$startDateTime || !$endDateTime || $endDateTime <= $startDateTime) {
        $bookingError = 'Please select a valid start and end time.';
    } else {

        $opening = DateTime::createFromFormat(
            'H:i:s',
            $location['opening_time']
        );

        $closing = DateTime::createFromFormat(
            'H:i:s',
            $location['closing_time']
        );

        $startClock = $startDateTime->format('H:i:s');
        $endClock = $endDateTime->format('H:i:s');

        if (
            $opening &&
            $closing &&
            (
                $startClock < $opening->format('H:i:s') ||
                $endClock > $closing->format('H:i:s')
            )
        ) {
            $bookingError =
                'Please choose a time within the parking location operating hours.';
        } else {

            $durationMinutes =
                (int)ceil(
                    ($endDateTime->getTimestamp() - $startDateTime->getTimestamp())
                    / 60
                );

            if ($durationMinutes > 24 * 60) {
                $bookingError = 'Reservations can be a maximum of 24 hours.';
            } else {

                $hours = max(1, (int)ceil($durationMinutes / 60));

                $amount =
                    round($hours * (float)$location['hourly_rate'], 2);

                try {

                    $pdo->beginTransaction();

                    /* Lock the selected slot so two users cannot book
                       the same slot at the same time. */
                    $lockStmt = $pdo->prepare(
                        "SELECT id, slot_number, slot_type, status
                         FROM parking_slots
                         WHERE id = ?
                           AND location_id = ?
                         FOR UPDATE"
                    );

                    $lockStmt->execute([
                        $slotId,
                        $locationId
                    ]);

                    $selectedSlot = $lockStmt->fetch();

                    if (!$selectedSlot) {
                        throw new RuntimeException(
                            'The selected slot does not exist.'
                        );
                    }

                    if ($selectedSlot['status'] !== 'Available') {
                        throw new RuntimeException(
                            'The selected slot is not available.'
                        );
                    }

                    /* Check overlapping confirmed reservations again
                       inside the transaction. */
                    $conflictStmt = $pdo->prepare(
                        "SELECT id
                         FROM reservations
                         WHERE slot_id = ?
                           AND status = 'Confirmed'
                           AND start_time < ?
                           AND end_time > ?
                         LIMIT 1
                         FOR UPDATE"
                    );

                    $conflictStmt->execute([
                        $slotId,
                        $endDateTime->format('Y-m-d H:i:s'),
                        $startDateTime->format('Y-m-d H:i:s')
                    ]);

                    if ($conflictStmt->fetch()) {
                        throw new RuntimeException(
                            'That slot was just reserved for the selected time.'
                        );
                    }

                    $bookingCode = makeBookingCode($pdo);

                    $insertStmt = $pdo->prepare(
                        "INSERT INTO reservations
                        (
                            booking_code,
                            user_id,
                            location_id,
                            slot_id,
                            vehicle_number,
                            vehicle_type,
                            start_time,
                            end_time,
                            amount,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed')"
                    );

                    $insertStmt->execute([
                        $bookingCode,
                        (int)$_SESSION['user_id'],
                        $locationId,
                        $slotId,
                        $vehicleNumber,
                        $vehicleType,
                        $startDateTime->format('Y-m-d H:i:s'),
                        $endDateTime->format('Y-m-d H:i:s'),
                        $amount
                    ]);

                    /* Keep the location's aggregate available count
                       in sync with the completed reservation. */
                    $locationUpdate = $pdo->prepare(
                        "UPDATE parking_locations
                         SET available_slots =
                             CASE
                                 WHEN available_slots > 0
                                 THEN available_slots - 1
                                 ELSE 0
                             END
                         WHERE id = ?"
                    );

                    $locationUpdate->execute([$locationId]);

                    $pdo->commit();

                    header(
                        'Location: booking-confirmation.php?code='
                        . rawurlencode($bookingCode)
                    );
                    exit;

                } catch (Throwable $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $bookingError = $e->getMessage();
                }
            }
        }
    }
}


/* =========================================================
   REFRESH LOCATION + SLOTS AFTER BOOKING
   ========================================================= */

if ($bookingSuccess !== '') {

    $stmt = $pdo->prepare(
        "SELECT *
         FROM parking_locations
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([$locationId]);
    $location = $stmt->fetch();

    $slotStmt->execute([$locationId]);
    $slots = $slotStmt->fetchAll();

    $overlapStmt = $pdo->prepare(
        "SELECT slot_id
         FROM reservations
         WHERE location_id = ?
           AND status = 'Confirmed'
           AND start_time < ?
           AND end_time > ?"
    );

    $overlapStmt->execute([
        $locationId,
        $endDateTime->format('Y-m-d H:i:s'),
        $startDateTime->format('Y-m-d H:i:s')
    ]);

    $occupiedSlotIds = array_map(
        'intval',
        $overlapStmt->fetchAll(PDO::FETCH_COLUMN)
    );
}


/* =========================================================
   DERIVED VIEW DATA
   ========================================================= */

$availableCount = 0;

foreach ($slots as $slot) {
    if (
        $slot['status'] === 'Available' &&
        !in_array((int)$slot['id'], $occupiedSlotIds, true)
    ) {
        $availableCount++;
    }
}

$pageTitle =
    h($location['name']) . ' | ParkNexa';

require 'includes/header.php';
?>

<section class="details-page">

    <!-- HEADER -->
    <section class="details-hero">

        <div class="details-container">

            <a href="search-parking.php" class="details-back">
                ← Back to Find Parking
            </a>

            <div class="details-overview">

                <div class="details-image">
                    <img
                        src="assets/images/parking-hero-reference.png"
                        alt="<?= h($location['name']) ?>"
                    >

                    <span class="details-open-badge">
                        ● Open now
                    </span>
                </div>


                <div class="details-info">

                    <span class="details-city">
                        <?= h($location['city']) ?>
                    </span>

                    <h1>
                        <?= h($location['name']) ?>
                    </h1>

                    <p class="details-address">
                        <span>⌖</span>
                        <?= h($location['address']) ?>
                    </p>


                    <div class="details-rating">
                        <span>★★★★★</span>
                        <strong>4.8</strong>
                        <small>Customer parking facility</small>
                    </div>


                    <div class="details-feature-row">

                        <div class="details-feature">
                            <span class="details-feature-icon">▣</span>
                            <div>
                                <strong>CCTV</strong>
                                <small>Monitored</small>
                            </div>
                        </div>

                        <div class="details-feature">
                            <span class="details-feature-icon">✓</span>
                            <div>
                                <strong>Security</strong>
                                <small>Protected</small>
                            </div>
                        </div>

                        <div class="details-feature">
                            <span class="details-feature-icon orange">ϟ</span>
                            <div>
                                <strong>EV</strong>
                                <small>Supported</small>
                            </div>
                        </div>

                        <div class="details-feature">
                            <span class="details-feature-icon">◷</span>
                            <div>
                                <strong>Hours</strong>
                                <small>
                                    <?= h(substr($location['opening_time'], 0, 5)) ?>
                                    –
                                    <?= h(substr($location['closing_time'], 0, 5)) ?>
                                </small>
                            </div>
                        </div>

                    </div>

                </div>


                <div class="details-price-card">

                    <span>Starting from</span>

                    <strong>
                        ₹<?= number_format((float)$location['hourly_rate'], 0) ?>
                    </strong>

                    <small>per hour</small>

                    <div class="details-available-count">
                        <b><?= $availableCount ?></b>
                        <span>slots available</span>
                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- MAIN -->
    <section class="details-container details-main">

        <?php if ($bookingSuccess !== ''): ?>

            <div class="booking-success">

                <div class="booking-success-icon">✓</div>

                <div>
                    <strong>Reservation confirmed</strong>

                    <p>
                        Your booking code is
                        <b><?= h($bookingCode) ?></b>.
                        Keep this code for managing your reservation.
                    </p>
                </div>

                <a href="my-reservations.php" class="btn btn-sm">
                    My Reservations →
                </a>

            </div>

        <?php endif; ?>


        <?php if ($bookingError !== ''): ?>

            <div class="details-alert error">
                <?= h($bookingError) ?>
            </div>

        <?php endif; ?>


        <div class="details-layout">

            <!-- LEFT -->
            <div class="details-left">

                <div class="details-card">

                    <div class="details-card-header">

                        <div>
                            <span class="details-kicker">PARKING AVAILABILITY</span>
                            <h2>Choose your parking slot</h2>
                        </div>

                        <div class="slot-legend">
                            <span>
                                <i class="legend available"></i>
                                Available
                            </span>

                            <span>
                                <i class="legend selected"></i>
                                Selected
                            </span>

                            <span>
                                <i class="legend occupied"></i>
                                Occupied
                            </span>
                        </div>

                    </div>


                    <!-- AVAILABILITY FILTER -->
                    <form
                        class="availability-bar"
                        method="get"
                        action="parking-details.php"
                    >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)$location['id'] ?>"
                        >

                        <label>
                            <span>Start time</span>

                            <input
                                type="datetime-local"
                                name="start_time"
                                value="<?= h($startInput) ?>"
                                required
                            >
                        </label>


                        <label>
                            <span>End time</span>

                            <input
                                type="datetime-local"
                                name="end_time"
                                value="<?= h($endInput) ?>"
                                required
                            >
                        </label>


                        <button class="btn btn-sm" type="submit">
                            Check Availability
                        </button>

                    </form>


                    <?php if ($availabilityError !== ''): ?>

                        <div class="details-alert error">
                            <?= h($availabilityError) ?>
                        </div>

                    <?php endif; ?>


                    <div class="slot-grid-modern">

                        <?php if (!$slots): ?>

                            <div class="no-slots">
                                No parking slots have been configured for this location.
                            </div>

                        <?php else: ?>

                            <?php foreach ($slots as $slot): ?>

                                <?php
                                $slotId = (int)$slot['id'];

                                $isMaintenance =
                                    $slot['status'] !== 'Available';

                                $isOccupied =
                                    in_array(
                                        $slotId,
                                        $occupiedSlotIds,
                                        true
                                    );

                                $statusClass =
                                    $isMaintenance
                                        ? 'maintenance'
                                        : ($isOccupied ? 'occupied' : 'available');
                                ?>

                                <button
                                    type="button"
                                    class="slot-modern <?= $statusClass ?>"
                                    data-slot-id="<?= $slotId ?>"
                                    data-slot-number="<?= h($slot['slot_number']) ?>"
                                    data-slot-type="<?= h($slot['slot_type']) ?>"
                                    <?= ($isMaintenance || $isOccupied) ? 'disabled' : '' ?>
                                >

                                    <span class="slot-modern-number">
                                        <?= h($slot['slot_number']) ?>
                                    </span>

                                    <small>
                                        <?= h($slot['slot_type']) ?>
                                    </small>

                                    <span class="slot-modern-status">
                                        <?= $isMaintenance
                                            ? 'Maintenance'
                                            : ($isOccupied ? 'Occupied' : 'Available')
                                        ?>
                                    </span>

                                </button>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- FACILITIES -->
                <div class="details-card facilities-card">

                    <div class="details-card-header compact">

                        <div>
                            <span class="details-kicker">AT THIS LOCATION</span>
                            <h2>Facilities &amp; amenities</h2>
                        </div>

                    </div>


                    <div class="facility-grid">

                        <div class="facility">
                            <span>▣</span>
                            <strong>Wide Slots</strong>
                            <small>Comfortable parking</small>
                        </div>

                        <div class="facility">
                            <span>✓</span>
                            <strong>Security</strong>
                            <small>On-site protection</small>
                        </div>

                        <div class="facility">
                            <span>ϟ</span>
                            <strong>EV Ready</strong>
                            <small>Charging supported</small>
                        </div>

                        <div class="facility">
                            <span>◷</span>
                            <strong>Open Daily</strong>
                            <small>
                                <?= h(substr($location['opening_time'], 0, 5)) ?>
                                –
                                <?= h(substr($location['closing_time'], 0, 5)) ?>
                            </small>
                        </div>

                    </div>

                </div>

            </div>


            <!-- RIGHT -->
            <aside class="details-right">

                <div class="booking-card-modern">

                    <div class="booking-card-title">

                        <span class="details-kicker">
                            RESERVATION
                        </span>

                        <h2>Your Reservation</h2>

                        <p>
                            Select a slot and enter your vehicle details.
                        </p>

                    </div>


                    <form
                        method="post"
                        action="parking-details.php?id=<?= (int)$location['id'] ?>"
                        id="reservationForm"
                    >

                        <input
                            type="hidden"
                            name="location_id"
                            value="<?= (int)$location['id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="slot_id"
                            id="selectedSlotInput"
                            value=""
                        >

                        <input
                            type="hidden"
                            name="start_time"
                            value="<?= h($startInput) ?>"
                        >

                        <input
                            type="hidden"
                            name="end_time"
                            value="<?= h($endInput) ?>"
                        >


                        <label class="booking-field">

                            <span>Vehicle Number</span>

                            <input
                                type="text"
                                name="vehicle_number"
                                maxlength="20"
                                placeholder="e.g. UP15 AB 1234"
                                required
                            >

                        </label>


                        <label class="booking-field">

                            <span>Vehicle Type</span>

                            <select name="vehicle_type" required>

                                <option value="">
                                    Select vehicle type
                                </option>

                                <option value="Car">Car</option>
                                <option value="Bike">Bike</option>
                                <option value="SUV">SUV</option>
                                <option value="Other">Other</option>

                            </select>

                        </label>


                        <div class="booking-time-grid">

                            <div class="booking-time">
                                <span>Start</span>
                                <strong>
                                    <?= h($startDateTime->format('d M, h:i A')) ?>
                                </strong>
                            </div>

                            <div class="booking-time">
                                <span>End</span>
                                <strong>
                                    <?= h($endDateTime->format('d M, h:i A')) ?>
                                </strong>
                            </div>

                        </div>


                        <div class="selected-slot-box">

                            <div class="selected-slot-icon">
                                P
                            </div>

                            <div>
                                <span>Selected Slot</span>

                                <strong id="selectedSlotText">
                                    Select a slot
                                </strong>

                                <small id="selectedSlotType">
                                    No slot selected
                                </small>
                            </div>

                        </div>


                        <?php
                        $durationMinutes =
                            max(
                                60,
                                (int)round(
                                    (
                                        $endDateTime->getTimestamp()
                                        -
                                        $startDateTime->getTimestamp()
                                    ) / 60
                                )
                            );

                        $billableHours =
                            max(
                                1,
                                (int)ceil($durationMinutes / 60)
                            );

                        $estimatedAmount =
                            round(
                                $billableHours
                                *
                                (float)$location['hourly_rate'],
                                2
                            );
                        ?>


                        <div class="booking-summary">

                            <div>
                                <span>Hourly Rate</span>
                                <strong>
                                    ₹<?= number_format((float)$location['hourly_rate'], 2) ?>/hr
                                </strong>
                            </div>

                            <div>
                                <span>Duration</span>
                                <strong>
                                    <?= $billableHours ?> hour<?= $billableHours === 1 ? '' : 's' ?>
                                </strong>
                            </div>

                            <div class="booking-total">
                                <span>Estimated Amount</span>
                                <strong>
                                    ₹<?= number_format($estimatedAmount, 2) ?>
                                </strong>
                            </div>

                        </div>


                        <button
                            class="btn reserve-button"
                            id="reserveButton"
                            type="submit"
                            disabled
                        >
                            Reserve Slot →
                        </button>


                        <p class="secure-note">
                            🔒 Your booking is safe and secure.
                        </p>

                    </form>

                </div>


                <!-- MAP -->
                <div class="details-map-card">

                    <div class="details-map-heading">

                        <div>
                            <span class="details-kicker">LOCATION</span>
                            <h3>Find this parking area</h3>
                        </div>

                        <a
                            href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode($location['address'] . ', ' . $location['city'] . ', India') ?>"
                            target="_blank"
                            rel="noopener"
                        >
                            Get Directions ↗
                        </a>

                    </div>

                    <div class="details-map-fallback">

                        <div class="map-pin-large">
                            ⌖
                        </div>

                        <strong>
                            <?= h($location['city']) ?>
                        </strong>

                        <span>
                            <?= h($location['address']) ?>
                        </span>

                        <a
                            href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode($location['address'] . ', ' . $location['city'] . ', India') ?>"
                            target="_blank"
                            rel="noopener"
                        >
                            Open in Google Maps
                        </a>

                    </div>

                </div>

            </aside>

        </div>

    </section>

</section>


<script>
document.addEventListener('DOMContentLoaded', () => {

    const slotButtons =
        document.querySelectorAll('.slot-modern:not(:disabled)');

    const selectedInput =
        document.getElementById('selectedSlotInput');

    const selectedText =
        document.getElementById('selectedSlotText');

    const selectedType =
        document.getElementById('selectedSlotType');

    const reserveButton =
        document.getElementById('reserveButton');


    slotButtons.forEach((button) => {

        button.addEventListener('click', () => {

            slotButtons.forEach((item) => {
                item.classList.remove('selected');
            });

            button.classList.add('selected');

            selectedInput.value =
                button.dataset.slotId;

            selectedText.textContent =
                button.dataset.slotNumber;

            selectedType.textContent =
                button.dataset.slotType;

            reserveButton.disabled = false;

        });

    });


    const vehicleInput =
        document.querySelector(
            'input[name="vehicle_number"]'
        );

    if (vehicleInput) {

        vehicleInput.addEventListener('input', () => {

            vehicleInput.value =
                vehicleInput.value
                    .toUpperCase()
                    .replace(/[^A-Z0-9 -]/g, '')
                    .slice(0, 20);

        });

    }

});
</script>

<?php require 'includes/footer.php'; ?>
