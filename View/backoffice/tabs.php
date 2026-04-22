<?php
session_start();

include_once(__DIR__ . '/../../controller/Controller_user.php');

$controller = new Controller_user();
$usersResult = $controller->listusers();
$users = [];
$errorMessage = '';

try {
    if ($usersResult) {
        $users = $usersResult->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $errorMessage = 'Unable to load users list.';
}

$columns = [];
if (!empty($users)) {
    $columns = array_keys($users[0]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Foovia Backoffice - Users</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Boldonse&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --yellow: #f5c842;
      --green: #4bae52;
      --orange: #d94f00;
      --forest: #2e4a28;
      --dark: #111008;
      --off-white: #fdf8ee;
      --line: rgba(17, 16, 8, 0.12);
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'DM Sans', sans-serif;
      color: var(--dark);
      background:
        radial-gradient(circle at 10% 0%, rgba(245, 200, 66, 0.22), transparent 35%),
        radial-gradient(circle at 100% 10%, rgba(75, 174, 82, 0.16), transparent 38%),
        var(--off-white);
      min-height: 100vh;
      padding: 30px;
    }

    .page-shell {
      max-width: 1400px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.85);
      border: 1px solid var(--line);
      border-radius: 20px;
      box-shadow: 0 16px 40px rgba(17, 16, 8, 0.12);
      overflow: hidden;
    }

    .hero {
      padding: 28px 30px;
      background: linear-gradient(120deg, #1c1a10 0%, #2f2a19 60%, #2e4a28 100%);
      color: #fff;
    }

    .hero h1 {
      margin: 0;
      font-family: 'Boldonse', sans-serif;
      font-size: clamp(1.5rem, 2vw, 2.1rem);
      letter-spacing: 0.02em;
    }

    .hero p {
      margin: 8px 0 0;
      color: rgba(255, 255, 255, 0.78);
    }

    .content {
      padding: 24px;
    }

    .notice {
      margin-bottom: 16px;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid #f2c1b2;
      background: #fff0eb;
      color: #842b16;
      font-weight: 500;
    }

    .empty {
      padding: 30px;
      border: 1px dashed var(--line);
      border-radius: 12px;
      text-align: center;
      color: #5f5a4f;
      background: #fff;
    }

    .table-wrap {
      width: 100%;
      overflow: auto;
      border: 1px solid var(--line);
      border-radius: 14px;
      background: #fff;
    }

    table {
      width: 100%;
      min-width: 1000px;
      border-collapse: collapse;
      font-size: 0.9rem;
    }

    thead th {
      position: sticky;
      top: 0;
      z-index: 1;
      background: linear-gradient(180deg, #fff8d7 0%, #fff 100%);
      color: var(--forest);
      text-align: left;
      padding: 12px 14px;
      border-bottom: 1px solid var(--line);
      white-space: nowrap;
      font-family: 'Boldonse', sans-serif;
      font-size: 0.78rem;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    tbody td {
      padding: 11px 14px;
      border-bottom: 1px solid rgba(17, 16, 8, 0.07);
      white-space: nowrap;
      color: #2a2922;
    }

    tbody tr:nth-child(even) {
      background: #fffcf3;
    }

    tbody tr:hover {
      background: #f4fbe9;
    }

    .top-actions {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 14px;
    }

    .back-link {
      text-decoration: none;
      color: #fff;
      background: var(--orange);
      border: 1px solid transparent;
      border-radius: 999px;
      padding: 8px 14px;
      font-weight: 700;
      font-size: 0.85rem;
      transition: background 0.2s ease;
    }

    .back-link:hover {
      background: #b63f00;
    }
  </style>
</head>
<body>
  <div class="page-shell">
    <div class="hero">
      <h1>Foovia Users Table</h1>
      <p>Live list of all users from your database.</p>
    </div>

    <div class="content">
      <div class="top-actions">
        <a href="backoffice_work.php" class="back-link">Back to Backoffice</a>
      </div>

      <?php if (!empty($errorMessage)): ?>
        <div class="notice"><?php echo htmlspecialchars($errorMessage); ?></div>
      <?php endif; ?>

      <?php if (empty($users)): ?>
        <div class="empty">No users found in the database.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <?php foreach ($columns as $column): ?>
                  <th><?php echo htmlspecialchars($column); ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
                <tr>
                  <?php foreach ($columns as $column): ?>
                    <td><?php echo htmlspecialchars((string) ($user[$column] ?? '')); ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
