<?php
declare(strict_types=1);
require 'includes/admin-tools.php';

$editing = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_location') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $total = (int) ($_POST['total_slots'] ?? 0);
        $rate = (float) ($_POST['hourly_rate'] ?? 0);
        $opening = $_POST['opening_time'] ?? '';
        $closing = $_POST['closing_time'] ?? '';
        $status = $_POST['status'] ?? 'Active';

        if ($name === '') $errors[] = 'Parking name is required.';
        if ($address === '') $errors[] = 'Address is required.';
        if ($city === '') $errors[] = 'City is required.';
        if ($total < 1) $errors[] = 'Total slots must be at least 1.';
        if ($rate <= 0) $errors[] = 'Hourly rate must be greater than 0.';
        if ($opening === '' || $closing === '') $errors[] = 'Opening and closing times are required.';
        if ($opening !== '' && $closing !== '' && $opening >= $closing) $errors[] = 'Closing time must be later than opening time.';
        if (!in_array($status, ['Active', 'Inactive'], true)) $errors[] = 'Invalid parking status.';

        if (!$errors) {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare('SELECT total_slots, available_slots FROM parking_locations WHERE id = ?');
                    $stmt->execute([$id]);
                    $old = $stmt->fetch();
                    if (!$old) {
                        $errors[] = 'Parking location not found.';
                    } else {
                        $oldTotal = (int) $old['total_slots'];
                        $oldAvailable = (int) $old['available_slots'];
                        $occupied = max(0, $oldTotal - $oldAvailable);
                        if ($total < $occupied) {
                            $errors[] = 'Total slots cannot be reduced below the number of currently occupied slots (' . $occupied . ').';
                        } else {
                            $available = max(0, min($total, $oldAvailable + ($total - $oldTotal)));
                            $stmt = $pdo->prepare('UPDATE parking_locations SET name=?, address=?, city=?, total_slots=?, available_slots=?, hourly_rate=?, opening_time=?, closing_time=?, status=? WHERE id=?');
                            $stmt->execute([$name, $address, $city, $total, $available, $rate, $opening, $closing, $status, $id]);
                            admin_flash_set('success', 'Parking location updated successfully.');
                            header('Location: admin-parking.php');
                            exit;
                        }
                    }
                } else {
                    $stmt = $pdo->prepare('INSERT INTO parking_locations (name,address,city,total_slots,available_slots,hourly_rate,opening_time,closing_time,status) VALUES (?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([$name, $address, $city, $total, $total, $rate, $opening, $closing, $status]);
                    admin_flash_set('success', 'Parking location added successfully.');
                    header('Location: admin-parking.php');
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = 'Could not save the parking location. Please check the entered values.';
            }
        }

        $editing = [
            'id' => $id, 'name' => $name, 'address' => $address, 'city' => $city,
            'total_slots' => $total, 'hourly_rate' => $rate, 'opening_time' => $opening,
            'closing_time' => $closing, 'status' => $status
        ];
    }

    if ($action === 'delete_location') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE location_id = ?');
        $stmt->execute([$id]);
        $reservationCount = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM parking_slots WHERE location_id = ?');
        $stmt->execute([$id]);
        $slotCount = (int) $stmt->fetchColumn();
        if ($reservationCount > 0) {
            admin_flash_set('error', 'This location cannot be deleted because it has reservation history. Set it to Inactive instead.');
        } elseif ($slotCount > 0) {
            admin_flash_set('error', 'This location still has ' . $slotCount . ' configured slot(s). Delete those slots first, or set the location to Inactive.');
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM parking_locations WHERE id = ?');
                $stmt->execute([$id]);
                admin_flash_set('success', 'Parking location deleted successfully.');
            } catch (PDOException $e) {
                admin_flash_set('error', 'The parking location could not be deleted.');
            }
        }
        header('Location: admin-parking.php');
        exit;
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM parking_locations WHERE id = ?');
    $stmt->execute([$id]);
    $editing = $stmt->fetch() ?: null;
}

$stmt = $pdo->query('SELECT p.*, (SELECT COUNT(*) FROM parking_slots s WHERE s.location_id = p.id) AS slot_count, (SELECT COUNT(*) FROM reservations r WHERE r.location_id = p.id) AS reservation_count FROM parking_locations p ORDER BY p.created_at DESC');
$locations = $stmt->fetchAll();
$flash = admin_flash_get();
$pageTitle = 'Admin – Parking Locations | ParkNexa';
require 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">

