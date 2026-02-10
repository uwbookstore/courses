<?php
require '../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = bin2hex(random_bytes(16));
  $expires = date('Y-m-d H:i:s', time() + 3600);

  $stmt = $conn->prepare(
    'UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?'
  );
  $stmt->execute([$token, $expires, $_POST['email']]);

  $link = SITE_URL . '/reset-password.php?token=$token';
  
  sendMail($_POST['email'], 'Password Reset', 'Reset here:\$link');

  $success = 'If the email exists, a reset link was sent.';
}
?>