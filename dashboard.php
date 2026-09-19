<?php
declare(strict_types=1);

require 'includes/auth.php';
requireLogin();

/*
 * Keep the admin interface separate from the customer dashboard.
 * This also fixes the case where an admin account is opened directly
 * at dashboard.php.
 */
if (strtolower((string)($_SESSION['role'] ?? '')) === 'admin') {
    header('Location: admin-dashboard.php');
    exit;
}

require 'config/database.php';

$pageTitle = 'Dashboard | ParkNexa';

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_reservations,
        SUM(status = 'Confirmed') AS active_reservations
    FROM reservations
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$stats = $stmt->fetch() ?: [
    'total_reservations' => 0,
    'active_reservations' => 0,
];

require 'includes/header.php';
?>

<section class="container section">
    <span class="eyebrow">USER DASHBOARD</span>

    <h1>
        Welcome, <?= htmlspecialchars((string)($_SESSION['name'] ?? 'User')) ?> 👋
    </h1>

    <p>Manage your parking reservations and vehicle details.</p>

    <div class="stats-grid" style="margin-top:32px;">
        <article class="card">
            <span>Total Reservations</span>
            <h2 style="color:var(--plum); margin:12px 0 0;">
                <?= (int)$stats['total_reservations'] ?>
            </h2>
        </article>

        <article class="card">
            <span>Active Reservations</span>
            <h2 style="color:var(--orange); margin:12px 0 0;">
                <?= (int)$stats['active_reservations'] ?>
            </h2>
        </article>

        <article class="card">
            <span>Quick Action</span>
            <a class="btn" href="search-parking.php" style="display:block; margin-top:18px; text-align:center;">
                Reserve Slot
            </a>
        </article>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
