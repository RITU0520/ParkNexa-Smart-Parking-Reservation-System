<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
$userRole = strtolower((string)($_SESSION['role'] ?? ''));
$isAdmin = $userRole === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="ParkNexa - Smart Parking Reservation System">
<title>
<?= isset($pageTitle)
    ? htmlspecialchars($pageTitle) . ' | ParkNexa'
    : 'ParkNexa – Smart Parking Reservation System'
?>
</title>
<link rel="stylesheet" href="/Smart%20Parking%20Reservation%20System/assets/css/style.css">
</head>
<body>
<header class="navbar">
<div class="navbar-container">
<a href="index.php" class="logo">
<span class="logo-icon">🅿</span>
<span>Park<span style="color:#6D28D9;">Nexa</span></span>
</a>

<button class="nav-toggle" id="navToggle" type="button" aria-label="Toggle navigation">☰</button>

<nav class="nav-links" id="navLinks">
<a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>
<a href="search-parking.php" class="<?= $currentPage === 'search-parking.php' ? 'active' : '' ?>">Find Parking</a>

<?php if (isset($_SESSION['user_id'])): ?>

<a href="<?= $isAdmin ? 'admin-dashboard.php' : 'dashboard.php' ?>"
   class="<?= ($isAdmin ? $currentPage === 'admin-dashboard.php' : $currentPage === 'dashboard.php') ? 'active' : '' ?>">
    <?= $isAdmin ? 'Admin Dashboard' : 'Dashboard' ?>
</a>

<?php if (!$isAdmin): ?>
<a href="my-reservations.php" class="<?= $currentPage === 'my-reservations.php' ? 'active' : '' ?>">My Reservations</a>
<?php endif; ?>

<?php if ($isAdmin): ?>
<a href="admin-parking.php" class="<?= $currentPage === 'admin-parking.php' ? 'active' : '' ?>">Parking</a>
<a href="admin-slots.php" class="<?= $currentPage === 'admin-slots.php' ? 'active' : '' ?>">Slots</a>
<a href="admin-reservations.php" class="<?= $currentPage === 'admin-reservations.php' ? 'active' : '' ?>">Reservations</a>
<a href="admin-users.php" class="<?= $currentPage === 'admin-users.php' ? 'active' : '' ?>">Users</a>
<?php endif; ?>

<a href="logout.php" class="nav-login">Logout</a>

<?php else: ?>

<a href="login.php" class="<?= $currentPage === 'login.php' ? 'active' : '' ?>">Login</a>
<a href="register.php" class="nav-register">Get Started</a>

<?php endif; ?>
</nav>
</div>
</header>
<main>
