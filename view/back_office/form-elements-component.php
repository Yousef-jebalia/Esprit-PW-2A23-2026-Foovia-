<?php
session_start();
include '../../controller/ObjectifLongTerme_Controller.php';

// Initialisation des variables
$error_message = '';
$success_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $data = [
        'id_obj' => $_POST['id_obj'] ?? null,
        'id_user' => $_POST['id_user'] ?? null,
        'type_obj' => $_POST['type_obj'] ?? null,
        'val_init_obj' => $_POST['val_init_obj'] ?? null,
        'val_cible_obj' => $_POST['val_cible_obj'] ?? null,
        'date_deb_obj' => $_POST['date_deb_obj'] ?? null,
        'date_fin_obj' => $_POST['date_fin_obj'] ?? null,
        'status_obj' => $_POST['status_obj'] ?? 'en_attente',
        'frequency_rappel_obj' => $_POST['frequency_rappel_obj'] ?? null,
        'consistancy_sport_obj' => $_POST['consistancy_sport_obj'] ?? 0,
        'consistency_alim_obj' => $_POST['consistency_alim_obj'] ?? 0,
        'obj_cal_obj' => $_POST['obj_cal_obj'] ?? null,
        'obj_fat_obj' => $_POST['obj_fat_obj'] ?? null,
        'obj_prot_obj' => $_POST['obj_prot_obj'] ?? null,
        'obj_carb_obj' => $_POST['obj_carb_obj'] ?? null
    ];
    
    // Validation des données
    $errors = [];
    
    // Vérification que les champs obligatoires ne sont pas vides
    if (empty($data['id_obj'])) $errors[] = "L'ID de l'objectif est requis";
    if (empty($data['id_user'])) $errors[] = "L'ID utilisateur est requis";
    if (empty($data['type_obj'])) $errors[] = "Le type d'objectif est requis";
    if (empty($data['val_init_obj'])) $errors[] = "La valeur initiale est requise";
    if (empty($data['val_cible_obj'])) $errors[] = "La valeur cible est requise";
    if (empty($data['date_deb_obj'])) $errors[] = "La date de début est requise";
    if (empty($data['date_fin_obj'])) $errors[] = "La date de fin est requise";
    
    // Vérification des valeurs positives
    $positive_fields = ['val_cible_obj', 'val_init_obj', 'obj_cal_obj', 'obj_fat_obj', 'obj_prot_obj', 'obj_carb_obj', 'frequency_rappel_obj'];
    foreach ($positive_fields as $field) {
        if (!empty($data[$field]) && $data[$field] <= 0) {
            $errors[] = "Le champ doit être strictement positif";
        }
    }
    
    // Vérification des dates
    if (!empty($data['date_deb_obj']) && !empty($data['date_fin_obj'])) {
        $date_deb = new DateTime($data['date_deb_obj']);
        $date_fin = new DateTime($data['date_fin_obj']);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($date_deb < $today) $errors[] = "La date de début ne peut pas être antérieure à aujourd'hui";
        if ($date_deb > $date_fin) $errors[] = "La date de début ne peut pas être postérieure à la date de fin";
        
        $diff = $date_deb->diff($date_fin);
        if ($diff->days < 30) $errors[] = "La durée minimale de l'objectif est de 30 jours";
    }
    
    // Vérification valeur cible selon type
    if (!empty($data['type_obj']) && !empty($data['val_cible_obj']) && !empty($data['val_init_obj'])) {
        $type = $data['type_obj'];
        $val_cible = floatval($data['val_cible_obj']);
        $val_init = floatval($data['val_init_obj']);
        
        if ($type == 'prise_de_poids' && $val_cible <= $val_init) {
            $errors[] = "Pour une prise de poids, la valeur cible doit être supérieure à la valeur initiale";
        }
        if ($type == 'perte_de_poids' && $val_cible >= $val_init) {
            $errors[] = "Pour une perte de poids, la valeur cible doit être inférieure à la valeur initiale";
        }
        if ($type == 'maintien_de_poids' && abs($val_cible - $val_init) > 0.5) {
            $errors[] = "Pour un maintien de poids, la valeur cible doit être proche de la valeur initiale (±0.5)";
        }
    }
    
    // Si pas d'erreurs, insertion en base de données
    if (empty($errors)) {
        try {
            // Création de l'objet ObjectifLongTerme
            $objectif = new ObjectifLongTerme(
                $data['id_obj'],
                $data['id_user'],
                $data['type_obj'],
                floatval($data['val_cible_obj']),
                floatval($data['val_init_obj']),
                $data['date_deb_obj'],
                $data['date_fin_obj'],
                $data['status_obj'],
                intval($data['frequency_rappel_obj'] ?? 0),
                intval($data['consistancy_sport_obj'] ?? 0),
                intval($data['consistency_alim_obj'] ?? 0),
                floatval($data['obj_cal_obj'] ?? 0),
                floatval($data['obj_fat_obj'] ?? 0),
                floatval($data['obj_prot_obj'] ?? 0),
                floatval($data['obj_carb_obj'] ?? 0)
            );
            
            // Insertion via le controller
            $controller = new ObjectifLongTerme_Controller();
            
            // Modification de la méthode add_objectif pour accepter les données
            $sql = "INSERT INTO objectiflongterme (id_obj, id_user, type_obj, val_cible_obj, val_init_obj, date_deb_obj, date_fin_obj, status_obj, frequency_rappel_obj, consistancy_sport_obj, consistency_alim_obj, obj_cal_obj, obj_fat_obj, obj_prot_obj, obj_carb_obj) 
                    VALUES (:id_obj, :id_user, :type_obj, :val_cible_obj, :val_init_obj, :date_deb_obj, :date_fin_obj, :status_obj, :frequency_rappel_obj, :consistancy_sport_obj, :consistency_alim_obj, :obj_cal_obj, :obj_fat_obj, :obj_prot_obj, :obj_carb_obj)";
            
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            $query->execute([
                'id_obj' => $objectif->getIdObj(),
                'id_user' => $objectif->getIdUser(),
                'type_obj' => $objectif->getTypeObj(),
                'val_cible_obj' => $objectif->getValCibleObj(),
                'val_init_obj' => $objectif->getValInitObj(),
                'date_deb_obj' => $objectif->getDateDebObj(),
                'date_fin_obj' => $objectif->getDateFinObj(),
                'status_obj' => $objectif->getStatusObj(),
                'frequency_rappel_obj' => $objectif->getFrequencyRappelObj(),
                'consistancy_sport_obj' => $objectif->getConsistancySportObj(),
                'consistency_alim_obj' => $objectif->getConsistencyAlimObj(),
                'obj_cal_obj' => $objectif->getObjCalObj(),
                'obj_fat_obj' => $objectif->getObjFatObj(),
                'obj_prot_obj' => $objectif->getObjProtObj(),
                'obj_carb_obj' => $objectif->getObjCarbObj()
            ]);

            header('Location: ../front_office/index.html');
            exit;
            
        } catch (Exception $e) {
            $error_message = "❌ Erreur lors de l'insertion : " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>❌ ", $errors);
        $error_message = "❌ " . $error_message;
    }
}

