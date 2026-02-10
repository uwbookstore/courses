<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <link rel="stylesheet" href="../css/style.css">

  <title><?= htmlspecialchars($pageTitle ?? 'UW Bookstore') ?></title>

  <?php if (!empty($redirectUrl)): ?>
    <meta http-equiv="refresh"
          content="<?= intval($redirectSeconds ?? 3) ?>;url=<?= htmlspecialchars($redirectUrl) ?>">
  <?php endif; ?>
</head>
<body>

<?php if (empty($hideNav)): ?>
<header>
  <nav class="navbar navbar-expand-lg bg-light">
    <div class="container">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
        aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a href="/courses/admin/logout.php" class="nav-link">
              Log Out <i class="fa fa-sign-out" aria-hidden="true"></i>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>
<?php endif; ?>
