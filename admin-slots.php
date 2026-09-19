<?php
declare(strict_types=1);
require 'includes/admin-tools.php';

$editing = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_slot') {
        $id = (int) ($_POST['id'] ?? 0);
        $locationId = (int) ($_POST['location_id'] ?? 0);
        $slotNumber = strtoupper(trim($_POST['slot_number'] ?? ''));
        $slotType = $_POST['slot_type'] ?? 'Regular';
        $status = $_POST['status'] ?? 'Available';

        if ($locationId < 1) $errors[] = 'Select a parking location.';
        if ($slotNumber === '' || strlen($slotNumber) > 20) $errors[] = 'Enter a valid slot number.';
        if (!in_array($slotType, ['Regular', 'Accessible', 'EV'], true)) $errors[] = 'Invalid slot type.';
        if (!in_array($status, ['Available', 'Maintenance'], true)) $errors[] = 'Invalid slot status.';

        if (!$errors) {
            try {
                $stmt = $pdo->prepare('SELECT id FROM parking_locations WHERE id = ?');
                $stmt->execute([$locationId]);
                if (!$stmt->fetch()) {
                    $errors[] = 'Parking location not found.';
                } elseif ($id > 0) {
                    $stmt = $pdo->prepare('SELECT location_id, slot_number, status FROM parking_slots WHERE id=?');
                    $stmt->execute([$id]);
                    $current = $stmt->fetch();
                    if (!$current) {
                        $errors[] = 'Parking slot not found.';
                    } else {
                        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE slot_id=?');
                        $stmt->execute([$id]);
                        $reservationCount = (int) $stmt->fetchColumn();
                        if ($reservationCount > 0 && ((int) $current['location_id'] !== $locationId || $current['slot_number'] !== $slotNumber)) {
                            $errors[] = 'Location and slot number cannot be changed after reservation history exists for this slot.';
                        } else {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE slot_id=? AND status='Confirmed' AND end_time > NOW()");
                            $stmt->execute([$id]);
                            $futureConfirmed = (int) $stmt->fetchColumn();
                            if ($status === 'Maintenance' && $futureConfirmed > 0) {
                                $errors[] = 'This slot has an upcoming confirmed reservation and cannot be moved to Maintenance yet.';
                            } else {
                                $stmt = $pdo->prepare('UPDATE parking_slots SET location_id=?, slot_number=?, slot_type=?, status=? WHERE id=?');
                                $stmt->execute([$locationId, $slotNumber, $slotType, $status, $id]);
                                admin_flash_set('success', 'Parking slot updated successfully.');
                                header('Location: admin-slots.php');
                                exit;
                            }
                        }
                    }
                } else {
                    $stmt = $pdo->prepare('INSERT INTO parking_slots (location_id,slot_number,slot_type,status) VALUES (?,?,?,?)');
                    $stmt->execute([$locationId, $slotNumber, $slotType, $status]);
                    admin_flash_set('success', 'Parking slot added successfully.');
                    header('Location: admin-slots.php');
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = 'Could not save the slot. The slot number may already exist at this location.';
            }
        }
        $editing = ['id'=>$id,'location_id'=>$locationId,'slot_number'=>$slotNumber,'slot_type'=>$slotType,'status'=>$status];
    }

    if ($action === 'delete_slot') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE slot_id = ?');
        $stmt->execute([$id]);
        $reservationCount = (int) $stmt->fetchColumn();
        if ($reservationCount > 0) {
            admin_flash_set('error', 'This slot cannot be deleted because it has reservation history. Set the slot to Maintenance instead.');
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM parking_slots WHERE id = ?');
                $stmt->execute([$id]);
                admin_flash_set('success', 'Parking slot deleted successfully.');
            } catch (PDOException $e) {
                admin_flash_set('error', 'The parking slot could not be deleted.');
            }
        }
        header('Location: admin-slots.php');
        exit;
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM parking_slots WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$locations = $pdo->query('SELECT id,name,city,status FROM parking_locations ORDER BY city,name')->fetchAll();
$locationFilter = (int) ($_GET['location'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];
if ($locationFilter > 0) { $where[] = 's.location_id = ?'; $params[] = $locationFilter; }
if (in_array($statusFilter, ['Available','Maintenance'], true)) { $where[] = 's.status = ?'; $params[] = $statusFilter; }
$sql = 'SELECT s.*, p.name AS parking_name, p.city, (SELECT COUNT(*) FROM reservations r WHERE r.slot_id=s.id) AS reservation_count FROM parking_slots s INNER JOIN parking_locations p ON p.id=s.location_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.city,p.name,s.slot_number';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$slots = $stmt->fetchAll();
$flash = admin_flash_get();
$pageTitle = 'Admin – Parking Slots | ParkNexa';
require 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">
<section class="admin-shell">
    <div class="admin-head">
        <div><span class="eyebrow">ADMIN CRUD</span><h1>Parking Slots</h1><p>Manage individual parking spaces, slot types and maintenance status.</p></div>
        <div class="admin-actions"><a class="btn btn-outline" href="admin-parking.php">Parking Locations</a><a class="btn" href="admin-reservations.php">Reservations →</a></div>
    </div>
    <?php if ($flash): ?><div class="admin-alert <?= admin_h($flash['type']) ?>"><?= admin_h($flash['message']) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="admin-alert error"><?php foreach ($errors as $error): ?><div>• <?= admin_h($error) ?></div><?php endforeach; ?></div><?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-title"><div><h2><?= $editing ? 'Edit Parking Slot' : 'Add Parking Slot' ?></h2><p><?= $editing ? 'Update this slot record.' : 'Create a new slot under a parking location.' ?></p></div><?php if ($editing): ?><a class="btn btn-outline btn-sm" href="admin-slots.php">Cancel Edit</a><?php endif; ?></div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= admin_csrf() ?>"><input type="hidden" name="action" value="save_slot"><input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <div class="admin-grid-2">
                <div class="admin-field"><span>Parking Location</span><select required name="location_id"><option value="">Select location</option><?php foreach ($locations as $l): ?><option value="<?= (int) $l['id'] ?>" <?= ((int) ($editing['location_id'] ?? 0) === (int) $l['id']) ? 'selected' : '' ?>><?= admin_h($l['name'] . ' — ' . $l['city']) ?><?= $l['status'] === 'Inactive' ? ' (Inactive)' : '' ?></option><?php endforeach; ?></select></div>
                <div class="admin-field"><span>Slot Number</span><input required maxlength="20" name="slot_number" value="<?= admin_h($editing['slot_number'] ?? '') ?>" placeholder="e.g. A-21"></div>
                <div class="admin-field"><span>Slot Type</span><select name="slot_type"><option value="Regular" <?= (($editing['slot_type'] ?? 'Regular') === 'Regular') ? 'selected' : '' ?>>Regular</option><option value="Accessible" <?= (($editing['slot_type'] ?? '') === 'Accessible') ? 'selected' : '' ?>>Accessible</option><option value="EV" <?= (($editing['slot_type'] ?? '') === 'EV') ? 'selected' : '' ?>>EV</option></select></div>
                <div class="admin-field"><span>Status</span><select name="status"><option value="Available" <?= (($editing['status'] ?? 'Available') === 'Available') ? 'selected' : '' ?>>Available</option><option value="Maintenance" <?= (($editing['status'] ?? '') === 'Maintenance') ? 'selected' : '' ?>>Maintenance</option></select></div>
            </div>
            <div class="admin-form-actions"><button class="btn" type="submit"><?= $editing ? 'Update Slot' : 'Add Slot' ?></button><span class="admin-note">Slots with reservation history are protected from deletion to preserve booking records.</span></div>
        </form>
    </div>

    <div class="admin-card">
        <div class="admin-card-title"><div><h2>Slot Records</h2><p>Filter slots by location and status.</p></div><span class="admin-count"><?= count($slots) ?> slot(s)</span></div>
        <form class="admin-toolbar" method="get">
            <div class="admin-field"><span>Location</span><select name="location"><option value="0">All locations</option><?php foreach ($locations as $l): ?><option value="<?= (int) $l['id'] ?>" <?= $locationFilter === (int) $l['id'] ? 'selected' : '' ?>><?= admin_h($l['name']) ?></option><?php endforeach; ?></select></div>
            <div class="admin-field"><span>Status</span><select name="status"><option value="">All statuses</option><option value="Available" <?= $statusFilter === 'Available' ? 'selected' : '' ?>>Available</option><option value="Maintenance" <?= $statusFilter === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option></select></div>
            <div class="admin-form-actions" style="margin-top:0"><button class="btn btn-sm" type="submit">Filter</button><a class="btn btn-sm btn-outline" href="admin-slots.php">Reset</a></div>
        </form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Location</th><th>Slot</th><th>Type</th><th>Status</th><th>Reservations</th><th>Actions</th></tr></thead><tbody>
        <?php if (!$slots): ?><tr><td colspan="6"><div class="admin-empty"><strong>No slots match your filter.</strong>Try another location or status.</div></td></tr>
        <?php else: foreach ($slots as $s): ?><tr>
            <td><div class="admin-strong"><?= admin_h($s['parking_name']) ?></div><div class="admin-muted"><?= admin_h($s['city']) ?></div></td>
            <td class="admin-strong"><?= admin_h($s['slot_number']) ?></td>
            <td><?= admin_h($s['slot_type']) ?></td>
            <td><span class="admin-badge <?= $s['status'] === 'Available' ? 'available' : 'maintenance' ?>"><?= admin_h($s['status']) ?></span></td>
            <td><?= (int) $s['reservation_count'] ?></td>
            <td><div class="admin-inline"><a class="btn btn-sm btn-outline" href="admin-slots.php?edit=<?= (int) $s['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this slot?');"><input type="hidden" name="csrf_token" value="<?= admin_csrf() ?>"><input type="hidden" name="action" value="delete_slot"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button class="btn btn-sm admin-button-danger" type="submit">Delete</button></form></div></td>
        </tr><?php endforeach; endif; ?>
        </tbody></table></div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
