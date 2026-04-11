<?php
require_once '../../controller/ObjectifLongTerme_Controller.php';

$controller = new ObjectifLongTerme_Controller();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id_obj'])) {
  $controller->delete_objectif((int) $_POST['delete_id_obj']);
  header('Location: objectif-long-terme.php');
  exit;
}

$objectifs = $controller->list_objectifs();
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <title>Objectifs long terme</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <meta name="description" content="">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
  </head>
  <body>
    <section class="py-5 bg-light">
      <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
          <div>
            <p class="text-uppercase text-muted mb-1">Consultation</p>
            <h1 class="display-6 mb-0">Objectifs long terme</h1>
          </div>
          <a href="index.html" class="btn btn-outline-dark">Retour</a>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="table-dark">
                  <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Valeur cible</th>
                    <th>Valeur initiale</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Statut</th>
                    <th>Rappel</th>
                    <th>Sport</th>
                    <th>Alim.</th>
                    <th>Calories</th>
                    <th>Lipides</th>
                    <th>Protéines</th>
                    <th>Glucides</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($objectifs)): ?>
                    <?php foreach ($objectifs as $objectif): ?>
                      <tr>
                        <td><?php echo htmlspecialchars((string) $objectif['id_obj']); ?></td>
                        <td><?php echo htmlspecialchars($objectif['type_obj']); ?></td>
                        <td><?php echo htmlspecialchars((string) $objectif['val_cible_obj']); ?></td>
                        <td><?php echo htmlspecialchars((string) $objectif['val_init_obj']); ?></td>
                        <td><?php echo htmlspecialchars($objectif['date_deb_obj']); ?></td>
                        <td><?php echo htmlspecialchars($objectif['date_fin_obj']); ?></td>
                        <td><?php echo htmlspecialchars($objectif['status_obj']); ?></td>
                        <td><?php echo htmlspecialchars((string) $objectif['frequency_rappel_obj']); ?></td>
                        <td><?php echo htmlspecialchars((string) $objectif['consistancy_sport_obj']); ?></td>
                        <td><?php echo htmlspecialchars((string) $objectif['consistency_alim_obj']); ?></td>
                        <td><?php echo htmlspecialchars((string) $objectif['obj_cal_obj']); ?></td>
                        <td><?php echo htmlspecialchars((string) $objectif['obj_fat_obj']); ?></td>
                        <td><?php echo htmlspecialchars((string) $objectif['obj_prot_obj']); ?></td>
                        <td><?php echo htmlspecialchars((string) $objectif['obj_carb_obj']); ?></td>
                        <td>
                          <a href="../back_office/edit-objectif-long-terme.php?id_obj=<?php echo urlencode((string) $objectif['id_obj']); ?>" class="btn btn-sm btn-primary me-1">Modifier</a>
                          <form method="post" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet objectif ?');" class="d-inline">
                            <input type="hidden" name="delete_id_obj" value="<?php echo htmlspecialchars((string) $objectif['id_obj']); ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="15" class="text-center py-4 text-muted">Aucun objectif long terme trouvé.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
  </body>
</html>