<?php
declare(strict_types=1);

require 'includes/auth.php';
requireAdmin();
require 'config/database.php';

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

// Total users
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM users
    WHERE role = 'User'
");
$totalUsers = (int) $stmt->fetchColumn();


// Total parking locations
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM parking_locations
");
$totalLocations = (int) $stmt->fetchColumn();


// Total parking slots
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM parking_slots
");
$totalSlots = (int) $stmt->fetchColumn();


// Confirmed reservations
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM reservations
    WHERE status = 'Confirmed'
");
$activeReservations = (int) $stmt->fetchColumn();


// Cancelled reservations
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM reservations
    WHERE status = 'Cancelled'
");
$cancelledReservations = (int) $stmt->fetchColumn();


// Total revenue from confirmed reservations
$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM reservations
    WHERE status = 'Confirmed'
");
$totalRevenue = (float) $stmt->fetchColumn();


// Available slots
$stmt = $pdo->query("
    SELECT COALESCE(SUM(available_slots), 0)
    FROM parking_locations
    WHERE status = 'Active'
");
$availableSlots = (int) $stmt->fetchColumn();


// Recent reservations
$stmt = $pdo->query("
    SELECT
        r.id,
        r.booking_code,
        r.vehicle_number,
        r.vehicle_type,
        r.start_time,
        r.end_time,
        r.amount,
        r.status,
        r.created_at,

        u.name AS user_name,

        p.name AS parking_name,

        s.slot_number

    FROM reservations r

    INNER JOIN users u
        ON r.user_id = u.id

    INNER JOIN parking_locations p
        ON r.location_id = p.id

    INNER JOIN parking_slots s
        ON r.slot_id = s.id

    ORDER BY r.created_at DESC

    LIMIT 10
");

$recentReservations = $stmt->fetchAll();

?>

<?php require 'includes/header.php'; ?>

<style>

/* =========================================================
   ADMIN DASHBOARD
   ========================================================= */

.admin-page {
    max-width: 1200px;
    margin: 40px auto;
}

.admin-heading {
    margin-bottom: 30px;
}

.admin-heading h1 {
    margin-bottom: 8px;
}

.admin-heading p {
    color: #6b7280;
}


/* =========================================================
   STATISTICS
   ========================================================= */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 5px 18px rgba(0,0,0,0.05);
}

.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    background: #dcfce7;
    color: #166534;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 14px;
}

.stat-title {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 5px;
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
}


/* =========================================================
   QUICK ACTIONS
   ========================================================= */

.section {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 5px 18px rgba(0,0,0,0.04);
}

.section h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.quick-action {
    text-decoration: none;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 18px;
    color: #111827;
    transition: 0.2s;
}

.quick-action:hover {
    border-color: #166534;
    background: #f0fdf4;
    transform: translateY(-2px);
}

.quick-action strong {
    display: block;
    color: #166534;
    margin-bottom: 5px;
}

.quick-action span {
    font-size: 13px;
    color: #6b7280;
}


/* =========================================================
   RESERVATION TABLE
   ========================================================= */

.table-wrapper {
    overflow-x: auto;
}

.reservation-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.reservation-table th {
    text-align: left;
    background: #f9fafb;
    padding: 13px;
    font-size: 13px;
    color: #4b5563;
    border-bottom: 1px solid #e5e7eb;
}

