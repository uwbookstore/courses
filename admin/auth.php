<?php
require '../../db.php';
requireGuest();

if (isset($_SESSION['user_id'])) {
    header('Location: /bookstore/courses/admin/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email']));
  $password = $_POST['password'];

  if ($_POST['action'] === 'signup') {

    if (!str_ends_with($email, '@' . ALLOWED_DOMAIN)) {
      setFlash('danger', 'Invalid email domain');
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $code = bin2hex(random_bytes(16));

      try {
        $stmt = $conn->prepare(
          "INSERT INTO users (email, password_hash, verification_code)
          VALUES (?, ?, ?)"
        );
        $stmt->bind_param('sss', $email, $hash, $code);
        $stmt->execute();

        sendMail(
          $email,
          "Verify your account",
          "Verify here:\n" . SITE_URL . "/verify.php?code=$code"
        );

        setFlash('success', 'Check your email to verify your account.');

      } catch (mysqli_sql_exception $e) {

        // 1062 = duplicate entry
        if ($e->getCode() === 1062) {
          setFlash('danger', 'An account with this email already exists.');
        } else {
          error_log($e->getMessage());
          setFlash('danger', 'Something went wrong. Please try again.');
        }
      }
    }
  }

  if ($_POST['action'] === 'login') {
    $stmt = $conn->prepare(
      "SELECT id, password_hash, is_verified FROM users WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $user['is_verified'] &&
        password_verify($password, $user['password_hash'])
    ) {
      $_SESSION['user_id'] = $user['id'];
      setFlash('success', 'Welcome back!');
      header("Location: /bookstore/courses/admin/");
      exit;
    } else {
      setFlash('danger', "Invalid login or unverified account.");
    }
  }
}

$flashes = getFlashes();
$pageTitle = 'Login/Signup';
$hideNav = true;
include './includes/head.php';
?>

  <div class="container mt-5" style="max-width: 420px;">
    <h1 id="formTitle">Login</h1>

    <?php foreach ($flashes as $flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>

    <!-- LOGIN FORM -->
    <form method="post" id="loginForm">
      <input type="hidden" name="action" value="login">

      <div class="mb-3">
        <input type="email" name="email" class="form-control" placeholder="Email" required>
      </div>

      <div class="mb-3">
        <input type="password" name="password" class="form-control" placeholder="Password" required>
      </div>

      <button class="btn btn-primary w-100 submit-btn">Login</button>
    </form>

    <!-- SIGNUP FORM -->
    <form method="post" id="signupForm" class="d-none">
      <input type="hidden" name="action" value="signup">

      <div class="mb-3">
        <input type="email" name="email" class="form-control" placeholder="Email (@uwbookstore.com)" required>
      </div>

      <div class="mb-3">
        <input type="password" name="password" class="form-control" placeholder="Password" required>
      </div>

      <button class="btn btn-success w-100 submit-btn">Create Account</button>
    </form>

    <div class="text-center mt-3">
      <a href="#" id="toggleForm">Create account</a>
    </div>
  </div>

  <script>
    <?php foreach ($flashes as $flash): ?>
      <?php if ($flash['type'] === 'danger'): ?>
        document.getElementById('loginForm').classList.add('d-none');
        document.getElementById('signupForm').classList.remove('d-none');
        document.getElementById('formTitle').textContent = 'Create Account';
        document.getElementById('toggleForm').textContent = 'Already have an account?'
      <?php endif; ?>
    <?php endforeach; ?>
  </script>

  <script src="./js/login.js"></script>

<?php
include './includes/footer.php';
?>