<section class="admin-shell">
    <div class="admin-head">
        <div>
            <span class="eyebrow">ADMIN CRUD</span>
            <h1>Parking Locations</h1>
            <p>Create, view, update and remove parking locations used by the reservation system.</p>
        </div>
        <div class="admin-actions">
            <a class="btn btn-outline" href="admin-dashboard.php">Admin Dashboard</a>
            <a class="btn" href="admin-slots.php">Manage Slots →</a>
        </div>
    </div>

    <?php if ($flash): ?><div class="admin-alert <?= admin_h($flash['type']) ?>"><?= admin_h($flash['message']) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="admin-alert error"><?php foreach ($errors as $error): ?><div>• <?= admin_h($error) ?></div><?php endforeach; ?></div><?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-title">
            <div>
                <h2><?= $editing ? 'Edit Parking Location' : 'Add Parking Location' ?></h2>
                <p><?= $editing ? 'Update the selected location details.' : 'Create a new parking location and its initial availability.' ?></p>
            </div>
            <?php if ($editing): ?><a class="btn btn-outline btn-sm" href="admin-parking.php">Cancel Edit</a><?php endif; ?>
        </div>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= admin_csrf() ?>">
            <input type="hidden" name="action" value="save_location">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <div class="admin-grid-2">
                <div class="admin-field"><span>Parking Name</span><input required name="name" maxlength="120" value="<?= admin_h($editing['name'] ?? '') ?>"></div>
                <div class="admin-field"><span>City</span><input required name="city" maxlength="80" value="<?= admin_h($editing['city'] ?? '') ?>"></div>
                <div class="admin-field"><span>Address</span><input required name="address" maxlength="255" value="<?= admin_h($editing['address'] ?? '') ?>"></div>
                <div class="admin-field"><span>Total Slots</span><input required type="number" name="total_slots" min="1" value="<?= (int) ($editing['total_slots'] ?? 1) ?>"></div>
                <div class="admin-field"><span>Hourly Rate (₹)</span><input required type="number" name="hourly_rate" min="1" step="0.01" value="<?= admin_h((string) ($editing['hourly_rate'] ?? '40.00')) ?>"></div>
                <div class="admin-field"><span>Status</span><select name="status"><option value="Active" <?= (($editing['status'] ?? 'Active') === 'Active') ? 'selected' : '' ?>>Active</option><option value="Inactive" <?= (($editing['status'] ?? '') === 'Inactive') ? 'selected' : '' ?>>Inactive</option></select></div>
                <div class="admin-field"><span>Opening Time</span><input required type="time" name="opening_time" value="<?= admin_h(substr((string) ($editing['opening_time'] ?? '06:00:00'), 0, 5)) ?>"></div>
                <div class="admin-field"><span>Closing Time</span><input required type="time" name="closing_time" value="<?= admin_h(substr((string) ($editing['closing_time'] ?? '23:00:00'), 0, 5)) ?>"></div>
            </div>
            <div class="admin-form-actions">
                <button class="btn" type="submit"><?= $editing ? 'Update Location' : 'Add Location' ?></button>
                <span class="admin-note">When total slots are changed, current availability is adjusted to preserve existing occupancy as closely as possible.</span>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <div class="admin-card-title"><div><h2>All Parking Locations</h2><p>Current records in the <strong>parking_locations</strong> table.</p></div><span class="admin-count"><?= count($locations) ?> location(s)</span></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Location</th><th>City</th><th>Slots</th><th>Rate</th><th>Hours</th><th>Status</th><th>Reservations</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (!$locations): ?><tr><td colspan="8"><div class="admin-empty"><strong>No parking locations yet.</strong>Add your first location above.</div></td></tr>
                <?php else: foreach ($locations as $p): ?>
                    <tr>
                        <td><div class="admin-strong"><?= admin_h($p['name']) ?></div><div class="admin-muted"><?= admin_h($p['address']) ?></div></td>
                        <td><?= admin_h($p['city']) ?></td>
                        <td><div class="admin-strong"><?= (int) $p['available_slots'] ?> / <?= (int) $p['total_slots'] ?></div><div class="admin-muted"><?= (int) $p['slot_count'] ?> configured</div></td>
                        <td>₹<?= number_format((float) $p['hourly_rate'], 2) ?>/hr</td>
                        <td><?= admin_h(substr($p['opening_time'], 0, 5)) ?> – <?= admin_h(substr($p['closing_time'], 0, 5)) ?></td>
                        <td><span class="admin-badge <?= $p['status'] === 'Active' ? 'active' : 'inactive' ?>"><?= admin_h($p['status']) ?></span></td>
                        <td><?= (int) $p['reservation_count'] ?></td>
                        <td><div class="admin-inline"><a class="btn btn-sm btn-outline" href="admin-parking.php?edit=<?= (int) $p['id'] ?>">Edit</a><form method="post" class="no-print" onsubmit="return confirm('Delete this parking location?');"><input type="hidden" name="csrf_token" value="<?= admin_csrf() ?>"><input type="hidden" name="action" value="delete_location"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="btn btn-sm admin-button-danger" type="submit">Delete</button></form></div></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
