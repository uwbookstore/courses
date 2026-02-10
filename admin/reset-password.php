<?php
require '../../db.php';

$token = $_GET['token'] ?? '';

$stmt = $conn->prepare(
  'SELECT * FROM users
  WHERE reset_token = ? AND reset_expires > NOW()'
);
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
  die('Invalid or expired token.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $stmt = $conn->prepare(
    'UPDATE users
    SET password_hash = ?, reset_token = NULL, reset_expires = NULL
    WHERE id = ?'
  );
  $stmt->execute([$hash, $user['id']]);

  echo 'Password reset. You may log in.';
}
?>