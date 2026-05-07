<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../Model/MARKETPLACE_MODULE/url_helper.php';

$marketplaceUrl = foovia_url('MVC/View/front_office/MARKETPLACE_MODULE/organic-1.0.0/marketplace.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: foovia-signin.php?redirect=marketplace');
    exit;
}

header('Location: ' . $marketplaceUrl);
exit;
