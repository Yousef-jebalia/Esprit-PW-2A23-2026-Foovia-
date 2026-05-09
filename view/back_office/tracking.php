<?php
session_start();
require_once '../../controller/tracking/ObjectifLongTerme_Controller.php';
require_once '../../controller/tracking/ObjectifHebdomadaire_Controller.php';
require_once '../../model/config.php';

$longTermController = new ObjectifLongTerme_Controller();
$weeklyController = new ObjectifHebdomadaire_Controller();

$allGoals = $longTermController->list_objectifs();

// Get all unique user IDs from goals
$allUserIds = [];
$goalsByUser = [];
foreach ($allGoals as $goal) {
    $userId = (int) ($goal['id_user'] ?? 0);
    if ($userId > 0 && !in_array($userId, $allUserIds, true)) {
        $allUserIds[] = $userId;
    }
    if (!isset($goalsByUser[$userId])) {
        $goalsByUser[$userId] = $goal;
    }
}

// Get all users from database
$db = config::getConnexion();
$allUsers = [];
try {
    $query = $db->query("SELECT DISTINCT id_user FROM objectiflongterme ORDER BY id_user ASC");
    $rows = $query->fetchAll();
    foreach ($rows as $row) {
        $userId = (int) ($row['id_user'] ?? 0);
        if ($userId > 0) {
            $allUsers[$userId] = [
                'id_user' => $userId,
                'goal' => $goalsByUser[$userId] ?? null,
                'weeklyRows' => $weeklyController->list_objectifs_by_user($userId)
            ];
        }
    }
} catch (Exception $e) {
    $allUsers = [];
}

$totalUsers = count($allUsers);
$totalGoals = count($allGoals);
$totalWeeklyEntries = 0;
$usersWithGoals = 0;
$goalStatusCounts = [];

foreach ($allUsers as $userData) {
    $weeklyCount = count($userData['weeklyRows'] ?? []);
    $totalWeeklyEntries += $weeklyCount;

    if (!empty($userData['goal'])) {
        $usersWithGoals++;
        $status = trim((string) ($userData['goal']['status_obj'] ?? ''));
        if ($status === '') {
            $status = 'Unknown';
        }
        $goalStatusCounts[$status] = ($goalStatusCounts[$status] ?? 0) + 1;
    }
}

$usersWithoutGoals = max(0, $totalUsers - $usersWithGoals);
$averageWeeklyEntries = $totalUsers > 0 ? round($totalWeeklyEntries / $totalUsers, 1) : 0;

function tracking_numeric_value($value): ?float {
    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }

    $normalized = trim((string) $value);
    if ($normalized === '' || !is_numeric($normalized)) {
        return null;
    }

    return (float) $normalized;
}

function tracking_average(array $values): ?float {
    $count = count($values);
    if ($count === 0) {
        return null;
    }

    return array_sum($values) / $count;
}

