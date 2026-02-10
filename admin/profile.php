<?php
require '../../db.php';
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $newHash === password_hash($_POST['new_password'], PASSWORD_DEFAULT);

  $stmt = $conn->prepare(
    'UPDATE users SET password_hash = ? WHERE id = ?'
  );
  $stmt->execute([$newHash, $_SESSION['user_id']]);

  $succuss = 'Password updated.';
}
?>