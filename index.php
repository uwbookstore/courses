<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../db.php';

$REPEAT_THRESHOLD = 10; // aisles with <= 8 courses get repeated

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
$courseAisles = [];

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
  LEFT JOIN courses c ON ca.course_id = c.id AND c.is_active = 1
  ORDER BY a.display_order ASC, c.long_description"
);

while ($row = $result->fetch_assoc()) {
  if ($row['course_id']) {
    $aislesWithCourses[$row['aisle_id']]['courses'][] = $row;

    $courseAisles[$row['course_id']][] = (int) $row['aisle_id'];
  }
}

foreach ($courseAisles as &$aisles) {
  $aisles = array_values(array_unique($aisles));
}
unset($aisles);

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

foreach ($aislesWithCourses as &$aisle) {
  $courses = $aisle['courses'];
  $count = count($courses);

  // existing column split logic
  if ($count > $MAX_ROWS) {
    $splitAt = (int) ceil($count / 2);
    $aisle['columns'] = [
      array_slice($courses, 0, $splitAt),
      array_slice($courses, $splitAt),
    ];
  } else {
    $aisle['columns'] = [$courses];
  }

  // ✅ NOW compute repeat rules
  $aisle['repeat'] = [
    'top'    => false,
    'middle' => false,
    'bottom' => false,
  ];

  if ($count > 0 && $count <= $REPEAT_THRESHOLD) {
    $aisle['repeat']['bottom'] = true;

    if ($count <= 6) {
      $aisle['repeat']['middle'] = true;
    }
  }
}
unset($aisle);

function renderCourseCell($course, $extraClass = '') {
  if (!$course) {
    return '<td>&nbsp;</td><td>&nbsp;</td>';
  }

  $class = $extraClass;

  switch ($course['display_style'] ?? 'normal') {
    case 'notice':
      $class .= ' text-primary fw-bold text-center text-uppercase';
      break;
    case 'warning':
      $class .= ' text-danger fw-bold text-center text-uppercase';
      break;
  }

  if (!$course['is_active']) {
    $class .= ' course-inactive';
  }

  if ($course['short_description'] === null) {
    return sprintf(
      '<td colspan="2" class="%s">%s</td>',
      trim($class),
      htmlspecialchars($course['long_description'])
    );
  }

  return sprintf(
    '<td class="%s course-short">%s</td><td class="%s course">%s</td>',
    trim($class),
    htmlspecialchars($course['short_description']),
    trim($class),
    htmlspecialchars($course['long_description'])
  );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="stylesheet" href="./css/style.css">
  <title>Courses by Aisle</title>
</head>

<body>
    <?php
    $aisleChunks = array_chunk($aislesWithCourses, 3, true);
    ?>

    <div id="scroll-container">
      <div id="content">
        <?php foreach ($aisleChunks as $chunk): ?>
        <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <?php foreach ($chunk as $aisleId => $aisle): ?>
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

                    <?php
                      $course = $column[$i] ?? null;

                      // MIDDLE repeat
                      if (
                        !$course &&
                        $aisle['repeat']['middle'] &&
                        $i === (int) floor($maxRows / 2)
                      ) {
                        $course = $column[$i % count($column)];
                      }

                      // BOTTOM repeat
                      if (
                        !$course &&
                        $aisle['repeat']['bottom'] &&
                        $i > $maxRows - count($column)
                      ) {
                        $course = $column[$i % count($column)];
                      }

                      echo renderCourseCell($course);
                    ?>

                  <?php endforeach; ?>
                <?php endforeach; ?>
              </tr>
              <?php endfor; ?>
            </tbody>


    </table>
    <?php endforeach; ?>
      </div>
    </div>

  <script src="./js/script.js"></script>
</body>

</html>
