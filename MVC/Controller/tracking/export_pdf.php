<?php
// Simple export endpoint: outputs user's weekly tracking as CSV download.
// Placed under Controller/tracking/ to match new form action.
session_start();
require_once __DIR__ . '/ObjectifHebdomadaire_Controller.php';

header('Content-Type: text/csv; charset=utf-8');

$userId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (int) ($_SESSION['user_id'] ?? 0);
}

if (empty($userId)) {
  http_response_code(400);
  echo "Error: missing user_id\n";
  exit;
}

$hebdo = new ObjectifHebdomadaire_Controller();
$rows = $hebdo->list_objectifs_by_user((int) $userId);

$filename = 'tracking_export_' . date('Ymd') . '.csv';
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
if ($out === false) {
  http_response_code(500);
  echo "Unable to open output stream\n";
  exit;
}

// CSV header
fputcsv($out, ['date', 'weight', 'calories', 'protein_g', 'carbs_g', 'fat_g', 'water_glasses', 'sleep_h', 'steps', 'notes', 'status']);

foreach ($rows as $r) {
  fputcsv($out, [
    $r['date_suiv'] ?? '',
    $r['poids_suiv'] ?? '',
    $r['val_cal_suiv'] ?? '',
    $r['val_prot_suiv'] ?? '',
    $r['val_carb_suiv'] ?? '',
    $r['val_fat_suiv'] ?? '',
    $r['nb_verre_eau_suiv'] ?? '',
    $r['nb_h_sommeil_suiv'] ?? '',
    $r['nb_pas_suiv'] ?? '',
    $r['note_suiv'] ?? '',
    $r['status_obj_quot_suiv'] ?? '',
  ]);
}

fclose($out);
exit;
