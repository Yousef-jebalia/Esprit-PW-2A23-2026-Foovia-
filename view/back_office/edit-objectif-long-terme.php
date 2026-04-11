<?php
require_once '../../controller/ObjectifLongTerme_Controller.php';

$controller = new ObjectifLongTerme_Controller();
$error_message = '';

$id_obj = 0;
if (isset($_GET['id_obj'])) {
    $id_obj = (int) $_GET['id_obj'];
} elseif (isset($_POST['id_obj'])) {
    $id_obj = (int) $_POST['id_obj'];
}

if ($id_obj <= 0) {
    header('Location: ../front_office/objectif-long-terme.php');
    exit;
}

$objectif = $controller->get_objectif_by_id($id_obj);
if (!$objectif) {
    header('Location: ../front_office/objectif-long-terme.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'val_cible_obj' => (float) ($_POST['val_cible_obj'] ?? 0),
        'date_deb_obj' => $_POST['date_deb_obj'] ?? '',
        'date_fin_obj' => $_POST['date_fin_obj'] ?? '',
        'obj_cal_obj' => (float) ($_POST['obj_cal_obj'] ?? 0),
        'obj_fat_obj' => (float) ($_POST['obj_fat_obj'] ?? 0),
        'obj_prot_obj' => (float) ($_POST['obj_prot_obj'] ?? 0),
        'obj_carb_obj' => (float) ($_POST['obj_carb_obj'] ?? 0)
    ];

    if ($data['val_cible_obj'] <= 0 || $data['obj_cal_obj'] <= 0 || $data['obj_fat_obj'] <= 0 || $data['obj_prot_obj'] <= 0 || $data['obj_carb_obj'] <= 0) {
        $error_message = 'Toutes les valeurs numeriques doivent etre strictement positives.';
    } elseif (empty($data['date_deb_obj']) || empty($data['date_fin_obj'])) {
        $error_message = 'Les dates de debut et de fin sont obligatoires.';
    } elseif ($data['date_deb_obj'] > $data['date_fin_obj']) {
        $error_message = 'La date de debut ne peut pas etre posterieure a la date de fin.';
    } else {
        $updated = $controller->update_objectif_fields($id_obj, $data);
        if ($updated) {
            header('Location: ../front_office/objectif-long-terme.php');
            exit;
        }
        $error_message = 'La modification a echoue.';
    }

    $objectif = array_merge($objectif, $data);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier objectif long terme</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Modifier l'objectif #<?php echo htmlspecialchars((string) $id_obj); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                        <?php endif; ?>

                        <form method="post" action="">
                            <input type="hidden" name="id_obj" value="<?php echo htmlspecialchars((string) $id_obj); ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="val_cible_obj">Valeur cible</label>
                                    <input type="number" class="form-control" id="val_cible_obj" name="val_cible_obj" step="0.01" min="0.01" required value="<?php echo htmlspecialchars((string) $objectif['val_cible_obj']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="obj_cal_obj">Calories</label>
                                    <input type="number" class="form-control" id="obj_cal_obj" name="obj_cal_obj" step="0.01" min="0.01" required value="<?php echo htmlspecialchars((string) $objectif['obj_cal_obj']); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="date_deb_obj">Date debut</label>
                                    <input type="date" class="form-control" id="date_deb_obj" name="date_deb_obj" required value="<?php echo htmlspecialchars($objectif['date_deb_obj']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="date_fin_obj">Date fin</label>
                                    <input type="date" class="form-control" id="date_fin_obj" name="date_fin_obj" required value="<?php echo htmlspecialchars($objectif['date_fin_obj']); ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="obj_fat_obj">Lipides</label>
                                    <input type="number" class="form-control" id="obj_fat_obj" name="obj_fat_obj" step="0.01" min="0.01" required value="<?php echo htmlspecialchars((string) $objectif['obj_fat_obj']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="obj_prot_obj">Proteines</label>
                                    <input type="number" class="form-control" id="obj_prot_obj" name="obj_prot_obj" step="0.01" min="0.01" required value="<?php echo htmlspecialchars((string) $objectif['obj_prot_obj']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="obj_carb_obj">Glucides</label>
                                    <input type="number" class="form-control" id="obj_carb_obj" name="obj_carb_obj" step="0.01" min="0.01" required value="<?php echo htmlspecialchars((string) $objectif['obj_carb_obj']); ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="../front_office/objectif-long-terme.php" class="btn btn-outline-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
