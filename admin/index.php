<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../../db.php';
requireAuth();

if (!isset($_SESSION['user_id'])) {
    header('Location: /bookstore/courses/admin/auth.php');
    exit;
}
$error = null;
$lastCourse = null;
if (!empty($_SESSION['last_course_id'])) {
  $stmt = $conn->prepare(
    "SELECT c.short_description, c.long_description,
          GROUP_CONCAT(a.name ORDER BY a.display_order SEPARATOR ', ') AS aisles
    FROM courses c
    LEFT JOIN course_aisle ca ON c.id = ca.course_id
    LEFT JOIN aisles a ON ca.aisle_id = a.id
    WHERE c.id = ?" 
  );
  $stmt->bind_param("i", $_SESSION['last_course_id']);
  $stmt->execute();
  $lastCourse = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  // Optional: clear so it only shows once
  unset($_SESSION['last_course_id']);
}

$displayStyle = 'normal';
$id = null;
$short = '';
$long = '';
$selectedAisles = array_map('intval', $_POST['aisles'] ?? []);

/* ---------------------------
   Load aisles for checkbox list
---------------------------- */
$aisleList = [];
$result = $conn->query("SELECT id, name FROM aisles ORDER BY display_order");
while ($row = $result->fetch_assoc()) {
  $aisleList[] = $row;
}


/* ---------------------------
   If editing, load course + aisles
---------------------------- */
if (isset($_GET['id'])) {
  $id = (int)$_GET['id'];

  // Course data
  $stmt = $conn->prepare(
    "SELECT short_description, long_description, display_style, is_active
    FROM courses WHERE id = ?"
  );
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($short, $long, $displayStyle, $isActive);
  $stmt->fetch();
  $stmt->close();

  // Assigned aisles
  $stmt = $conn->prepare(
    "SELECT aisle_id FROM course_aisle WHERE course_id = ?"
  );
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $selectedAisles[] = $row['aisle_id'];
  }
  $stmt->close();
}

