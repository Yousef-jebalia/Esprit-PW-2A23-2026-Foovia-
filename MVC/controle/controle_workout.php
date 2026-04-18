<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once __DIR__ . '/../model/workout.php';
include_once __DIR__ . '/../model/config.php';

class controle_workout
{
    function add_workout($workout) {
        $sql = "INSERT INTO workout (name_work, pic_work, nb_work, cal_work, duree_work, id_user)
                VALUES (:name_work, :pic_work, :nb_work, :cal_work, :duree_work, :id_user)";
        $db = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->bindValue('name_work',  $workout->getNameWork());
            $query->bindValue('pic_work',   $workout->getPicWork(), PDO::PARAM_LOB);
            $query->bindValue('nb_work',    $workout->getNbWork());
            $query->bindValue('cal_work',   $workout->getCalWork());
            $query->bindValue('duree_work', $workout->getDureeWork());
            $query->bindValue('id_user',    $workout->getIdUser());
            $query->execute();
            return (int)$db->lastInsertId();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    function update_workout($workout, $id)
    {
        try {
            $db = config::getConnexion();
            $query = $db->prepare(
                'UPDATE workout SET
                    name_work  = :name_work,
                    pic_work   = :pic_work,
                    nb_work    = :nb_work,
                    cal_work   = :cal_work,
                    duree_work = :duree_work,
                    id_user    = :id_user
                WHERE id_work = :id'
            );
            $query->execute([
                'id'         => $id,
                'name_work'  => $workout->getNameWork(),
                'pic_work'   => $workout->getPicWork(),
                'nb_work'    => $workout->getNbWork(),
                'cal_work'   => $workout->getCalWork(),
                'duree_work' => $workout->getDureeWork(),
                'id_user'    => $workout->getIdUser(),
            ]);
            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    function delete_workout($id)
    {
        $sql = "DELETE FROM workout WHERE id_work = :id";
        $db = config::getConnexion();
        try {
            $req = $db->prepare($sql);
            $req->bindValue(':id', $id, PDO::PARAM_INT);
            $req->execute();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    function replace_belong_for_workout(int $workoutId, array $selectedExercises)
    {
        $db = config::getConnexion();
        try {
            $db->beginTransaction();

            $delete = $db->prepare("DELETE FROM belong WHERE id_work = :id_work");
            $delete->execute(['id_work' => $workoutId]);

            if (!empty($selectedExercises)) {
                $insert = $db->prepare(
                    "INSERT INTO belong (id_ex, id_work, sets, weight, `time`)
                     VALUES (:id_ex, :id_work, :sets, :weight, :time)
                     ON DUPLICATE KEY UPDATE
                        id_work = VALUES(id_work),
                        sets = VALUES(sets),
                        weight = VALUES(weight),
                        `time` = VALUES(`time`)"
                );

                foreach ($selectedExercises as $item) {
                    $idEx = (int)($item['id_ex'] ?? 0);
                    $sets = (int)($item['sets'] ?? 0);
                    $weight = (float)($item['weight'] ?? 0);
                    $time = (int)($item['time'] ?? 0);

                    if ($idEx <= 0) {
                        continue;
                    }

                    $insert->execute([
                        'id_ex' => $idEx,
                        'id_work' => $workoutId,
                        'sets' => max(1, $sets),
                        'weight' => max(0, $weight),
                        'time' => max(0, $time),
                    ]);
                }
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $e->getMessage();
        }
    }
}

// ── Handling POST Requests ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? 'add';
    $controller = new controle_workout();

    if ($action === 'delete') {
        $controller->delete_workout((int)$_POST['delete_id']);
        header('Location: ../view/back_office/form-elements-component.php');
        exit;
    }

    // Capture Inputs
    $name  = $_POST['work_name'] ?? '';
    $nb    = (int)($_POST['work_nb'] ?? 0);
    $cal   = (int)($_POST['work_cal'] ?? 0);
    $duree = (int)($_POST['work_duree'] ?? 0);
    $user  = (int)($_POST['id_user'] ?? 0);
    
    // Handle File Upload
    $pic = null;
    if (isset($_FILES['work_picture']) && $_FILES['work_picture']['error'] === UPLOAD_ERR_OK) {
        $pic = file_get_contents($_FILES['work_picture']['tmp_name']);
    }

    $selectedExercises = [];
    if (!empty($_POST['selected_exercises'])) {
        $decoded = json_decode($_POST['selected_exercises'], true);
        if (is_array($decoded)) {
            $selectedExercises = $decoded;
        }
    }

    $workout = new Workout($name, $pic, $nb, $cal, $duree, $user);

    if ($action === 'update') {
        $workoutId = (int)$_POST['edit_id'];
        $result = $controller->update_workout($workout, $workoutId);
        if ($result === true && isset($_POST['selected_exercises']) && trim((string)$_POST['selected_exercises']) !== '') {
            $result = $controller->replace_belong_for_workout($workoutId, $selectedExercises);
        }
    } else {
        $newWorkoutId = $controller->add_workout($workout);
        if (is_int($newWorkoutId) && $newWorkoutId > 0) {
            $result = $controller->replace_belong_for_workout($newWorkoutId, $selectedExercises);
        } else {
            $result = $newWorkoutId;
        }
    }

    if ($result === true) {
        header('Location: ../view/back_office/form-elements-component.php');
    } else {
        echo "<script>alert('Error: " . addslashes($result) . "');</script>";
    }
    exit;
}
?>