function tracking_median(array $values): ?float {
    $count = count($values);
    if ($count === 0) {
        return null;
    }

    sort($values, SORT_NUMERIC);
    $middle = (int) floor($count / 2);

    if ($count % 2 === 0) {
        return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    return $values[$middle];
}

$goalTypeCounts = [];
$macroValues = [
    'Calories' => [],
    'Fat' => [],
    'Protein' => [],
    'Carbs' => [],
];
$weekBuckets = [];
$scatterPoints = [];

foreach ($allGoals as $goal) {
    $goalType = trim((string) ($goal['type_obj'] ?? ''));
    if ($goalType === '') {
        $goalType = 'Unknown';
    }
    $goalTypeCounts[$goalType] = ($goalTypeCounts[$goalType] ?? 0) + 1;

    $calories = tracking_numeric_value($goal['obj_cal_obj'] ?? null);
    $fat = tracking_numeric_value($goal['obj_fat_obj'] ?? null);
    $protein = tracking_numeric_value($goal['obj_prot_obj'] ?? null);
    $carbs = tracking_numeric_value($goal['obj_carb_obj'] ?? null);

    if ($calories !== null) {
        $macroValues['Calories'][] = $calories;
    }
    if ($fat !== null) {
        $macroValues['Fat'][] = $fat;
    }
    if ($protein !== null) {
        $macroValues['Protein'][] = $protein;
    }
    if ($carbs !== null) {
        $macroValues['Carbs'][] = $carbs;
    }
}

foreach ($allUsers as $userId => $userData) {
    $weeklyRows = $userData['weeklyRows'] ?? [];

    foreach ($weeklyRows as $row) {
        $dateValue = trim((string) ($row['date_suiv'] ?? ''));
        if ($dateValue === '') {
            continue;
        }

        $dateTime = date_create($dateValue);
        if ($dateTime === false) {
            continue;
        }

        $weekStart = (clone $dateTime)->modify('monday this week');
        $weekKey = $weekStart->format('o-W');
        if (!isset($weekBuckets[$weekKey])) {
            $weekBuckets[$weekKey] = [
                'label' => $weekStart->format('M j'),
                'users' => [],
            ];
        }

        $weekBuckets[$weekKey]['users'][(int) $userId] = true;
    }

    $sortedWeeklyRows = $weeklyRows;
    usort($sortedWeeklyRows, function ($left, $right) {
        $leftDate = strtotime((string) ($left['date_suiv'] ?? '')) ?: 0;
        $rightDate = strtotime((string) ($right['date_suiv'] ?? '')) ?: 0;
        return $leftDate <=> $rightDate;
    });

    $weights = [];
    foreach ($sortedWeeklyRows as $row) {
        $weight = tracking_numeric_value($row['poids_suiv'] ?? null);
        if ($weight !== null) {
            $weights[] = $weight;
        }
    }

    if (count($weights) >= 2) {
        $scatterPoints[] = [
            'label' => 'User ' . $userId,
            'x' => count($sortedWeeklyRows),
            'y' => round($weights[0] - $weights[count($weights) - 1], 1),
        ];
    }
}

ksort($weekBuckets);
$retentionLabels = [];
$retentionValues = [];
$initialActiveUsers = 0;
foreach ($weekBuckets as $bucket) {
    $activeUsers = count($bucket['users']);
    if ($initialActiveUsers === 0) {
        $initialActiveUsers = $activeUsers;
    }

    $retentionLabels[] = $bucket['label'];
    $retentionValues[] = $initialActiveUsers > 0 ? round(($activeUsers / $initialActiveUsers) * 100, 1) : 0;
}

$goalTypeChartData = [];
foreach ($goalTypeCounts as $label => $count) {
    $goalTypeChartData[] = [
        'label' => $label,
        'value' => $count,
    ];
}

$macroChartData = [];
foreach ($macroValues as $label => $values) {
    $macroChartData[] = [
        'label' => $label,
        'average' => round(tracking_average($values) ?? 0, 1),
        'median' => round(tracking_median($values) ?? 0, 1),
    ];
}

$trackingChartData = [
    'retention' => [
        'labels' => $retentionLabels,
        'values' => $retentionValues,
    ],
    'goalTypes' => $goalTypeChartData,
    'macros' => $macroChartData,
    'scatter' => $scatterPoints,
];

function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Material Able bootstrap admin template by Codedthemes</title>
    <!-- HTML5 Shim and Respond.js IE10 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 10]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
      <![endif]-->
    <!-- Meta -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <meta name="keywords" content="bootstrap, bootstrap admin template, admin theme, admin dashboard, dashboard template, admin template, responsive" />
    <meta name="author" content="Codedthemes" />
    <!-- Favicon icon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <!-- Google font-->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">
    <!-- waves.css -->
    <link rel="stylesheet" href="assets/pages/waves/css/waves.min.css" type="text/css" media="all">
    <!-- Required Fremwork -->
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap/css/bootstrap.min.css">
    <!-- waves.css -->
    <link rel="stylesheet" href="assets/pages/waves/css/waves.min.css" type="text/css" media="all">
    <link rel="stylesheet" type="text/css" href="assets/icon/themify-icons/themify-icons.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome-n.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/jquery.mCustomScrollbar.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <style>
        .pcoded-content {
            background: linear-gradient(180deg, #f6f8fc 0%, #eef3fb 100%);
        }
        .page-header {
            margin-bottom: 24px;
        }
        .page-header-title h5 {
            letter-spacing: 0.4px;
        }
        .table thead th {
            white-space: nowrap;
        }
        .tracking-empty {
            color: #666;
            font-weight: 600;
        }
        .tracking-tabs {
            margin-bottom: 18px;
            display: inline-flex;
            gap: 8px;
            padding: 8px;
            border: 0;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.78);
            box-shadow: 0 12px 30px rgba(35, 48, 72, 0.08);
            backdrop-filter: blur(10px);
        }
        .tracking-tabs .nav-item {
            margin-bottom: 0;
        }
        .tracking-tabs .nav-link {
            border: 0;
            border-radius: 999px;
            color: #5d697f;
            font-weight: 700;
            padding: 10px 18px;
            transition: all 0.2s ease;
        }
        .tracking-tabs .nav-link:hover {
            color: #2b3d63;
            background: rgba(43, 61, 99, 0.08);
        }
        .tracking-tabs .nav-link.active {
            color: #fff;
            background: linear-gradient(135deg, #243b7a 0%, #4f7cff 100%);
            box-shadow: 0 10px 24px rgba(79, 124, 255, 0.28);
        }
        .tracking-panel {
            border-radius: 18px;
            border: 1px solid rgba(23, 39, 77, 0.08);
            box-shadow: 0 14px 35px rgba(35, 48, 72, 0.08);
            overflow: hidden;
        }
        .tracking-panel .card,
        .tracking-panel .table-card {
            border: 0;
            box-shadow: 0 10px 24px rgba(35, 48, 72, 0.06);
            border-radius: 16px;
        }
        .tracking-panel .card-header {
            background: linear-gradient(180deg, #ffffff 0%, #f8faff 100%);
            border-bottom: 1px solid rgba(23, 39, 77, 0.07);
        }
        .tracking-stat-card {
            position: relative;
            background: linear-gradient(180deg, #ffffff 0%, #f7f9ff 100%);
            border: 1px solid rgba(34, 54, 105, 0.08);
            border-radius: 18px;
            padding: 20px;
            height: 100%;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(35, 48, 72, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .tracking-stat-card::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: linear-gradient(180deg, #4f7cff 0%, #243b7a 100%);
        }
        .tracking-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px rgba(35, 48, 72, 0.12);
        }
        .tracking-stat-value {
            display: block;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.1;
            color: #1f2a44;
            margin-top: 8px;
        }
        .tracking-stat-card small {
            color: #6a768d;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 11px;
        }
        .tracking-stat-card--alt::before {
            background: linear-gradient(180deg, #4ecdc4 0%, #1c8f87 100%);
        }
        .tracking-stat-card--warm::before {
            background: linear-gradient(180deg, #ffb347 0%, #ff7a3d 100%);
        }
        .tracking-stat-card--rose::before {
            background: linear-gradient(180deg, #ff7e9a 0%, #e14b76 100%);
        }
        .tracking-chart-grid {
            margin-top: 8px;
        }
        .tracking-chart-card {
            height: 100%;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f7f9ff 100%);
            box-shadow: 0 12px 28px rgba(35, 48, 72, 0.08);
            overflow: hidden;
        }
        .tracking-chart-card .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(23, 39, 77, 0.07);
            padding-bottom: 14px;
        }
        .tracking-chart-card .card-block {
            padding: 18px;
        }
        .tracking-chart-stage {
            position: relative;
            width: 100%;
            min-height: 300px;
            border-radius: 14px;
            background: linear-gradient(180deg, rgba(245, 248, 255, 0.9) 0%, rgba(255, 255, 255, 0.95) 100%);
            overflow: hidden;
        }
        .tracking-chart-stage svg {
            display: block;
            width: 100%;
            height: 100%;
        }
        .tracking-chart-note {
            color: #6a768d;
            font-size: 13px;
            margin-top: 12px;
        }
        .tracking-chart-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            border-radius: 14px;
            color: #6a768d;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.72);
        }
        .tracking-donut-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: center;
        }
        .tracking-donut {
            width: 230px;
            height: 230px;
            border-radius: 50%;
            position: relative;
            flex: 0 0 auto;
            background: #d8e1f7;
            box-shadow: inset 0 0 0 10px rgba(255, 255, 255, 0.9), 0 14px 30px rgba(35, 48, 72, 0.08);
        }
        .tracking-donut::after {
            content: "";
            position: absolute;
            inset: 28px;
            border-radius: 50%;
            background: linear-gradient(180deg, #ffffff 0%, #f8faff 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }
        .tracking-donut-center {
            position: absolute;
            inset: 0;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            pointer-events: none;
        }
        .tracking-donut-center strong {
            font-size: 32px;
            line-height: 1;
            color: #1f2a44;
        }
        .tracking-donut-center span {
            color: #6a768d;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 11px;
            margin-top: 6px;
        }
        .tracking-donut-legend {
            flex: 1 1 240px;
            display: grid;
            gap: 10px;
        }
        .tracking-legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(34, 54, 105, 0.08);
        }
        .tracking-legend-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: #23324f;
        }
        .tracking-legend-swatch {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.03);
            flex: 0 0 auto;
        }
        .tracking-legend-value {
            color: #6a768d;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="theme-loader">
        <div class="loader-track">
            <div class="preloader-wrapper">
                <div class="spinner-layer spinner-blue">
                    <div class="circle-clipper left"><div class="circle"></div></div>
                    <div class="gap-patch"><div class="circle"></div></div>
                    <div class="circle-clipper right"><div class="circle"></div></div>
                </div>
                <div class="spinner-layer spinner-red">
                    <div class="circle-clipper left"><div class="circle"></div></div>
                    <div class="gap-patch"><div class="circle"></div></div>
                    <div class="circle-clipper right"><div class="circle"></div></div>
                </div>
                <div class="spinner-layer spinner-yellow">
                    <div class="circle-clipper left"><div class="circle"></div></div>
                    <div class="gap-patch"><div class="circle"></div></div>
                    <div class="circle-clipper right"><div class="circle"></div></div>
                </div>
                <div class="spinner-layer spinner-green">
                    <div class="circle-clipper left"><div class="circle"></div></div>
                    <div class="gap-patch"><div class="circle"></div></div>
                    <div class="circle-clipper right"><div class="circle"></div></div>
                </div>
            </div>
        </div>
    </div>

    <div id="pcoded" class="pcoded">
        <div class="pcoded-overlay-box"></div>
        <div class="pcoded-container navbar-wrapper">
            <nav class="navbar header-navbar pcoded-header">
                <div class="navbar-wrapper">
                    <div class="navbar-logo">
                        <a class="mobile-menu waves-effect waves-light" id="mobile-collapse" href="#!">
                            <i class="ti-menu"></i>
                        </a>
                        <div class="mobile-search waves-effect waves-light">
                            <div class="header-search">
                                <div class="main-search morphsearch-search">
                                    <div class="input-group">
                                        <span class="input-group-prepend search-close"><i class="ti-close input-group-text"></i></span>
                                        <input type="text" class="form-control" placeholder="Enter Keyword">
                                        <span class="input-group-append search-btn"><i class="ti-search input-group-text"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="index.html">
                            <img class="img-fluid" src="assets/images/logo.png" alt="Theme-Logo" />
                        </a>
                        <a class="mobile-options waves-effect waves-light">
                            <i class="ti-more"></i>
                        </a>
                    </div>
                    <div class="navbar-container container-fluid">
                        <ul class="nav-left">
                            <li>
                                <div class="sidebar_toggle"><a href="javascript:void(0)"><i class="ti-menu"></i></a></div>
                            </li>
                            <li>
                                <a href="#!" onclick="javascript:toggleFullScreen()" class="waves-effect waves-light">
                                    <i class="ti-fullscreen"></i>
                                </a>
                            </li>
                        </ul>
                        <ul class="nav-right">
                            <li>
                                <a href="../back_office/tracking.php" class="nav-btn nav-frontoffice waves-effect waves-light" title="Open Front Office">
                                    <i class="fa fa-home" aria-hidden="true"></i>&nbsp;Front Office
                                </a>
                            </li>
                            <li class="header-notification">
                                <a href="#!" class="waves-effect waves-light">
                                    <i class="ti-bell"></i>
                                    <span class="badge bg-c-red"></span>
                                </a>
                            </li>
                            <li class="user-profile header-notification">
                                <a href="#!" class="waves-effect waves-light">
                                    <img src="assets/images/avatar-4.jpg" class="img-radius" alt="User-Profile-Image">
                                    <span>John Doe</span>
                                    <i class="ti-angle-down"></i>
                                </a>
                                <ul class="show-notification profile-notification">
                                    <li class="waves-effect waves-light"><a href="#"><i class="ti-settings"></i> Settings</a></li>
                                    <li class="waves-effect waves-light"><a href="user-profile.html"><i class="ti-user"></i> Profile</a></li>
                                    <li class="waves-effect waves-light"><a href="auth-normal-sign-in.html"><i class="ti-layout-sidebar-left"></i> Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="pcoded-main-container">
                <div class="pcoded-wrapper">
                    <nav class="pcoded-navbar">
                        <div class="sidebar_toggle"><a href="#"><i class="icon-close icons"></i></a></div>
                        <div class="pcoded-inner-navbar main-menu">
                            <div class="">
                                <div class="main-menu-header">
                                    <img class="img-80 img-radius" src="assets/images/avatar-4.jpg" alt="User-Profile-Image">
                                    <div class="user-details">
                                        <span id="more-details">John Doe<i class="fa fa-caret-down"></i></span>
                                    </div>
                                </div>
                            </div>
                            <div class="pcoded-navigation-label">Navigation</div>
                            <ul class="pcoded-item pcoded-left-item">
                                <li class="">
                                    <a href="index.html" class="waves-effect waves-dark">
                                        <span class="pcoded-micon"><i class="ti-home"></i><b>D</b></span>
                                        <span class="pcoded-mtext">Dashboard</span>
                                        <span class="pcoded-mcaret"></span>
                                    </a>
                                </li>
                                <li class="active">
                                    <a href="tracking.php" class="waves-effect waves-dark">
                                        <span class="pcoded-micon"><i class="ti-target"></i><b>T</b></span>
                                        <span class="pcoded-mtext">Tracking</span>
                                        <span class="pcoded-mcaret"></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </nav>

                    <div class="pcoded-content">
                        <div class="page-header">
                            <div class="page-block">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="page-header-title">
                                            <h5 class="m-b-10">Tracking</h5>
                                            <p class="m-b-0">Long-term goals, weekly entries, and performance insights</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <ul class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="index.html"><i class="fa fa-home"></i></a></li>
                                            <li class="breadcrumb-item"><a href="#">Tracking</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pcoded-inner-content">
                            <div class="main-body">
                                <div class="page-wrapper">
                                    <div class="page-body">
                                        <div class="row">
                                            <div class="col-xl-12">
                                                <ul class="nav nav-tabs tracking-tabs" role="tablist">
                                                    <li class="nav-item">
                                                        <a class="nav-link active" data-toggle="tab" href="#tracking-data" role="tab" aria-controls="tracking-data" aria-selected="true">Tracking Data</a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link" data-toggle="tab" href="#tracking-statistics" role="tab" aria-controls="tracking-statistics" aria-selected="false">Statistics</a>
                                                    </li>
                                                </ul>
                                                <div class="tab-content tracking-panel">
                                                    <div class="tab-pane fade show active" id="tracking-data" role="tabpanel">
                                                        <div class="card mb-4">
                                                            <div class="card-header">
                                                                <h5 class="mb-0">User Information</h5>
                                                            </div>
                                                            <div class="card-block">
                                                                <h6 class="m-b-0">All users tracking data at a glance</h6>
                                                            </div>
                                                        </div>

                                                        <div class="card table-card">
                                                            <div class="card-header">
                                                                <h5 class="mb-0">All Users Tracking Summary</h5>
                                                            </div>
                                                            <div class="card-block">
                                                                <?php if (empty($allUsers)): ?>
                                                                    <p class="tracking-empty m-b-0">No users with tracking data found.</p>
                                                                <?php else: ?>
                                                                    <div class="table-responsive">
                                                                        <table class="table table-hover table-bordered mb-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>User ID</th>
                                                                                    <th>Goal Type</th>
                                                                                    <th>Initial Value</th>
                                                                                    <th>Target Value</th>
                                                                                    <th>Goal Status</th>
                                                                                    <th>Start Date</th>
                                                                                    <th>End Date</th>
                                                                                    <th>Weekly Entries</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <?php foreach ($allUsers as $userId => $userData): ?>
                                                                                    <tr style="cursor: pointer;" onclick="toggleUserDetails(<?php echo (int) $userId; ?>)">
                                                                                        <td><strong><?php echo h($userId); ?></strong></td>
                                                                                        <td><?php echo $userData['goal'] ? h($userData['goal']['type_obj'] ?? '—') : '—'; ?></td>
                                                                                        <td><?php echo $userData['goal'] ? h($userData['goal']['val_init_obj'] ?? '—') : '—'; ?></td>
                                                                                        <td><?php echo $userData['goal'] ? h($userData['goal']['val_cible_obj'] ?? '—') : '—'; ?></td>
                                                                                        <td><?php echo $userData['goal'] ? h($userData['goal']['status_obj'] ?? '—') : '—'; ?></td>
                                                                                        <td><?php echo $userData['goal'] ? h($userData['goal']['date_deb_obj'] ?? '—') : '—'; ?></td>
                                                                                        <td><?php echo $userData['goal'] ? h($userData['goal']['date_fin_obj'] ?? '—') : '—'; ?></td>
                                                                                        <td><?php echo count($userData['weeklyRows']); ?></td>
                                                                                    </tr>
                                                                                    <tr id="details-<?php echo (int) $userId; ?>" style="display: none;">
                                                                                        <td colspan="8">
                                                                                            <div style="padding: 20px; background-color: #f9f9f9; border-radius: 5px;">
                                                                                                <h6>User ID <?php echo h($userId); ?> - Detailed Information</h6>

                                                                                                <?php if ($userData['goal']): ?>
                                                                                                    <div style="margin-bottom: 20px;">
                                                                                                        <h6 style="margin-bottom: 10px;">Long-Term Goal Details:</h6>
                                                                                                        <div class="table-responsive">
                                                                                                            <table class="table table-bordered table-sm mb-0">
                                                                                                                <tbody>
                                                                                                                    <tr><th>ID Goal</th><td><?php echo h($userData['goal']['id_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>ID User</th><td><?php echo h($userData['goal']['id_user'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Type</th><td><?php echo h($userData['goal']['type_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Initial Value</th><td><?php echo h($userData['goal']['val_init_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Target Value</th><td><?php echo h($userData['goal']['val_cible_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Start Date</th><td><?php echo h($userData['goal']['date_deb_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>End Date</th><td><?php echo h($userData['goal']['date_fin_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Status</th><td><?php echo h($userData['goal']['status_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Reminder Frequency</th><td><?php echo h($userData['goal']['frequency_rappel_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Sport Consistency</th><td><?php echo h($userData['goal']['consistancy_sport_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Diet Consistency</th><td><?php echo h($userData['goal']['consistency_alim_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Calories Target</th><td><?php echo h($userData['goal']['obj_cal_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Fat Target</th><td><?php echo h($userData['goal']['obj_fat_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Protein Target</th><td><?php echo h($userData['goal']['obj_prot_obj'] ?? ''); ?></td></tr>
                                                                                                                    <tr><th>Carbs Target</th><td><?php echo h($userData['goal']['obj_carb_obj'] ?? ''); ?></td></tr>
                                                                                                                </tbody>
                                                                                                            </table>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                <?php else: ?>
                                                                                                    <p style="color: #666;">No long-term goal found for this user.</p>
                                                                                                <?php endif; ?>

                                                                                                <?php if (!empty($userData['weeklyRows'])): ?>
                                                                                                    <div>
                                                                                                        <h6 style="margin-bottom: 10px;">Weekly Tracking Entries (<?php echo count($userData['weeklyRows']); ?>):</h6>
                                                                                                        <div class="table-responsive">
                                                                                                            <table class="table table-hover table-bordered table-sm mb-0">
                                                                                                                <thead>
                                                                                                                    <tr>
                                                                                                                        <th>ID Suiv</th>
                                                                                                                        <th>Date</th>
                                                                                                                        <th>Weight</th>
                                                                                                                        <th>Calories</th>
                                                                                                                        <th>Fat</th>
                                                                                                                        <th>Protein</th>
                                                                                                                        <th>Carbs</th>
                                                                                                                        <th>Status</th>
                                                                                                                        <th>Water</th>
                                                                                                                        <th>Sleep</th>
                                                                                                                        <th>Steps</th>
                                                                                                                        <th>Note</th>
                                                                                                                    </tr>
                                                                                                                </thead>
                                                                                                                <tbody>
                                                                                                                    <?php foreach ($userData['weeklyRows'] as $row): ?>
                                                                                                                        <tr>
                                                                                                                            <td><?php echo h($row['id_suiv'] ?? ''); ?></td>
                                                                                                                            <td><?php echo h($row['date_suiv'] ?? ''); ?></td>
                                                                                                                            <td><?php echo h($row['poids_suiv'] ?? ''); ?></td>
                                                                                                                            <td><?php echo h($row['val_cal_suiv'] ?? ''); ?></td>
                                                                                                                            <td><?php echo h($row['val_fat_suiv'] ?? ''); ?></td>
                                                                                                                            <td><?php echo h($row['val_prot_suiv'] ?? ''); ?></td>
                                                                                                                            <td><?php echo h($row['val_carb_suiv'] ?? ''); ?></td>
                                                                                                                            <td><?php echo h($row['status_obj_quot_suiv'] ?? ''); ?></td>
                                                                                                                            <td><?php echo h($row['nb_verre_eau_suiv'] ?? ''); ?></td>
                                                                                                                            <td><?php echo h($row['nb_h_sommeil_suiv'] ?? ''); ?></td>
                                                                                                                            <td><?php echo h($row['nb_pas_suiv'] ?? ''); ?></td>
                                                                                                                            <td><?php echo h($row['note_suiv'] ?? ''); ?></td>
                                                                                                                        </tr>
                                                                                                                    <?php endforeach; ?>
                                                                                                                </tbody>
                                                                                                            </table>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                <?php else: ?>
                                                                                                    <p style="color: #666; margin-top: 15px;">No weekly tracking entries found for this user.</p>
                                                                                                <?php endif; ?>
                                                                                            </div>
                                                                                        </td>
                                                                                    </tr>
                                                                                <?php endforeach; ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade" id="tracking-statistics" role="tabpanel">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 mb-3">
                                                                <div class="tracking-stat-card">
                                                                    <small>Total users</small>
                                                                    <span class="tracking-stat-value"><?php echo h($totalUsers); ?></span>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-3 col-md-6 mb-3">
                                                                <div class="tracking-stat-card tracking-stat-card--alt">
                                                                    <small>Total goals</small>
                                                                    <span class="tracking-stat-value"><?php echo h($totalGoals); ?></span>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-3 col-md-6 mb-3">
                                                                <div class="tracking-stat-card tracking-stat-card--warm">
                                                                    <small>Weekly entries</small>
                                                                    <span class="tracking-stat-value"><?php echo h($totalWeeklyEntries); ?></span>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-3 col-md-6 mb-3">
                                                                <div class="tracking-stat-card tracking-stat-card--rose">
                                                                    <small>Avg. weekly entries / user</small>
                                                                    <span class="tracking-stat-value"><?php echo h($averageWeeklyEntries); ?></span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row tracking-chart-grid">
                                                            <div class="col-12 mb-4">
                                                                <div class="card tracking-chart-card">
                                                                    <div class="card-header">
                                                                        <h5 class="mb-0">Active User Retention</h5>
                                                                    </div>
                                                                    <div class="card-block">
                                                                        <?php if (empty($trackingChartData['retention']['labels'])): ?>
                                                                            <div class="tracking-chart-empty">No weekly activity was found to build a retention trend.</div>
                                                                        <?php else: ?>
                                                                            <div class="tracking-chart-stage">
                                                                                <div id="activeRetentionChart"></div>
                                                                            </div>
                                                                            <div class="tracking-chart-note">Retention is measured week over week, using the first observed active week as the baseline.</div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row tracking-chart-grid">
                                                            <div class="col-lg-6 mb-4">
                                                                <div class="card tracking-chart-card">
                                                                    <div class="card-header">
                                                                        <h5 class="mb-0">User Goal Distribution</h5>
                                                                    </div>
                                                                    <div class="card-block">
                                                                        <?php if (empty($trackingChartData['goalTypes'])): ?>
                                                                            <div class="tracking-chart-empty">No goal distribution data is available yet.</div>
                                                                        <?php else: ?>
                                                                            <div class="tracking-donut-wrap">
                                                                                <div class="tracking-chart-stage" style="min-height: 260px; max-width: 260px; margin: 0 auto;">
                                                                                    <div id="userGoalDistributionChart"></div>
                                                                                </div>
                                                                                <div class="tracking-donut-legend" id="userGoalDistributionLegend"></div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6 mb-4">
                                                                <div class="card tracking-chart-card">
                                                                    <div class="card-header">
                                                                        <h5 class="mb-0">Community Macro Averages</h5>
                                                                    </div>
                                                                    <div class="card-block">
                                                                        <?php if (empty($trackingChartData['macros'])): ?>
                                                                            <div class="tracking-chart-empty">No macro averages are available yet.</div>
                                                                        <?php else: ?>
                                                                            <div class="tracking-chart-stage">
                                                                                <div id="communityMacroAveragesChart"></div>
                                                                            </div>
                                                                            <div class="tracking-chart-note">Each group compares the community average and median for the main macro targets.</div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row tracking-chart-grid">
                                                            <div class="col-12 mb-4">
                                                                <div class="card tracking-chart-card">
                                                                    <div class="card-header">
                                                                        <h5 class="mb-0">Engagement vs. Weight Change</h5>
                                                                    </div>
                                                                    <div class="card-block">
                                                                        <?php if (empty($trackingChartData['scatter'])): ?>
                                                                            <div class="tracking-chart-empty">Not enough weekly tracking data exists to build the scatter plot.</div>
                                                                        <?php else: ?>
                                                                            <div class="tracking-chart-stage">
                                                                                <div id="engagementWeightScatterChart"></div>
                                                                            </div>
                                                                            <div class="tracking-chart-note">Higher points indicate more weight loss; lower points indicate weight gain over the recorded entries.</div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="styleSelector"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="assets/js/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="assets/js/jquery-ui/jquery-ui.min.js"></script>
    <script type="text/javascript" src="assets/js/popper.js/popper.min.js"></script>
    <script type="text/javascript" src="assets/js/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/pages/waves/js/waves.min.js"></script>
    <script type="text/javascript" src="assets/js/jquery-slimscroll/jquery.slimscroll.js"></script>
    <script src="assets/js/jquery.mCustomScrollbar.concat.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <script src="assets/js/vertical/vertical-layout.min.js"></script>
    <script type="text/javascript" src="assets/js/script.js"></script>
    <script>
        const trackingChartData = <?php echo json_encode($trackingChartData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        function svgElement(tagName, attributes = {}) {
            const element = document.createElementNS('http://www.w3.org/2000/svg', tagName);
            Object.entries(attributes).forEach(([attributeName, value]) => {
                element.setAttribute(attributeName, value);
            });
            return element;
        }

        function clearNode(node) {
            while (node.firstChild) {
                node.removeChild(node.firstChild);
            }
        }

        function numberOrZero(value) {
            return Number.isFinite(Number(value)) ? Number(value) : 0;
        }

        function niceMax(value) {
            if (value <= 0) {
                return 1;
            }

            const magnitude = Math.pow(10, Math.floor(Math.log10(value)));
            const normalized = value / magnitude;
            const stepped = normalized <= 1 ? 1 : normalized <= 2 ? 2 : normalized <= 5 ? 5 : 10;
            return stepped * magnitude;
        }

        function renderLineChart(containerId, labels, values) {
            const container = document.getElementById(containerId);
            if (!container || !labels.length || !values.length) {
                return;
            }

            clearNode(container);

            const width = 1000;
            const height = 320;
            const padding = { top: 24, right: 26, bottom: 52, left: 56 };
            const chartWidth = width - padding.left - padding.right;
            const chartHeight = height - padding.top - padding.bottom;
            const maxValue = niceMax(Math.max(...values.map(numberOrZero), 100));
            const yStep = maxValue / 5;

            const svg = svgElement('svg', {
                viewBox: `0 0 ${width} ${height}`,
                width: '100%',
                height: height,
                preserveAspectRatio: 'none'
            });

            const defs = svgElement('defs');
            const gradient = svgElement('linearGradient', { id: 'retentionGradient', x1: '0%', y1: '0%', x2: '0%', y2: '100%' });
            gradient.appendChild(svgElement('stop', { offset: '0%', 'stop-color': '#4f7cff', 'stop-opacity': '0.26' }));
            gradient.appendChild(svgElement('stop', { offset: '100%', 'stop-color': '#4f7cff', 'stop-opacity': '0.02' }));
            defs.appendChild(gradient);
            svg.appendChild(defs);

            for (let index = 0; index <= 5; index += 1) {
                const y = padding.top + chartHeight - (chartHeight / 5) * index;
                const value = Math.round(yStep * index);
                svg.appendChild(svgElement('line', {
                    x1: padding.left,
                    y1: y,
                    x2: width - padding.right,
                    y2: y,
                    stroke: '#dbe3f0',
                    'stroke-width': '1'
                }));

                const label = svgElement('text', {
                    x: padding.left - 10,
                    y: y + 4,
                    'text-anchor': 'end',
                    fill: '#6a768d',
                    'font-size': '11'
                });
                label.textContent = `${value}%`;
                svg.appendChild(label);
            }

            const points = values.map((value, index) => {
                const x = padding.left + (chartWidth * index / Math.max(labels.length - 1, 1));
                const y = padding.top + chartHeight - ((numberOrZero(value) / maxValue) * chartHeight);
                return { x, y };
            });

            const areaPath = [
                `M ${points[0].x} ${padding.top + chartHeight}`,
                `L ${points[0].x} ${points[0].y}`,
                ...points.slice(1).map(point => `L ${point.x} ${point.y}`),
                `L ${points[points.length - 1].x} ${padding.top + chartHeight}`,
                'Z'
            ].join(' ');

            const linePath = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');

            svg.appendChild(svgElement('path', {
                d: areaPath,
                fill: 'url(#retentionGradient)'
            }));
            svg.appendChild(svgElement('path', {
                d: linePath,
                fill: 'none',
                stroke: '#4f7cff',
                'stroke-width': '4',
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round'
            }));

            points.forEach((point, index) => {
                svg.appendChild(svgElement('circle', {
                    cx: point.x,
                    cy: point.y,
                    r: '6',
                    fill: '#ffffff',
                    stroke: '#4f7cff',
                    'stroke-width': '3'
                }));

                const valueLabel = svgElement('text', {
                    x: point.x,
                    y: point.y - 14,
                    'text-anchor': 'middle',
                    fill: '#243b7a',
                    'font-size': '12',
                    'font-weight': '700'
                });
                valueLabel.textContent = `${numberOrZero(values[index]).toFixed(1)}%`;
                svg.appendChild(valueLabel);

                const xLabel = svgElement('text', {
                    x: point.x,
                    y: height - 20,
                    'text-anchor': 'middle',
                    fill: '#6a768d',
                    'font-size': '11'
                });
                xLabel.textContent = labels[index];
                svg.appendChild(xLabel);
            });

            container.appendChild(svg);
        }

        function renderDonutChart(containerId, legendId, segments) {
            const container = document.getElementById(containerId);
            const legendContainer = document.getElementById(legendId);
            if (!container || !legendContainer || !segments.length) {
                return;
            }

            clearNode(container);
            clearNode(legendContainer);

            const total = segments.reduce((sum, segment) => sum + numberOrZero(segment.value), 0);
            if (total <= 0) {
                container.innerHTML = '<div class="tracking-chart-empty">No goal data available for the distribution chart.</div>';
                return;
            }

            const colors = ['#4f7cff', '#4ecdc4', '#ffb347', '#ff7e9a', '#7a6ff0', '#2ecc71', '#f15b5b'];
            let currentAngle = 0;
            const slices = segments.map((segment, index) => {
                const value = numberOrZero(segment.value);
                const angle = (value / total) * 360;
                const start = currentAngle;
                currentAngle += angle;
                return `${colors[index % colors.length]} ${start}deg ${currentAngle}deg`;
            });

            const donut = document.createElement('div');
            donut.className = 'tracking-donut';
            donut.style.background = `conic-gradient(${slices.join(', ')})`;

            const center = document.createElement('div');
            center.className = 'tracking-donut-center';
            center.innerHTML = `<strong>${segments.length}</strong><span>Goal Types</span>`;
            donut.appendChild(center);

            container.appendChild(donut);

            segments.forEach((segment, index) => {
                const count = numberOrZero(segment.value);
                const percentage = total > 0 ? ((count / total) * 100).toFixed(1) : '0.0';
                const legendItem = document.createElement('div');
                legendItem.className = 'tracking-legend-item';
                legendItem.innerHTML = `
                    <div class="tracking-legend-label">
                        <span class="tracking-legend-swatch" style="background:${colors[index % colors.length]}"></span>
                        <span>${segment.label}</span>
                    </div>
                    <div class="tracking-legend-value">${count} (${percentage}%)</div>
                `;
                legendContainer.appendChild(legendItem);
            });
        }

        function renderGroupedBarChart(containerId, categories) {
            const container = document.getElementById(containerId);
            if (!container || !categories.length) {
                return;
            }

            clearNode(container);

            const width = 1000;
            const height = 320;
            const padding = { top: 28, right: 26, bottom: 54, left: 58 };
            const chartWidth = width - padding.left - padding.right;
            const chartHeight = height - padding.top - padding.bottom;
            const series = [
                { key: 'average', label: 'Average', color: '#4f7cff' },
                { key: 'median', label: 'Median', color: '#4ecdc4' }
            ];
            const maxValue = niceMax(Math.max(...categories.flatMap(category => series.map(item => numberOrZero(category[item.key]))), 1));
            const yStep = maxValue / 5;
            const svg = svgElement('svg', {
                viewBox: `0 0 ${width} ${height}`,
                width: '100%',
                height: height,
                preserveAspectRatio: 'none'
            });

            for (let index = 0; index <= 5; index += 1) {
                const y = padding.top + chartHeight - (chartHeight / 5) * index;
                const value = Math.round(yStep * index);
                svg.appendChild(svgElement('line', {
                    x1: padding.left,
                    y1: y,
                    x2: width - padding.right,
                    y2: y,
                    stroke: '#dbe3f0',
                    'stroke-width': '1'
                }));

                const label = svgElement('text', {
                    x: padding.left - 10,
                    y: y + 4,
                    'text-anchor': 'end',
                    fill: '#6a768d',
                    'font-size': '11'
                });
                label.textContent = `${value}`;
                svg.appendChild(label);
            }

            const groupWidth = chartWidth / categories.length;
            const barGap = 8;
            const barWidth = Math.max(16, (groupWidth - barGap * 3) / series.length);

            categories.forEach((category, categoryIndex) => {
                const groupStart = padding.left + (groupWidth * categoryIndex);
                const baseline = padding.top + chartHeight;

                series.forEach((item, seriesIndex) => {
                    const value = numberOrZero(category[item.key]);
                    const barHeight = (value / maxValue) * chartHeight;
                    const x = groupStart + barGap + (seriesIndex * (barWidth + barGap));
                    const y = baseline - barHeight;
                    const rect = svgElement('rect', {
                        x,
                        y,
                        width: barWidth,
                        height: barHeight,
                        rx: '10',
                        fill: item.color
                    });
                    rect.appendChild(svgElement('title', {}));
                    rect.firstChild.textContent = `${item.label}: ${value}`;
                    svg.appendChild(rect);
                });

                const label = svgElement('text', {
                    x: groupStart + (groupWidth / 2),
                    y: height - 20,
                    'text-anchor': 'middle',
                    fill: '#6a768d',
                    'font-size': '11'
                });
                label.textContent = category.label;
                svg.appendChild(label);
            });

            const legend = document.createElement('div');
            legend.className = 'tracking-chart-note';
            legend.innerHTML = '<span style="display:inline-flex;align-items:center;gap:10px;margin-right:16px;"><span class="tracking-legend-swatch" style="background:#4f7cff"></span>Average</span><span style="display:inline-flex;align-items:center;gap:10px;"><span class="tracking-legend-swatch" style="background:#4ecdc4"></span>Median</span>';
            container.appendChild(svg);
            container.appendChild(legend);
        }

        function renderScatterChart(containerId, points) {
            const container = document.getElementById(containerId);
            if (!container || !points.length) {
                return;
            }

            clearNode(container);

            const width = 1000;
            const height = 320;
            const padding = { top: 28, right: 28, bottom: 58, left: 62 };
            const chartWidth = width - padding.left - padding.right;
            const chartHeight = height - padding.top - padding.bottom;
            const maxX = niceMax(Math.max(...points.map(point => numberOrZero(point.x)), 1));
            const minY = Math.min(...points.map(point => numberOrZero(point.y)));
            const maxY = Math.max(...points.map(point => numberOrZero(point.y)));
            const yRange = maxY - minY || 1;
            const yPadding = Math.max(1, Math.round(yRange * 0.15));
            const yMin = minY - yPadding;
            const yMax = maxY + yPadding;
            const svg = svgElement('svg', {
                viewBox: `0 0 ${width} ${height}`,
                width: '100%',
                height: height,
                preserveAspectRatio: 'none'
            });

            for (let index = 0; index <= 5; index += 1) {
                const y = padding.top + chartHeight - (chartHeight / 5) * index;
                const value = yMin + ((yMax - yMin) / 5) * index;
                svg.appendChild(svgElement('line', {
                    x1: padding.left,
                    y1: y,
                    x2: width - padding.right,
                    y2: y,
                    stroke: '#dbe3f0',
                    'stroke-width': '1'
                }));

                const label = svgElement('text', {
                    x: padding.left - 10,
                    y: y + 4,
                    'text-anchor': 'end',
                    fill: '#6a768d',
                    'font-size': '11'
                });
                label.textContent = `${value.toFixed(1)} kg`;
                svg.appendChild(label);
            }

            svg.appendChild(svgElement('line', {
                x1: padding.left,
                y1: padding.top + chartHeight,
                x2: width - padding.right,
                y2: padding.top + chartHeight,
                stroke: '#94a3c1',
                'stroke-width': '1.3'
            }));
            svg.appendChild(svgElement('line', {
                x1: padding.left,
                y1: padding.top,
                x2: padding.left,
                y2: padding.top + chartHeight,
                stroke: '#94a3c1',
                'stroke-width': '1.3'
            }));

            points.forEach(point => {
                const x = padding.left + ((numberOrZero(point.x) / maxX) * chartWidth);
                const y = padding.top + chartHeight - (((numberOrZero(point.y) - yMin) / (yMax - yMin)) * chartHeight);
                const accent = numberOrZero(point.y) >= 0 ? '#4f7cff' : '#ff7e9a';

                svg.appendChild(svgElement('circle', {
                    cx: x,
                    cy: y,
                    r: '8',
                    fill: accent,
                    opacity: '0.22'
                }));
                const pointCircle = svgElement('circle', {
                    cx: x,
                    cy: y,
                    r: '5',
                    fill: accent,
                    stroke: '#ffffff',
                    'stroke-width': '2'
                });
                const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                title.textContent = `${point.label}: engagement ${numberOrZero(point.x)}, weight change ${numberOrZero(point.y).toFixed(1)} kg`;
                pointCircle.appendChild(title);
                svg.appendChild(pointCircle);
            });

            const xLabel = svgElement('text', {
                x: width / 2,
                y: height - 14,
                'text-anchor': 'middle',
                fill: '#6a768d',
                'font-size': '11'
            });
            xLabel.textContent = 'Engagement level (weekly entries)';
            svg.appendChild(xLabel);

            const yLabel = svgElement('text', {
                x: 18,
                y: height / 2,
                transform: `rotate(-90 18 ${height / 2})`,
                'text-anchor': 'middle',
                fill: '#6a768d',
                'font-size': '11'
            });
            yLabel.textContent = 'Weight change (kg)';
            svg.appendChild(yLabel);

            container.appendChild(svg);
        }

        function renderTrackingCharts() {
            renderLineChart(
                'activeRetentionChart',
                trackingChartData.retention.labels || [],
                trackingChartData.retention.values || []
            );

            renderDonutChart(
                'userGoalDistributionChart',
                'userGoalDistributionLegend',
                (trackingChartData.goalTypes || []).map(item => ({ label: item.label, value: item.value }))
            );

            renderGroupedBarChart(
                'communityMacroAveragesChart',
                (trackingChartData.macros || []).map(item => ({
                    label: item.label,
                    average: item.average,
                    median: item.median
                }))
            );

            renderScatterChart(
                'engagementWeightScatterChart',
                trackingChartData.scatter || []
            );
        }

        let trackingChartResizeTimer = null;
        window.addEventListener('resize', function () {
            window.clearTimeout(trackingChartResizeTimer);
            trackingChartResizeTimer = window.setTimeout(renderTrackingCharts, 150);
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderTrackingCharts);
        } else {
            renderTrackingCharts();
        }

        function toggleUserDetails(userId) {
            const detailsRow = document.getElementById('details-' + userId);
            if (detailsRow) {
                if (detailsRow.style.display === 'none') {
                    detailsRow.style.display = 'table-row';
                } else {
                    detailsRow.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
