<?php
declare(strict_types=1);

require 'includes/auth.php';
requireLogin();

require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: search-parking.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Get form data
|--------------------------------------------------------------------------
*/

$locationId = (int)($_POST['location_id'] ?? 0);
$slotId     = (int)($_POST['slot_id'] ?? 0);

$vehicleNumber = strtoupper(trim($_POST['vehicle_number'] ?? ''));
$vehicleType   = trim($_POST['vehicle_type'] ?? '');

$startTime = trim($_POST['start_time'] ?? '');
$endTime   = trim($_POST['end_time'] ?? '');

$userId = (int)$_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Basic validation
|--------------------------------------------------------------------------
*/

$errors = [];

if ($locationId <= 0) {
    $errors[] = 'Invalid parking location.';
}

if ($slotId <= 0) {
    $errors[] = 'Please select a parking slot.';
}

if (!preg_match('/^[A-Z]{2}[0-9]{1,2}[A-Z]{1,3}[0-9]{3,4}$/', $vehicleNumber)) {
    $errors[] = 'Enter a valid vehicle number. Example: DL01AB1234';
}

$allowedVehicleTypes = [
    'Car',
    'Bike',
    'SUV',
    'Other'
];

if (!in_array($vehicleType, $allowedVehicleTypes, true)) {
    $errors[] = 'Please select a valid vehicle type.';
}


/*
|--------------------------------------------------------------------------
| Validate date/time
|--------------------------------------------------------------------------
*/

$startDateTime = DateTime::createFromFormat(
    'Y-m-d H:i',
    $startTime
);

$endDateTime = DateTime::createFromFormat(
    'Y-m-d H:i',
    $endTime
);

if (!$startDateTime || !$endDateTime) {

    $errors[] = 'Invalid reservation date or time.';

} else {

    $now = new DateTime();

    if ($startDateTime <= $now) {
        $errors[] = 'Reservation must start in the future.';
    }

    if ($endDateTime <= $startDateTime) {
        $errors[] = 'End time must be after start time.';
    }
}


/*
|--------------------------------------------------------------------------
| Stop if validation errors exist
|--------------------------------------------------------------------------
*/

if ($errors) {

    require 'includes/header.php';
    ?>

    <section class="container section">

        <div class="card" style="max-width:650px;margin:auto;padding:30px;">

            <span class="eyebrow">
                RESERVATION ERROR
            </span>

            <h1>Unable to create reservation</h1>

            <?php foreach ($errors as $error): ?>

                <div class="alert error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endforeach; ?>

            <a
                href="javascript:history.back()"
                class="btn"
            >
                Go Back
            </a>

        </div>

    </section>

    <?php

    require 'includes/footer.php';
    exit;
}


/*
|--------------------------------------------------------------------------
| Get parking location
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM parking_locations
    WHERE id = ?
      AND status = 'Active'
");

$stmt->execute([$locationId]);

$parking = $stmt->fetch();

if (!$parking) {
    exit('Parking location not found.');
}


/*
|--------------------------------------------------------------------------
| Check selected slot
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM parking_slots
    WHERE id = ?
      AND location_id = ?
");

$stmt->execute([
    $slotId,
    $locationId
]);

$slot = $stmt->fetch();

if (!$slot) {
    exit('Invalid parking slot.');
}

if ($slot['status'] !== 'Available') {
    exit('This parking slot is currently unavailable.');
}


/*
|--------------------------------------------------------------------------
| Check overlapping reservation
|--------------------------------------------------------------------------
|
| A slot cannot be booked when another confirmed reservation
| overlaps the requested start/end time.
|
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM reservations
    WHERE slot_id = ?
      AND status = 'Confirmed'
      AND start_time < ?
      AND end_time > ?
    LIMIT 1
");

$stmt->execute([
    $slotId,
    $endDateTime->format('Y-m-d H:i:s'),
    $startDateTime->format('Y-m-d H:i:s')
]);

$existingReservation = $stmt->fetch();

if ($existingReservation) {

    require 'includes/header.php';
    ?>

    <section class="container section">

        <div class="card" style="max-width:650px;margin:auto;padding:30px;">

            <span class="eyebrow">
                SLOT UNAVAILABLE
            </span>

            <h1>Sorry, this slot is already reserved.</h1>

            <p class="muted">
                Please go back and select another available parking slot.
            </p>

            <a
                href="javascript:history.back()"
                class="btn"
            >
                Choose Another Slot
            </a>

        </div>

    </section>

    <?php

    require 'includes/footer.php';
    exit;
}


/*
|--------------------------------------------------------------------------
| Calculate duration
|--------------------------------------------------------------------------
*/

$seconds =
    $endDateTime->getTimestamp()
    -
    $startDateTime->getTimestamp();

$hours = ceil($seconds / 3600);

if ($hours <= 0) {
    exit('Invalid reservation duration.');
}


/*
|--------------------------------------------------------------------------
| Calculate total fare
|--------------------------------------------------------------------------
*/

$hourlyRate = (float)$parking['hourly_rate'];

$totalFare = $hours * $hourlyRate;


/*
|--------------------------------------------------------------------------
| Generate unique booking code
|--------------------------------------------------------------------------
*/

function generateBookingCode(PDO $pdo): string
{
    do {

        $code =
            'PE'
            .
            strtoupper(
                substr(
                    bin2hex(random_bytes(5)),
                    0,
                    8
                )
            );

        $stmt = $pdo->prepare("
            SELECT id
            FROM reservations
            WHERE booking_code = ?
        ");

        $stmt->execute([$code]);

    } while ($stmt->fetch());

    return $code;
}


/*
|--------------------------------------------------------------------------
| Insert reservation
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    $bookingCode = generateBookingCode($pdo);

    $stmt = $pdo->prepare("
        INSERT INTO reservations
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
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed'
        )
    ");

    $stmt->execute([
        $bookingCode,
        $userId,
        $locationId,
        $slotId,
        $vehicleNumber,
        $vehicleType,
        $startDateTime->format('Y-m-d H:i:s'),
        $endDateTime->format('Y-m-d H:i:s'),
        $totalFare
    ]);

    /*
    |--------------------------------------------------------------------------
    | Update available slot count
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE parking_locations
        SET available_slots =
            CASE
                WHEN available_slots > 0
                THEN available_slots - 1
                ELSE 0
            END
        WHERE id = ?
    ");

    $stmt->execute([$locationId]);

    $pdo->commit();

    /*
    |--------------------------------------------------------------------------
    | Redirect to confirmation
    |--------------------------------------------------------------------------
    */

    header(
        'Location: booking-confirmation.php?code='
        . urlencode($bookingCode)
    );

    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    exit(
        'Reservation failed: '
        . htmlspecialchars($e->getMessage())
    );
}