.reservation-table td {
    padding: 14px 13px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.booking-code {
    font-weight: 700;
    color: #166534;
}

.status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-confirmed {
    background: #dcfce7;
    color: #166534;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.status-completed {
    background: #e0e7ff;
    color: #3730a3;
}


/* =========================================================
   SUMMARY BOXES
   ========================================================= */

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.summary-box {
    padding: 20px;
    border-radius: 10px;
    background: #f9fafb;
}

.summary-box h3 {
    margin: 0 0 8px;
    color: #166534;
}

.summary-box p {
    margin: 0;
    color: #6b7280;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 950px) {

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .quick-actions {
        grid-template-columns: repeat(2, 1fr);
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {

    .admin-page {
        margin: 25px 12px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .quick-actions {
        grid-template-columns: 1fr;
    }

    .section {
        padding: 18px;
    }
}

</style>


<div class="container admin-page">

    <!-- =====================================================
         PAGE HEADING
         ===================================================== -->

    <div class="admin-heading">

        <h1>Admin Dashboard</h1>

        <p>
            Manage ParkEasy users, parking locations, slots and reservations.
        </p>

    </div>


    <!-- =====================================================
         STATISTICS
         ===================================================== -->

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-icon">👥</div>

            <div class="stat-title">
                Registered Users
            </div>

            <div class="stat-number">
                <?= $totalUsers ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">🅿️</div>

            <div class="stat-title">
                Parking Locations
            </div>

            <div class="stat-number">
                <?= $totalLocations ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">🚗</div>

            <div class="stat-title">
                Total Parking Slots
            </div>

            <div class="stat-number">
                <?= $totalSlots ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">📅</div>

            <div class="stat-title">
                Active Reservations
            </div>

            <div class="stat-number">
                <?= $activeReservations ?>
            </div>

        </div>

    </div>


    <!-- =====================================================
         SECONDARY STATISTICS
         ===================================================== -->

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-icon">🟢</div>

            <div class="stat-title">
                Available Slots
            </div>

            <div class="stat-number">
                <?= $availableSlots ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">❌</div>

            <div class="stat-title">
                Cancelled Reservations
            </div>

            <div class="stat-number">
                <?= $cancelledReservations ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">₹</div>

            <div class="stat-title">
                Confirmed Revenue
            </div>

            <div class="stat-number">
                ₹<?= number_format($totalRevenue, 2) ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">📊</div>

            <div class="stat-title">
                Reservation Records
            </div>

            <div class="stat-number">
                <?= count($recentReservations) ?>
            </div>

        </div>

    </div>


    <!-- =====================================================
         QUICK ACTIONS
         ===================================================== -->

    <div class="section">

        <h2>Quick Actions</h2>

        <div class="quick-actions">

            <a href="admin-parking.php" class="quick-action">

                <strong>Manage Parking</strong>

                <span>
                    Add, edit and manage parking locations.
                </span>

            </a>


            <a href="admin-slots.php" class="quick-action">

                <strong>Manage Slots</strong>

                <span>
                    View and manage parking slots.
                </span>

            </a>


            <a href="admin-reservations.php" class="quick-action">

                <strong>Reservations</strong>

                <span>
                    View and manage customer bookings.
                </span>

            </a>


            <a href="admin-users.php" class="quick-action">

                <strong>Users</strong>

                <span>
                    View registered ParkEasy users.
                </span>

            </a>

            <a href="admin-users.php" class="btn btn-secondary">
                👥 Manage Users
            </a>

        </div>

    </div>


    <!-- =====================================================
         SYSTEM SUMMARY
         ===================================================== -->

    <div class="section">

        <h2>System Summary</h2>

        <div class="summary-grid">

            <div class="summary-box">

                <h3><?= $totalLocations ?></h3>

                <p>
                    Parking locations currently registered in the system.
                </p>

            </div>


            <div class="summary-box">

                <h3><?= $availableSlots ?></h3>

                <p>
                    Parking slots currently marked as available.
                </p>

            </div>


            <div class="summary-box">

                <h3>₹<?= number_format($totalRevenue, 2) ?></h3>

                <p>
                    Revenue recorded from confirmed reservations.
                </p>

            </div>

        </div>

    </div>


    <!-- =====================================================
         RECENT RESERVATIONS
         ===================================================== -->

    <div class="section">

        <h2>Recent Reservations</h2>

        <?php if (!$recentReservations): ?>

            <p>
                No reservations have been made yet.
            </p>

        <?php else: ?>

            <div class="table-wrapper">

                <table class="reservation-table">

                    <thead>

                        <tr>

                            <th>Booking Code</th>

                            <th>User</th>

                            <th>Parking</th>

                            <th>Slot</th>

                            <th>Vehicle</th>

                            <th>Start Time</th>

                            <th>Amount</th>

                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($recentReservations as $reservation): ?>

                            <?php
                            $start = new DateTime(
                                $reservation['start_time']
                            );

                            $statusClass = 'status-confirmed';

                            if ($reservation['status'] === 'Cancelled') {
                                $statusClass = 'status-cancelled';
                            } elseif ($reservation['status'] === 'Completed') {
                                $statusClass = 'status-completed';
                            }
                            ?>

                            <tr>

                                <td class="booking-code">
                                    <?= htmlspecialchars(
                                        $reservation['booking_code']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $reservation['user_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $reservation['parking_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $reservation['slot_number']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $reservation['vehicle_number']
                                    ) ?>
                                </td>

                                <td>
                                    <?= $start->format('d M Y, h:i A') ?>
                                </td>

                                <td>
                                    ₹<?= number_format(
                                        (float)$reservation['amount'],
                                        2
                                    ) ?>
                                </td>

                                <td>

                                    <span class="status <?= $statusClass ?>">

                                        <?= htmlspecialchars(
                                            $reservation['status']
                                        ) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php require 'includes/footer.php'; ?>