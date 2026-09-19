<?php
require 'config/database.php';
$pageTitle='Login | ParkEasy'; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
$email=trim($_POST['email']??''); $password=$_POST['password']??'';
$s=$pdo->prepare('SELECT id,name,password_hash,role FROM users WHERE email=?'); $s->execute([$email]); $u=$s->fetch();
if($u && password_verify($password,$u['password_hash'])){
session_start(); session_regenerate_id(true); $_SESSION['user_id']=$u['id']; $_SESSION['name']=$u['name']; $_SESSION['role']=$u['role'];
header('Location: dashboard.php'); exit;
}$error='Invalid email or password.';
}
require 'includes/header.php';
?>
<section class="auth-section"><div class="auth-card">
<span class="eyebrow">WELCOME BACK</span><h1>Login</h1>
<?php if(isset($_GET['registered'])):?><div class="alert success">Registration successful. Please log in.</div><?php endif;?>
<?php if($error):?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post">
<label>Email<input type="email" name="email" required></label>
<label>Password<input type="password" name="password" required></label>
<button class="btn full">Login</button>
</form><p class="muted">New user? <a href="register.php">Create an account</a>.</p>
</div></section>
<?php require 'includes/footer.php'; ?>
