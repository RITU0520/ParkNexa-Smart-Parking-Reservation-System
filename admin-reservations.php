<?php
declare(strict_types=1);
require 'includes/admin-tools.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        if (!in_array($newStatus, ['Confirmed','Cancelled','Completed'], true)) {
            admin_flash_set('error', 'Invalid reservation status.');
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT id,location_id,status FROM reservations WHERE id=? FOR UPDATE');
                $stmt->execute([$id]);
                $reservation = $stmt->fetch();
                if (!$reservation) {
                    throw new RuntimeException('Reservation not found.');
                }
                $oldStatus = $reservation['status'];
                if ($oldStatus !== $newStatus) {
                    if ($oldStatus === 'Confirmed' && $newStatus === 'Cancelled') {
                        $stmt = $pdo->prepare('UPDATE parking_locations SET available_slots = LEAST(total_slots, available_slots + 1) WHERE id=?');
                        $stmt->execute([(int) $reservation['location_id']]);
                    } elseif ($oldStatus === 'Cancelled' && $newStatus === 'Confirmed') {
                        $stmt = $pdo->prepare('SELECT available_slots FROM parking_locations WHERE id=? FOR UPDATE');
                        $stmt->execute([(int) $reservation['location_id']]);
                        $available = (int) $stmt->fetchColumn();
                        if ($available < 1) {
                            throw new RuntimeException('No availability remains at this location, so the reservation cannot be re-confirmed.');
                        }
                        $stmt = $pdo->prepare('UPDATE parking_locations SET available_slots = available_slots - 1 WHERE id=?');
                        $stmt->execute([(int) $reservation['location_id']]);
                    }
                    $stmt = $pdo->prepare('UPDATE reservations SET status=? WHERE id=?');
                    $stmt->execute([$newStatus, $id]);
                }
                $pdo->commit();
                admin_flash_set('success', 'Reservation status updated successfully.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                admin_flash_set('error', $e instanceof RuntimeException ? $e->getMessage() : 'Could not update the reservation status.');
            }
        }
        header('Location: admin-reservations.php');
        exit;
    }
}

$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(r.booking_code LIKE ? OR r.vehicle_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR p.name LIKE ? OR s.slot_number LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like,$like,$like,$like,$like,$like];
}
if (in_array($statusFilter, ['Confirmed','Cancelled','Completed'], true)) { $where[] = 'r.status = ?'; $params[] = $statusFilter; }
$sql = 'SELECT r.*, u.name AS user_name, u.email AS user_email, p.name AS parking_name, p.city, s.slot_number FROM reservations r INNER JOIN users u ON u.id=r.user_id INNER JOIN parking_locations p ON p.id=r.location_id INNER JOIN parking_slots s ON s.id=r.slot_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY r.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();
$flash = admin_flash_get();
$pageTitle = 'Admin – Reservations | ParkNexa';
require 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">
<section class="admin-shell">
    <div class="admin-head">
        <div><span class="eyebrow">ADMIN OPERATIONS</span><h1>Reservations</h1><p>Review bookings and update their operational status while keeping availability in sync.</p></div>
        <div class="admin-actions"><a class="btn btn-outline" href="admin-dashboard.php">Admin Dashboard</a><a class="btn" href="admin-users.php">Manage Users →</a></div>
    </div>
    <?php if ($flash): ?><div class="admin-alert <?= admin_h($flash['type']) ?>"><?= admin_h($flash['message']) ?></div><?php endif; ?>
    <div class="admin-card">
        <div class="admin-card-title"><div><h2>Reservation Records</h2><p>Search by booking code, vehicle, user, parking location or slot.</p></div><span class="admin-count"><?= count($reservations) ?> record(s)</span></div>
        <form class="admin-toolbar" method="get">
            <div class="admin-field wide"><span>Search</span><input name="q" value="<?= admin_h($q) ?>" placeholder="Booking code, vehicle, user, parking..."></div>
            <div class="admin-field"><span>Status</span><select name="status"><option value="">All statuses</option><option value="Confirmed" <?= $statusFilter === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option><option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option><option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option></select></div>
            <div class="admin-form-actions" style="margin-top:0"><button class="btn btn-sm" type="submit">Search</button><a class="btn btn-sm btn-outline" href="admin-reservations.php">Reset</a></div>
        </form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Booking</th><th>User</th><th>Parking</th><th>Vehicle</th><th>Timing</th><th>Amount</th><th>Status</th><th>Update</th></tr></thead><tbody>
        <?php if (!$reservations): ?><tr><td colspan="8"><div class="admin-empty"><strong>No reservations found.</strong>Bookings will appear here after users reserve a slot.</div></td></tr>
        <?php else: foreach ($reservations as $r): ?>
            <tr>
                <td><div class="admin-code"><?= admin_h($r['booking_code']) ?></div><div class="admin-muted">#<?= (int) $r['id'] ?></div></td>
                <td><div class="admin-strong"><?= admin_h($r['user_name']) ?></div><div class="admin-muted"><?= admin_h($r['user_email']) ?></div></td>
                <td><div class="admin-strong"><?= admin_h($r['parking_name']) ?></div><div class="admin-muted"><?= admin_h($r['city']) ?> · Slot <?= admin_h($r['slot_number']) ?></div></td>
                <td><div class="admin-strong"><?= admin_h($r['vehicle_number']) ?></div><div class="admin-muted"><?= admin_h($r['vehicle_type']) ?></div></td>
                <td><div><?= admin_h(date('d M Y, h:i A', strtotime($r['start_time']))) ?></div><div class="admin-muted">to <?= admin_h(date('d M Y, h:i A', strtotime($r['end_time']))) ?></div></td>
                <td>₹<?= number_format((float) $r['amount'], 2) ?></td>
                <td><span class="admin-badge <?= strtolower($r['status']) ?>"><?= admin_h($r['status']) ?></span></td>
                <td><form method="post" class="admin-inline"><input type="hidden" name="csrf_token" value="<?= admin_csrf() ?>"><input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><select name="status"><option value="Confirmed" <?= $r['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option><option value="Cancelled" <?= $r['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option><option value="Completed" <?= $r['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option></select><button class="btn btn-sm" type="submit">Save</button></form></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody></table></div>
    </div>
    <div class="admin-card"><div class="admin-card-title"><div><h2>Operational Note</h2><p>How status changes affect availability.</p></div></div><div class="admin-note">Moving a reservation from <strong>Confirmed → Cancelled</strong> returns one slot to the location. Moving <strong>Cancelled → Confirmed</strong> consumes one available slot and is blocked when no availability remains. Completed records do not change the stored availability count.</div></div>
</section>
<?php require 'includes/footer.php'; ?>
