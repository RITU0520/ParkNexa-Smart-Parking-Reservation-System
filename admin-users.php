<?php
declare(strict_types=1);
require 'includes/admin-tools.php';

$editing = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_user') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
        $role = strtolower($_POST['role'] ?? 'user');
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '') $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (strlen($phone) < 10 || strlen($phone) > 15) $errors[] = 'Phone number must contain 10 to 15 digits.';
        if (!in_array($role, ['user','admin'], true)) $errors[] = 'Invalid role.';
        if ($id === 0 && strlen($password) < 8) $errors[] = 'New users need a password of at least 8 characters.';
        if ($id > 0 && $id === (int) $_SESSION['user_id'] && $role !== 'admin') $errors[] = 'You cannot remove admin access from your own account.';

        if (!$errors) {
            try {
                if ($id > 0) {
                    if ($password !== '') {
                        $stmt = $pdo->prepare('UPDATE users SET name=?,email=?,phone=?,role=?,password_hash=? WHERE id=?');
                        $stmt->execute([$name,$email,$phone,$role,password_hash($password,PASSWORD_DEFAULT),$id]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE users SET name=?,email=?,phone=?,role=? WHERE id=?');
                        $stmt->execute([$name,$email,$phone,$role,$id]);
                    }
                    if ($id === (int) $_SESSION['user_id']) {
                        $_SESSION['role'] = $role;
                        $_SESSION['name'] = $name;
                    }
                    admin_flash_set('success', 'User account updated successfully.');
                    header('Location: admin-users.php');
                    exit;
                } else {
                    $stmt = $pdo->prepare('INSERT INTO users (name,email,phone,password_hash,role) VALUES (?,?,?,?,?)');
                    $stmt->execute([$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT),$role]);
                    admin_flash_set('success', 'User account created successfully.');
                    header('Location: admin-users.php');
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = 'Could not save the user. The email address may already be in use.';
            }
        }

        $editing = ['id'=>$id,'name'=>$name,'email'=>$email,'phone'=>$phone,'role'=>$role];
    }

    if ($action === 'delete_user') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $_SESSION['user_id']) {
            admin_flash_set('error', 'You cannot delete your own account while logged in.');
        } else {
            $stmt = $pdo->prepare('SELECT role FROM users WHERE id=?');
            $stmt->execute([$id]);
            $targetRole = $stmt->fetchColumn();
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'");
            $adminCount = (int) $stmt->fetchColumn();
            if ($targetRole === 'admin' && $adminCount <= 1) {
                admin_flash_set('error', 'The last administrator account cannot be deleted. Create another admin first.');
                header('Location: admin-users.php');
                exit;
            }
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE user_id = ?');
            $stmt->execute([$id]);
            $reservationCount = (int) $stmt->fetchColumn();
            if ($reservationCount > 0) {
                admin_flash_set('error', 'This user cannot be deleted because reservation history exists for the account.');
            } else {
                try {
                    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->execute([$id]);
                    admin_flash_set('success', 'User deleted successfully.');
                } catch (PDOException $e) {
                    admin_flash_set('error', 'The user could not be deleted.');
                }
            }
        }
        header('Location: admin-users.php');
        exit;
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT id,name,email,phone,role FROM users WHERE id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$q = trim($_GET['q'] ?? '');
$roleFilter = strtolower($_GET['role'] ?? '');
$where = [];
$params = [];
if ($q !== '') { $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)'; $like = '%' . $q . '%'; $params = [$like,$like,$like]; }
if (in_array($roleFilter, ['user','admin'], true)) { $where[] = 'u.role = ?'; $params[] = $roleFilter; }
$sql = 'SELECT u.id,u.name,u.email,u.phone,u.role,u.created_at,(SELECT COUNT(*) FROM reservations r WHERE r.user_id=u.id) AS reservation_count FROM users u';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY u.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
$flash = admin_flash_get();
$pageTitle = 'Admin – Users | ParkNexa';
require 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">
<section class="admin-shell">
    <div class="admin-head">
        <div><span class="eyebrow">ADMIN CRUD</span><h1>Users</h1><p>Manage registered user accounts, contact details and application roles.</p></div>
        <div class="admin-actions"><a class="btn btn-outline" href="admin-dashboard.php">Admin Dashboard</a><a class="btn" href="admin-reservations.php">Reservations →</a></div>
    </div>
    <?php if ($flash): ?><div class="admin-alert <?= admin_h($flash['type']) ?>"><?= admin_h($flash['message']) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="admin-alert error"><?php foreach ($errors as $error): ?><div>• <?= admin_h($error) ?></div><?php endforeach; ?></div><?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-title"><div><h2><?= $editing ? 'Edit User' : 'Create User' ?></h2><p><?= $editing ? 'Update account details. Leave password blank to keep the current password.' : 'Create a user or admin account directly from the control panel.' ?></p></div><?php if ($editing): ?><a class="btn btn-outline btn-sm" href="admin-users.php">Cancel Edit</a><?php endif; ?></div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= admin_csrf() ?>"><input type="hidden" name="action" value="save_user"><input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <div class="admin-grid-2">
                <div class="admin-field"><span>Full Name</span><input required name="name" maxlength="100" value="<?= admin_h($editing['name'] ?? '') ?>"></div>
                <div class="admin-field"><span>Email</span><input required type="email" name="email" maxlength="150" value="<?= admin_h($editing['email'] ?? '') ?>"></div>
                <div class="admin-field"><span>Mobile</span><input required type="tel" name="phone" maxlength="15" value="<?= admin_h($editing['phone'] ?? '') ?>"></div>
                <div class="admin-field"><span>Role</span><select name="role"><option value="user" <?= (($editing['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>User</option><option value="admin" <?= (($editing['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option></select></div>
                <div class="admin-field"><span>Password <?= $editing ? '(optional)' : '' ?></span><input <?= $editing ? '' : 'required' ?> type="password" name="password" minlength="8" autocomplete="new-password" placeholder="At least 8 characters"></div>
            </div>
            <div class="admin-form-actions"><button class="btn" type="submit"><?= $editing ? 'Update User' : 'Create User' ?></button><span class="admin-note">Passwords are stored as hashes; never store plain-text passwords in the database.</span></div>
        </form>
    </div>

    <div class="admin-card">
        <div class="admin-card-title"><div><h2>User Records</h2><p>Search by name, email or phone and filter by role.</p></div><span class="admin-count"><?= count($users) ?> user(s)</span></div>
        <form class="admin-toolbar" method="get">
            <div class="admin-field wide"><span>Search</span><input name="q" value="<?= admin_h($q) ?>" placeholder="Name, email or phone"></div>
            <div class="admin-field"><span>Role</span><select name="role"><option value="">All roles</option><option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>User</option><option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option></select></div>
            <div class="admin-form-actions" style="margin-top:0"><button class="btn btn-sm" type="submit">Search</button><a class="btn btn-sm btn-outline" href="admin-users.php">Reset</a></div>
        </form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Name</th><th>Contact</th><th>Role</th><th>Reservations</th><th>Created</th><th>Actions</th></tr></thead><tbody>
        <?php if (!$users): ?><tr><td colspan="6"><div class="admin-empty"><strong>No users found.</strong>Try another search.</div></td></tr>
        <?php else: foreach ($users as $u): ?><tr>
            <td><div class="admin-strong"><?= admin_h($u['name']) ?></div><div class="admin-muted">#<?= (int) $u['id'] ?></div></td>
            <td><div><?= admin_h($u['email']) ?></div><div class="admin-muted"><?= admin_h($u['phone']) ?></div></td>
            <td><span class="admin-badge <?= $u['role'] === 'admin' ? 'admin' : 'user' ?>"><?= admin_h(ucfirst($u['role'])) ?></span></td>
            <td><?= (int) $u['reservation_count'] ?></td>
            <td><?= admin_h(date('d M Y', strtotime($u['created_at']))) ?></td>
            <td><div class="admin-inline"><a class="btn btn-sm btn-outline" href="admin-users.php?edit=<?= (int) $u['id'] ?>">Edit</a><?php if ((int) $u['id'] !== (int) $_SESSION['user_id']): ?><form method="post" onsubmit="return confirm('Delete this user account?');"><input type="hidden" name="csrf_token" value="<?= admin_csrf() ?>"><input type="hidden" name="action" value="delete_user"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button class="btn btn-sm admin-button-danger" type="submit">Delete</button></form><?php endif; ?></div></td>
        </tr><?php endforeach; endif; ?>
        </tbody></table></div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
