<?php

function requireAuth() {
  if (!isset($_SESSION['user_id'])) {
    setFlash('danger', 'Please sign in to continue.');
    header('Location: /bookstore/courses/admin/auth.php');
    exit;
  }
}

function requireGuest() {
  if (isset($_SESSION['user_id'])) {
    header('Location: /bookstore/courses/admin/');
    exit;
  }
}
?>