// Récupération de l'ID utilisateur connecté (exemple)
$user_id = $_SESSION['user_id'] ?? 1; // À adapter selon votre système d'authentification
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

    <meta name="keywords"
        content="bootstrap, bootstrap admin template, admin theme, admin dashboard, dashboard template, admin template, responsive" />
    <meta name="author" content="Codedthemes" />
    <!-- Favicon icon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <!-- Google font-->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">
    <!-- waves.css -->
    <link rel="stylesheet" href="assets/pages/waves/css/waves.min.css" type="text/css" media="all">
    <!-- Required Fremwork -->
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap/css/bootstrap.min.css">
    <!-- themify-icons line icon -->
    <link rel="stylesheet" type="text/css" href="assets/icon/themify-icons/themify-icons.css">
    <!-- ico font -->
    <link rel="stylesheet" type="text/css" href="assets/icon/icofont/css/icofont.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css" href="assets/icon/font-awesome/css/font-awesome.min.css">
    <!-- Style.css -->
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="assets/css/jquery.mCustomScrollbar.css">

    <style>
        .form-group.row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .form-group.row .col-sm-2 {
            flex: 0 0 16.666667%;
            max-width: 16.666667%;
        }

        .form-group.row .col-sm-10 {
            flex: 0 0 83.333333%;
            max-width: 83.333333%;
        }

        @media (max-width: 768px) {

            .form-group.row .col-sm-2,
            .form-group.row .col-sm-10 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>

</head>

<body>
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
                                        <span class="input-group-prepend search-close"><i
                                                class="ti-close input-group-text"></i></span>
                                        <input type="text" class="form-control" placeholder="Enter Keyword">
                                        <span class="input-group-append search-btn"><i
                                                class="ti-search input-group-text"></i></span>
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
                                <div class="sidebar_toggle"><a href="javascript:void(0)"><i class="ti-menu"></i></a>
                                </div>
                            </li>
                            <li>
                                <a href="#!" onclick="javascript:toggleFullScreen()" class="waves-effect waves-light">
                                    <i class="ti-fullscreen"></i>
                                </a>
                            </li>
                        </ul>
                        <ul class="nav-right">
                            <li class="header-notification">
                                <a href="#!" class="waves-effect waves-light">
                                    <i class="ti-bell"></i>
                                    <span class="badge bg-c-red"></span>
                                </a>
                                <ul class="show-notification">
                                    <li>
                                        <h6>Notifications</h6>
                                        <label class="label label-danger">New</label>
                                    </li>
                                    <li class="waves-effect waves-light">
                                        <div class="media">
                                            <img class="d-flex align-self-center img-radius"
                                                src="assets/images/avatar-2.jpg" alt="Generic placeholder image">
                                            <div class="media-body">
                                                <h5 class="notification-user">John Doe</h5>
                                                <p class="notification-msg">Lorem ipsum dolor sit amet, consectetuer
                                                    elit.</p>
                                                <span class="notification-time">30 minutes ago</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="waves-effect waves-light">
                                        <div class="media">
                                            <img class="d-flex align-self-center img-radius"
                                                src="assets/images/avatar-4.jpg" alt="Generic placeholder image">
                                            <div class="media-body">
                                                <h5 class="notification-user">Joseph William</h5>
                                                <p class="notification-msg">Lorem ipsum dolor sit amet, consectetuer
                                                    elit.</p>
                                                <span class="notification-time">30 minutes ago</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="waves-effect waves-light">
                                        <div class="media">
                                            <img class="d-flex align-self-center img-radius"
                                                src="assets/images/avatar-3.jpg" alt="Generic placeholder image">
                                            <div class="media-body">
                                                <h5 class="notification-user">Sara Soudein</h5>
                                                <p class="notification-msg">Lorem ipsum dolor sit amet, consectetuer
                                                    elit.</p>
                                                <span class="notification-time">30 minutes ago</span>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </li>
                            <li class="user-profile header-notification">
                                <a href="#!" class="waves-effect waves-light">
                                    <img src="assets/images/avatar-4.jpg" class="img-radius" alt="User-Profile-Image">
                                    <span>John Doe</span>
                                    <i class="ti-angle-down"></i>
                                </a>
                                <ul class="show-notification profile-notification">
                                    <li class="waves-effect waves-light">
                                        <a href="#!">
                                            <i class="ti-settings"></i> Settings
                                        </a>
                                    </li>
                                    <li class="waves-effect waves-light">
                                        <a href="user-profile.html">
                                            <i class="ti-user"></i> Profile
                                        </a>
                                    </li>
                                    <li class="waves-effect waves-light">
                                        <a href="email-inbox.html">
                                            <i class="ti-email"></i> My Messages
                                        </a>
                                    </li>
                                    <li class="waves-effect waves-light">
                                        <a href="auth-lock-screen.html">
                                            <i class="ti-lock"></i> Lock Screen
                                        </a>
                                    </li>
                                    <li class="waves-effect waves-light">
                                        <a href="auth-normal-sign-in.html">
                                            <i class="ti-layout-sidebar-left"></i> Logout
                                        </a>
                                    </li>
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
                            <div class="main-menu-header">
                                <img class="img-80 img-radius" src="assets/images/avatar-4.jpg"
                                    alt="User-Profile-Image">
                                <div class="user-details">
                                    <span id="more-details">John Doe<i class="fa fa-caret-down"></i></span>
                                </div>
                            </div>
                            <div class="main-menu-content">
                                <ul>
                                    <li class="more-details">
                                        <a href="user-profile.html"><i class="ti-user"></i>View Profile</a>
                                        <a href="#!"><i class="ti-settings"></i>Settings</a>
                                        <a href="auth-normal-sign-in.html"><i
                                                class="ti-layout-sidebar-left"></i>Logout</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="p-15 p-b-0">
                                <form class="form-material">
                                    <div class="form-group form-primary">
                                        <input type="text" name="footer-email" class="form-control">
                                        <span class="form-bar"></span>
                                        <label class="float-label"><i class="fa fa-search m-r-10"></i>Search
                                            Friend</label>
                                    </div>
                                </form>
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
                            </ul>
                            <div class="pcoded-navigation-label">UI Element</div>
                            <ul class="pcoded-item pcoded-left-item">
                                <li class="pcoded-hasmenu">
                                    <a href="javascript:void(0)" class="waves-effect waves-dark">
                                        <span class="pcoded-micon"><i class="ti-layout-grid2-alt"></i><b>BC</b></span>
                                        <span class="pcoded-mtext">Basic</span>
                                        <span class="pcoded-mcaret"></span>
                                    </a>
                                    <ul class="pcoded-submenu">
                                        <li class=" ">
                                            <a href="breadcrumb.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                                                <span class="pcoded-mtext">Breadcrumbs</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                        <li class=" ">
                                            <a href="button.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                                                <span class="pcoded-mtext">Button</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                        <li class="">
                                            <a href="accordion.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                                                <span class="pcoded-mtext">Accordion</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                        <li class=" ">
                                            <a href="tabs.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                                                <span class="pcoded-mtext">Tabs</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                        <li class=" ">
                                            <a href="color.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                                                <span class="pcoded-mtext">Color</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                        <li class=" ">
                                            <a href="label-badge.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                                                <span class="pcoded-mtext">Label Badge</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                        <li class=" ">
                                            <a href="tooltip.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                                                <span class="pcoded-mtext">Tooltip And Popover</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                        <li class=" ">
                                            <a href="typography.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                                                <span class="pcoded-mtext">Typography</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                        <li class=" ">
                                            <a href="notification.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                                                <span class="pcoded-mtext">Notifications</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                            <div class="pcoded-navigation-label">Forms</div>
                            <ul class="pcoded-item pcoded-left-item">
                                <li class="active">
                                    <a href="form-elements-component.php" class="waves-effect waves-dark">
                                        <span class="pcoded-micon"><i class="ti-layers"></i><b>FC</b></span>
                                        <span class="pcoded-mtext">Form</span>
                                        <span class="pcoded-mcaret"></span>
                                    </a>
                                </li>
                            </ul>
                            <div class="pcoded-navigation-label">Tables</div>
                            <ul class="pcoded-item pcoded-left-item">
                                <li class="">
                                    <a href="bs-basic-table.html" class="waves-effect waves-dark">
                                        <span class="pcoded-micon"><i class="ti-receipt"></i><b>B</b></span>
                                        <span class="pcoded-mtext">Table</span>
                                        <span class="pcoded-mcaret"></span>
                                    </a>
                                </li>
                            </ul>
                            <div class="pcoded-navigation-label">Chart And Maps</div>
                            <ul class="pcoded-item pcoded-left-item">
                                <li class="">
                                    <a href="chart-morris.html" class="waves-effect waves-dark">
                                        <span class="pcoded-micon"><i class="ti-bar-chart-alt"></i><b>C</b></span>
                                        <span class="pcoded-mtext">Charts</span>
                                        <span class="pcoded-mcaret"></span>
                                    </a>
                                </li>
                                <li class="">
                                    <a href="map-google.html" class="waves-effect waves-dark">
                                        <span class="pcoded-micon"><i class="ti-map-alt"></i><b>M</b></span>
                                        <span class="pcoded-mtext">Maps</span>
                                        <span class="pcoded-mcaret"></span>
                                    </a>
                                </li>
                            </ul>
                            <div class="pcoded-navigation-label">Pages</div>
                            <ul class="pcoded-item pcoded-left-item">
                                <li class="pcoded-hasmenu ">
                                    <a href="javascript:void(0)" class="waves-effect waves-dark">
                                        <span class="pcoded-micon"><i class="ti-id-badge"></i><b>A</b></span>
                                        <span class="pcoded-mtext">Pages</span>
                                        <span class="pcoded-mcaret"></span>
                                    </a>
                                    <ul class="pcoded-submenu">
                                        <li class="">
                                            <a href="auth-normal-sign-in.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                                                <span class="pcoded-mtext">Login</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                        <li class="">
                                            <a href="auth-sign-up.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                                                <span class="pcoded-mtext">Registration</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                        <li class="">
                                            <a href="sample-page.html" class="waves-effect waves-dark">
                                                <span class="pcoded-micon"><i
                                                        class="ti-layout-sidebar-left"></i><b>S</b></span>
                                                <span class="pcoded-mtext">Sample Page</span>
                                                <span class="pcoded-mcaret"></span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </nav>

                    <div class="pcoded-content">
                        <div class="pcoded-inner-content">
                            <div class="main-body">
                                <div class="page-wrapper">
                                    <div class="page-body">
                                        <div class="row">
                                            <div class="col-md-12 col-xl-10">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h5> Objectif Long Terme</h5>
                                                    </div>
                                                    <div class="card-block">
                                                        <?php if (!empty($error_message)): ?>
                                                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                                <?php echo $error_message; ?>
                                                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
    
                                                        <?php if (!empty($success_message)): ?>
                                                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                                <?php echo $success_message; ?>
                                                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                        <form id="objectifForm" method="POST" action="">
                                                            <h6 class="sub-title">Identification</h6>
                                                            <div class="row">
                                                                <!--id objecttif-->
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>ID Objectif</label>
                                                                        <input type="number" class="form-control" id="id_obj" name="id_obj" placeholder="Ex: 1001" min="1" max="9999" oninput="if(this.value.length > 4) this.value = this.value.slice(0,4);">
                                                                    </div>
                                                                </div>
                                                                <!--id utilisateur-->
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>ID Utilisateur</label>
                                                                        <input type="text" class="form-control" id="id_user" name="id_user" value="<?php echo $user_id; ?>" readonly>
                                                                    </div>
                                                                </div>
                                                            </div>
                                        
                                                            <h6 class="sub-title">Type d'objectif et Valeurs</h6>
                                                            <div class="form-group">
                                                                <!--type d'objectif-->
                                                                <label>Type d'objectif</label>
                                                                <select class="form-control" id="type_obj" name="type_obj">
                                                                    <option value="">Sélectionner un type</option>
                                                                    <option value="prise_de_poids">Prise de poids</option>
                                                                    <option value="perte_de_poids">Perte de poids</option>
                                                                    <option value="maintien_de_poids">Maintien de poids</option>
                                                                </select>
                                                            </div>
                                                            <div class="row">
                                                                <!--valeur initiale-->
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Valeur initiale (kg)</label>
                                                                        <input type="number" class="form-control" id="val_init_obj" name="val_init_obj" step="0.01" min="0.01" placeholder="Ex: 75.5" required>
                                                                        <small id="initError" class="form-text text-danger" style="display:none;"></small>
                                                                    </div>
                                                                </div>
                                                                <!--valeur cible-->
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Valeur cible (kg)</label>
                                                                        <input type="number" class="form-control" id="val_cible_obj" name="val_cible_obj" step="0.01" min="0.01" placeholder="Ex: 68.0" required>
                                                                        <small id="cibleError" class="form-text text-danger" style="display:none;"></small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!--statut-->
                                                            <div class="form-group">
                                                                <label>Statut</label>
                                                                <input type="text" class="form-control" id="status_obj" name="status_obj" value="en_attente" readonly>
                                                            </div>
                                        
                                                            <h6 class="sub-title">Période</h6>
                                                            <div class="row">
                                                                <!--date debut-->
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Date début</label>
                                                                        <input type="date" class="form-control" id="date_deb_obj" name="date_deb_obj" required>
                                                                    </div>
                                                                </div>
                                                                <!--date fin-->
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Date fin</label>
                                                                        <input type="date" class="form-control" id="date_fin_obj" name="date_fin_obj" required>
                                                                        <small id="dateError" class="form-text text-danger" style="display:none;"></small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                        
                                                            <h6 class="sub-title">Rappels et Suivi</h6>
                                                            <div class="row">
                                                                <!--frequence rappel-->
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Fréquence rappel (jours)</label>
                                                                        <input type="number" class="form-control" id="frequency_rappel_obj" name="frequency_rappel_obj" min="1" placeholder="Ex: 7">
                                                                    </div>
                                                                </div>
                                                                <!--consistance sport-->
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Consistance sport </label>
                                                                        <input type="number" class="form-control" id="consistancy_sport_obj" name="consistancy_sport_obj" value="0" readonly>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!--consistance aliment-->
                                                            <div class="form-group">
                                                                <label>Consistance alimentation </label>
                                                                <input type="number" class="form-control" id="consistency_alim_obj" name="consistency_alim_obj" value="0" readonly>
                                                            </div>
                                        
                                                            <h6 class="sub-title">Objectifs Nutritionnels</h6>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Calories (kcal)</label>
                                                                        <input type="number" class="form-control" id="obj_cal_obj" name="obj_cal_obj" min="1" step="1" placeholder="Ex: 2000">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Lipides (g)</label>
                                                                        <input type="number" class="form-control" id="obj_fat_obj" name="obj_fat_obj" min="0.1" step="0.1" placeholder="Ex: 65">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Protéines (g)</label>
                                                                        <input type="number" class="form-control" id="obj_prot_obj" name="obj_prot_obj" min="0.1" step="0.1" placeholder="Ex: 150">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Glucides (g)</label>
                                                                        <input type="number" class="form-control" id="obj_carb_obj" name="obj_carb_obj" min="0.1" step="0.1" placeholder="Ex: 250">
                                                                    </div>
                                                                </div>
                                                            </div>
                                        
                                                            <div class="form-group m-b-0 text-right">
                                                                <button type="submit" class="btn btn-primary waves-effect waves-light">Enregistrer</button>
                                                            </div>
                                                        </form>
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
            </div>
        </div>
    </div>


    <script>
        // Récupération des éléments du formulaire
        const typeObjSelect = document.querySelector('select[name="type_obj"]');
        const valCibleInput = document.getElementById('val_cible_obj');
        const valInitInput = document.getElementById('val_init_obj');
        const cibleError = document.getElementById('cibleError');
        const initError = document.getElementById('initError');
        
        // Éléments pour les dates
        const dateDebInput = document.getElementById('date_deb_obj');
        const dateFinInput = document.getElementById('date_fin_obj');
        const dateErrorSpan = document.getElementById('dateError');
        
        // Éléments pour les champs positifs
        const positiveFields = ['val_cible_obj', 'val_init_obj', 'obj_cal_obj', 'obj_fat_obj', 'obj_prot_obj', 'obj_carb_obj', 'frequency_rappel_obj'];
        
        // ============ FONCTION POUR DÉFINIR LA DATE MINIMALE (DATE SYSTÈME) ============
        function setMinDates() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            const todayString = year + '-' + month + '-' + day;
            
            if (dateDebInput) {
                dateDebInput.setAttribute('min', todayString);
            }
            if (dateFinInput) {
                dateFinInput.setAttribute('min', todayString);
            }
        }
        
        // ============ FONCTION DE VALIDATION DE LA VALEUR CIBLE ============
        function validateValeurCible() {
            const typeObj = typeObjSelect ? typeObjSelect.value : '';
            const valCible = parseFloat(valCibleInput ? valCibleInput.value : 0);
            const valInit = parseFloat(valInitInput ? valInitInput.value : 0);
            
            if (valCibleInput) {
                valCibleInput.style.borderColor = '';
                valCibleInput.style.borderWidth = '';
            }
            if (cibleError) cibleError.style.display = 'none';
            if (initError) initError.style.display = 'none';
            
            if (isNaN(valCible) || isNaN(valInit)) {
                if (valCibleInput && (isNaN(valCible) || valCibleInput.value === '')) {
                    valCibleInput.style.borderColor = '#ffc107';
                    valCibleInput.style.borderWidth = '2px';
                }
                if (valInitInput && (isNaN(valInit) || valInitInput.value === '')) {
                    valInitInput.style.borderColor = '#ffc107';
                    valInitInput.style.borderWidth = '2px';
                }
                if (typeObj === '') {
                    if (cibleError) {
                        cibleError.textContent = '⚠️ Veuillez d\'abord sélectionner un type d\'objectif.';
                        cibleError.style.display = 'block';
                    }
                }
                return false;
            }
            
            let isValid = true;
            let errorMsg = '';
            
            if (typeObj === 'prise_de_poids') {
                if (valCible <= valInit) {
                    isValid = false;
                    errorMsg = '⚠️ Pour une prise de poids, la valeur cible doit être SUPÉRIEURE à la valeur initiale (' + valInit + ').';
                }
            } else if (typeObj === 'perte_de_poids') {
                if (valCible >= valInit) {
                    isValid = false;
                    errorMsg = '⚠️ Pour une perte de poids, la valeur cible doit être INFÉRIEURE à la valeur initiale (' + valInit + ').';
                }
            } else if (typeObj === 'maintien_de_poids') {
                if (Math.abs(valCible - valInit) > 0.5) {
                    isValid = false;
                    errorMsg = '⚠️ Pour un maintien de poids, la valeur cible doit être proche de la valeur initiale (' + valInit + ') à ±0.5 près.';
                }
            } else if (typeObj === '') {
                isValid = false;
                errorMsg = '⚠️ Veuillez sélectionner un type d\'objectif.';
            }
            
            if (!isValid) {
                if (cibleError) {
                    cibleError.textContent = errorMsg;
                    cibleError.style.display = 'block';
                }
                if (valCibleInput) {
                    valCibleInput.style.borderColor = '#dc3545';
                    valCibleInput.style.borderWidth = '2px';
                    valCibleInput.setCustomValidity(errorMsg);
                }
                if (valInitInput) {
                    valInitInput.style.borderColor = '#dc3545';
                    valInitInput.style.borderWidth = '2px';
                }
            } else {
                if (cibleError) cibleError.style.display = 'none';
                if (valCibleInput) {
                    valCibleInput.style.borderColor = '#28a745';
                    valCibleInput.style.borderWidth = '2px';
                    valCibleInput.setCustomValidity('');
                }
                if (valInitInput) {
                    valInitInput.style.borderColor = '#28a745';
                    valInitInput.style.borderWidth = '2px';
                }
            }
            
            return isValid;
        }
        
        // ============ FONCTION DE VALIDATION DES CHAMPS POSITIFS ============
        function validatePositiveFields() {
            let allValid = true;
            
            positiveFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && field.value !== '') {
                    const value = parseFloat(field.value);
                    if (isNaN(value) || value <= 0) {
                        field.style.borderColor = '#dc3545';
                        field.style.borderWidth = '2px';
                        allValid = false;
                    } else {
                        field.style.borderColor = '#28a745';
                        field.style.borderWidth = '2px';
                    }
                } else if (field && field.value === '') {
                    field.style.borderColor = '#ffc107';
                    field.style.borderWidth = '2px';
                    allValid = false;
                }
            });
            
            return allValid;
        }
        
        
        // ============ VALIDATION DES DATES ============
        function validateDates() {
            if (!dateDebInput || !dateFinInput || !dateErrorSpan) return true;
            
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const dateDeb = new Date(dateDebInput.value);
            const dateFin = new Date(dateFinInput.value);
            
            dateDebInput.style.borderColor = '';
            dateFinInput.style.borderColor = '';
            dateErrorSpan.style.display = 'none';
            
            if (!dateDebInput.value || !dateFinInput.value) {
                if (!dateDebInput.value) dateDebInput.style.borderColor = '#ffc107';
                if (!dateFinInput.value) dateFinInput.style.borderColor = '#ffc107';
                return false;
            }
            
            let isValid = true;
            let errorMsg = '';
            
            // Vérifier que date début n'est pas antérieure à aujourd'hui
            if (dateDeb < today) {
                isValid = false;
                errorMsg = '❌ La date de début ne peut pas être antérieure à aujourd\'hui.';
                dateDebInput.style.borderColor = '#dc3545';
                dateFinInput.style.borderColor = '#dc3545';
            }
            // Vérifier que date début <= date fin
            else if (dateDeb > dateFin) {
                isValid = false;
                errorMsg = '❌ La date de début ne peut pas être postérieure à la date de fin.';
                dateDebInput.style.borderColor = '#dc3545';
                dateFinInput.style.borderColor = '#dc3545';
            } 
            // Vérifier la durée minimale d'un mois (30 jours)
            else {
                const diffTime = dateFin - dateDeb;
                const diffDays = diffTime / (1000 * 60 * 60 * 24);
                
                if (diffDays < 30) {
                    isValid = false;
                    errorMsg = '❌ La durée minimale d\'un objectif est d\'un mois (30 jours).';
                    dateDebInput.style.borderColor = '#dc3545';
                    dateFinInput.style.borderColor = '#dc3545';
                } else {
                    dateDebInput.style.borderColor = '#28a745';
                    dateFinInput.style.borderColor = '#28a745';
                }
            }
            
            if (!isValid) {
                dateErrorSpan.textContent = errorMsg;
                dateErrorSpan.style.display = 'block';
                dateFinInput.setCustomValidity(errorMsg);
            } else {
                dateErrorSpan.style.display = 'none';
                dateFinInput.setCustomValidity('');
            }
            
            return isValid;
        }
        
        // ============ VALIDATION GLOBALE ============
        function validateAll() {
            const isCibleValid = validateValeurCible();
            const isDatesValid = validateDates();
            const isPositiveValid = validatePositiveFields();
            
            return isCibleValid && isDatesValid && isPositiveValid ;
        }


        // ============ CONSERVATION DES VALEURS APRÈS SOUMISSION ============
        // Cette fonction permet de garder les valeurs saisies si le formulaire est refusé
        function keepFormValues() {
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($error_message)): ?>
                const fields = {
                    'id_obj': '<?php echo addslashes($_POST['id_obj'] ?? ''); ?>',
                    'type_obj': '<?php echo addslashes($_POST['type_obj'] ?? ''); ?>',
                    'val_init_obj': '<?php echo addslashes($_POST['val_init_obj'] ?? ''); ?>',
                    'val_cible_obj': '<?php echo addslashes($_POST['val_cible_obj'] ?? ''); ?>',
                    'date_deb_obj': '<?php echo addslashes($_POST['date_deb_obj'] ?? ''); ?>',
                    'date_fin_obj': '<?php echo addslashes($_POST['date_fin_obj'] ?? ''); ?>',
                    'frequency_rappel_obj': '<?php echo addslashes($_POST['frequency_rappel_obj'] ?? ''); ?>',
                    'obj_cal_obj': '<?php echo addslashes($_POST['obj_cal_obj'] ?? ''); ?>',
                    'obj_fat_obj': '<?php echo addslashes($_POST['obj_fat_obj'] ?? ''); ?>',
                    'obj_prot_obj': '<?php echo addslashes($_POST['obj_prot_obj'] ?? ''); ?>',
                    'obj_carb_obj': '<?php echo addslashes($_POST['obj_carb_obj'] ?? ''); ?>'
                };
        
                for (const [id, value] of Object.entries(fields)) {
                    const element = document.getElementById(id);
                    if (element && value) {
                        element.value = value;
                    }
                }
            <?php endif; ?>
        }

        
        
        // ============ AJOUT DES ÉCOUTEURS D'ÉVÉNEMENTS ============
        if (typeObjSelect) {
            typeObjSelect.addEventListener('change', validateValeurCible);
        }
        if (valCibleInput) {
            valCibleInput.addEventListener('input', validateValeurCible);
            valCibleInput.addEventListener('keyup', validateValeurCible);
            valCibleInput.addEventListener('blur', validateValeurCible);
        }
        if (valInitInput) {
            valInitInput.addEventListener('input', validateValeurCible);
            valInitInput.addEventListener('keyup', validateValeurCible);
            valInitInput.addEventListener('blur', validateValeurCible);
        }
        
        positiveFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', validatePositiveFields);
                field.addEventListener('blur', validatePositiveFields);
            }
        });
        
        if (dateDebInput) {
            dateDebInput.addEventListener('change', validateDates);
            dateDebInput.addEventListener('blur', validateDates);
        }
        if (dateFinInput) {
            dateFinInput.addEventListener('change', validateDates);
            dateFinInput.addEventListener('blur', validateDates);
        }
        
        // ============ AJOUT DES ATTRIBUTS NAME ============
        document.querySelectorAll('.form-group input, .form-group select').forEach(el => {
            if (el.id === 'id_obj' && !el.getAttribute('name')) el.setAttribute('name', 'id_obj');
            if (el.id === 'val_cible_obj' && !el.getAttribute('name')) el.setAttribute('name', 'val_cible_obj');
            if (el.id === 'val_init_obj' && !el.getAttribute('name')) el.setAttribute('name', 'val_init_obj');
            if (el.id === 'date_deb_obj' && !el.getAttribute('name')) el.setAttribute('name', 'date_deb_obj');
            if (el.id === 'date_fin_obj' && !el.getAttribute('name')) el.setAttribute('name', 'date_fin_obj');
            if (el.id === 'obj_cal_obj' && !el.getAttribute('name')) el.setAttribute('name', 'obj_cal_obj');
            if (el.id === 'obj_fat_obj' && !el.getAttribute('name')) el.setAttribute('name', 'obj_fat_obj');
            if (el.id === 'obj_prot_obj' && !el.getAttribute('name')) el.setAttribute('name', 'obj_prot_obj');
            if (el.id === 'obj_carb_obj' && !el.getAttribute('name')) el.setAttribute('name', 'obj_carb_obj');
            if (el.id === 'frequency_rappel_obj' && !el.getAttribute('name')) el.setAttribute('name', 'frequency_rappel_obj');
        });
        
        if (typeObjSelect && !typeObjSelect.getAttribute('name')) typeObjSelect.setAttribute('name', 'type_obj');
        if (sportSlider && !sportSlider.getAttribute('name')) sportSlider.setAttribute('name', 'consistancy_sport_obj');
        if (alimSlider && !alimSlider.getAttribute('name')) alimSlider.setAttribute('name', 'consistency_alim_obj');
        
        // ============ VALIDATION AVANT SOUMISSION ============
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const isValid = validateAll();
                
                if (!isValid) {
                    e.preventDefault();
                    alert('❌ Veuillez corriger les erreurs dans le formulaire (champs en rouge ou orange).');
                } else {
                    alert('✅ Formulaire valide ! Envoi en cours...');
                }
            });
        }
        
        // ============ VALIDATION INITIALE AU CHARGEMENT ============
        document.addEventListener('DOMContentLoaded', function() {
            setMinDates();  
            validateAll();
            keepFormValues();
        });

    </script>



    <script type="text/javascript" src="assets/js/jquery/jquery.min.js "></script>
    <script type="text/javascript" src="assets/js/jquery-ui/jquery-ui.min.js "></script>
    <script type="text/javascript" src="assets/js/popper.js/popper.min.js"></script>
    <script type="text/javascript" src="assets/js/bootstrap/js/bootstrap.min.js "></script>
    <!-- waves js -->
    <script src="assets/pages/waves/js/waves.min.js"></script>
    <!-- jquery slimscroll js -->
    <script type="text/javascript" src="assets/js/jquery-slimscroll/jquery.slimscroll.js"></script>

    <!-- Custom js -->
    <script src="assets/js/pcoded.min.js"></script>
    <script src="assets/js/vertical/vertical-layout.min.js"></script>
    <script src="assets/js/jquery.mCustomScrollbar.concat.min.js"></script>
    <script type="text/javascript" src="assets/js/script.js"></script>
</body>

</html>