/* ---------------------------
   Handle submit
---------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $short = trim($_POST['short_description']);
  $long = trim($_POST['long_description']);
  $displayStyle = $_POST['display_style'] ?? 'normal';
  $isActive = isset($_POST['is_active']) ? 1 : 0;

  $short = ($short === '') ? null : $short;

  $allowedStyles = ['normal', 'notice', 'warning'];
  if (!in_array($displayStyle, $allowedStyles, true)) {
    $displayStyle = 'normal';
  }
  $selectedAisles = $_POST['aisles'] ?? [];

  // Check for duplicate (ignore current record when editing)
  $stmt = $conn->prepare(
    "SELECT id FROM courses
    WHERE (short_description <=> ?)
      AND long_description = ?
      AND (? IS NULL OR id != ?)"
  );
  $stmt->bind_param("ssii", $short, $long, $id, $id);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    $error = "This course already exists.";
  }

  $stmt->close();

  if (!$error) {
    if ($id) {
      // Update course
      $stmt = $conn->prepare(
        "UPDATE courses
        SET short_description = ?, long_description = ?, display_style = ?, is_active = ?
        WHERE id = ?"
      );
      $stmt->bind_param("sssii", $short, $long, $displayStyle, $isActive, $id);
      $stmt->execute();
      $stmt->close();

      // Clear existing aisle mappings
      $stmt = $conn->prepare(
        "DELETE FROM course_aisle WHERE course_id = ?"
      );
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->close();
    } else {
      // Insert course
      $stmt = $conn->prepare(
        "INSERT INTO courses (short_description, long_description, display_style, is_active)
        VALUES (?, ?, ?, ?)"
      );
      $stmt->bind_param("sssi", $short, $long, $displayStyle, $isActive);
      $stmt->execute();
      $id = $stmt->insert_id;
      $stmt->close();
    }
    $_SESSION['last_course_id'] = $id;
  }

  // Insert aisle mappings
  if (!$error && !empty($selectedAisles)) {
    $stmt = $conn->prepare(
      "INSERT INTO course_aisle (course_id, aisle_id)
      VALUES (?, ?)"
    );

    foreach ($selectedAisles as $aisleId) {
      $stmt->bind_param("ii", $id, $aisleId);
      $stmt->execute();
    }
    $stmt->close();
  }

  if (!$error) {
    header("Location: /bookstore/courses/admin/");
    exit;
  }
}

// Fetch aisles
$aislesResult = $conn->query(
  "SELECT id, name
  FROM aisles
  ORDER BY display_order"
);

$aislesWithCourses = [];
while ($row = $aislesResult->fetch_assoc()) {
  $aislesWithCourses[$row['id']] = [
    'name' => $row['name'],
    'courses' => []
  ];
}


// Fetch courses per aisle
$result = $conn->query(
  "SELECT
    a.id AS aisle_id,
    c.id AS course_id,
    c.short_description,
    c.long_description,
    c.display_style,
    c.is_active
  FROM aisles a
  LEFT JOIN course_aisle ca ON a.id = ca.aisle_id
  LEFT JOIN courses c ON ca.course_id = c.id
  ORDER BY a.name ASC, c.long_description"
);

while ($row = $result->fetch_assoc()) {
  if ($row['course_id']) {
    $aislesWithCourses[$row['aisle_id']]['courses'][] = $row;
  }
}

$MAX_ROWS = 35;

foreach ($aislesWithCourses as &$aisle) {
  $courses = $aisle['courses'];
  $count = count($courses);

  if ($count > $MAX_ROWS) {
    $splitAt = (int) ceil($count / 2);
    $aisle['columns'] = [
      array_slice($courses, 0, $splitAt),
      array_slice($courses, $splitAt),
    ];
  } else {
    $aisle['columns'] = [$courses];
  }
}
unset($aisle);

$pageTitle = $id ? 'Edit Course' : 'Add Course';

include './includes/head.php';
?>

  <main>
    <div class="container py-5">
      <h2><?= $id ? 'Edit Course' : 'Add Course' ?></h2>
      <form method="post">
        <div class="row g-2">
          <div class="mb-3 col-md">
            <label for="short_description" class="form-label">Short Description:</label>
            <input type="text" class="form-control" id="short_description" value="<?= htmlspecialchars($short ?? '') ?>" name="short_description" autofocus placeholder="Leave blank for notices/messages">
          </div>
          <div class="mb-3 col-md">
            <label for="long_description" class="form-label">Long Description:</label>
            <input type="text" class="form-control" id="long_description"value="<?= htmlspecialchars($long) ?>" name="long_description" required>
          </div>
          <div class="mb-3 col-md">
            <label for="aisles[]" class="form-label">Aisles</label>
            <select name="aisles[]" id="aisles[]" class="form-select" multiple>
              <?php foreach ($aisleList as $aisle): ?>
                <option
                  value="<?= $aisle['id'] ?>"
                  <?= in_array($aisle['id'], $selectedAisles) ? 'selected' : '' ?>
                >
                  <?= htmlspecialchars($aisle['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">
              Hold <kbd>Ctrl</kbd> (Windows) or <kbd>⌘</kbd> (Mac) to select multiple.
            </div>
          </div>
          <div class="mb-3 col-md">
            <label for="display_style" class="form-label">Display Style</label>
            <select
              name="display_style"
              id="display_style"
              class="form-select"
            >
              <option value="normal"  <?= $displayStyle === 'normal'  ? 'selected' : '' ?>>
                Normal
              </option>
              <option value="notice"  <?= $displayStyle === 'notice'  ? 'selected' : '' ?>>
                Notice (Blue text)
              </option>
              <option value="warning" <?= $displayStyle === 'warning' ? 'selected' : '' ?>>
                Warning (Red / Bold)
              </option>
            </select>
            <div class="form-text">
              Controls how this row is highlighted on the display screens.
            </div>
          </div>
          <div class="col-md mb-3">
            <div class="form-check">
              <input
                class="form-check-input"
                type="checkbox"
                name="is_active"
                id="is_active"
                value="1"
                <?= ($isActive ?? 1) ? 'checked' : '' ?>
              >
              <label class="form-check-label" for="is_active">
                Offered this semester
              </label>
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary mb-2">
          <?= $id ? 'Update Course' : 'Add Course' ?>
        </button>
      </form>
      <?php if ($error): ?>
        <div style="color:red; margin-bottom:10px;">
            <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      <?php if ($lastCourse): ?>
          <div style="padding:10px; border:1px solid #ccc; margin-bottom:15px;">
              <strong>Last added:</strong><br>
              <?= htmlspecialchars($lastCourse['short_description']) ?> —
              <?= htmlspecialchars($lastCourse['long_description']) ?><br>
              <em>Aisles:</em> <?= htmlspecialchars($lastCourse['aisles'] ?: 'None') ?>
          </div>
      <?php endif; ?>
    </div>
      <?php
      $aisleChunks = array_chunk($aislesWithCourses, 3, true);
      ?>
      <?php foreach ($aisleChunks as $chunk): ?>
          <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <?php foreach ($chunk as $aisle): ?>
                    <?php if (count($aisle['columns']) === 2): ?>
                      <th colspan="2" class="text-center">
                        <?= htmlspecialchars($aisle['name']) ?>
                      </th>
                      <th colspan="2" class="text-center">
                        <?= htmlspecialchars($aisle['name']) ?><span class="text-muted"> — continued</span>
                      </th>
                    <?php else: ?>
                      <th colspan="2" class="text-center">
                        <?= htmlspecialchars($aisle['name']) ?>
                      </th>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <?php
                $maxRows = max(array_map(function ($aisle) {
                  return max(array_map('count', $aisle['columns']));
                }, $chunk));
              ?>
              <tbody>
              <?php for ($i = 0; $i < $maxRows; $i++): ?>
                <tr>
                  <?php foreach ($chunk as $aisle): ?>
                    <?php foreach ($aisle['columns'] as $column): ?>
                      <?php if (!empty($column[$i])):
                        $course = $column[$i];
                        $isInactive = !$course['is_active'];
                        switch ($course['display_style'] ?? 'normal') {
                          case 'notice':
                            $class = 'text-primary fw-bold text-center text-uppercase';
                            break;
                          case 'warning':
                            $class = 'text-danger fw-bold text-center text-uppercase';
                            break;
                          default:
                            $class = '';
                        }
                        $class .= $isInactive ? ' course-inactive' : '';
                      ?>
                        <?php if ($course['short_description'] === null): ?>
                          <td colspan="2" class="<?= $class ?>">
                            <div class="cell-content">
                              <a href="index.php?id=<?= $course['course_id'] ?>" class="edit-link" title="Edit notice">
                                <?= htmlspecialchars($course['long_description']) ?>
                                <i class="fa fa-pencil" aria-hidden="true"></i>
                              </a>
                            </div>
                          </td>
                        <?php else: ?>
                          <td class="<?= $class ?> course-short">
                            <div class="cell-content">
                              <a href="index.php?id=<?= $course['course_id'] ?>" class="edit-link" title="Edit course">
                                <?= htmlspecialchars($course['short_description']) ?>
                                <i class="fa fa-pencil" aria-hidden="true"></i>
                              </a>
                            </div>
                          </td>
                          <td class="<?= $class ?> course">
                            <div class="cell-content">
                              <a href="index.php?id=<?= $course['course_id'] ?>" class="edit-link" title="Edit course">
                                <?= htmlspecialchars($course['long_description']) ?>
                                <i class="fa fa-pencil" aria-hidden="true"></i>
                              </a>
                            </div>
                          </td>
                        <?php endif; ?>
                      <?php else: ?>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                </tr>
              <?php endfor; ?>
              </tbody>
      </table>
      <?php endforeach; ?>
  </main>

<?php
include './includes/footer.php';
?>
