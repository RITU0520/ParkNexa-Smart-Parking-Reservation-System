<?php
require 'config/database.php';
$pageTitle='Register | ParkEasy';
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
$name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??'');
$password=$_POST['password']??''; $confirm=$_POST['confirm_password']??'';
if(!preg_match('/^[A-Za-z ]{2,100}$/',$name)) $errors[]='Enter a valid name.';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Enter a valid email address.';
if(!preg_match('/^[0-9]{10}$/',$phone)) $errors[]='Mobile number must contain 10 digits.';
if(strlen($password)<8) $errors[]='Password must contain at least 8 characters.';
if($password!==$confirm) $errors[]='Passwords do not match.';
if(!$errors){
$s=$pdo->prepare('SELECT id FROM users WHERE email=?'); $s->execute([$email]);
if($s->fetch()) $errors[]='An account with this email already exists.';
else {
$s=$pdo->prepare('INSERT INTO users(name,email,phone,password_hash) VALUES(?,?,?,?)');
$s->execute([$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT)]);
header('Location: login.php?registered=1'); exit;
}}}
require 'includes/header.php';
?>
<section class="auth-section"><div class="auth-card">
<span class="eyebrow">CREATE ACCOUNT</span><h1>Register</h1>
<?php foreach($errors as $e): ?><div class="alert error"><?=htmlspecialchars($e)?></div><?php endforeach; ?>
<form method="post">
<label>Full Name<input name="name" required value="<?=htmlspecialchars($_POST['name']??'')?>"></label>
<label>Email<input type="email" name="email" required value="<?=htmlspecialchars($_POST['email']??'')?>"></label>
<label>Mobile Number<input type="tel" name="phone" maxlength="10" required value="<?=htmlspecialchars($_POST['phone']??'')?>"></label>
<label>Password<input type="password" name="password" minlength="8" required></label>
<label>Confirm Password<input type="password" name="confirm_password" minlength="8" required></label>
<button class="btn full">Create Account</button>
</form><p class="muted">Already registered? <a href="login.php">Login</a>.</p>
</div></section>
<?php require 'includes/footer.php'; ?>
