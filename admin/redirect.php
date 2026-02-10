<?php
require '../../db.php';

$redirect = $_SESSION['redirect'] ?? null;
unset($_SESSION['redirect']);

$redirectUrl = $redirect['url'] ?? '/courses/admin/auth.php';
$redirectSeconds = $redirect['seconds'] ?? 5;

$pageTitle = 'Redirecting...';
$hideNav = true;

include './includes/head.php';
?>

<div class="container mt-5" style="max-width: 600px;">
  <?php foreach (getFlashes() as $flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
  <?php endforeach; ?>

  <p class="mt-3">
    Redirecting in
    <strong><span id="countdown"><?= $redirectSeconds ?></span></strong>
    second<span id="plural">s</span>…
  </p>

  <p>
    <a href="<?= htmlspecialchars($redirectUrl) ?>" class="btn btn-outline-secondary btn-sm">
      Click here if not redirected
    </a>
  </p>
</div>

<script>
let remaining = <?= (int)$redirectSeconds ?>;
const el = document.getElementById('countdown');
const plural = document.getElementById('plural');

const timer = setInterval(() => {
  remaining--;
  if (remaining <= 0) {
    clearInterval(timer);
    return;
  }
  el.textContent = remaining;
  plural.style.display = remaining === 1 ? 'none' : 'inline';
}, 1000);
</script>

<?php include './includes/footer.php'; ?>