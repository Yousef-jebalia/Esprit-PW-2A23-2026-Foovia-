<?php
include(__DIR__ . '/../model/config.php');
include(__DIR__ . '/../model/ObjectifLongTerme.php');

class ObjectifLongTerme_Controller {

    public function add_objectif(ObjectifLongTerme $objectif, $data) {
        $errors = $this->validate_objectif($data);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo "<p style='color:red'>$error</p>";
            }
            return false;
        }
        
        $sql = "INSERT INTO objectiflongterme (id_obj, id_user, type_obj, val_cible_obj, val_init_obj, date_deb_obj, date_fin_obj, status_obj, frequency_rappel_obj, consistancy_sport_obj, consistency_alim_obj, obj_cal_obj, obj_fat_obj, obj_prot_obj, obj_carb_obj) 
                VALUES (:id_obj, :id_user, :type_obj, :val_cible_obj, :val_init_obj, :date_deb_obj, :date_fin_obj, :status_obj, :frequency_rappel_obj, :consistancy_sport_obj, :consistency_alim_obj, :obj_cal_obj, :obj_fat_obj, :obj_prot_obj, :obj_carb_obj)";
        
        $db = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id_obj' => $objectif->getIdObj(),
                'id_user' => $objectif->getIdUser(),
                'type_obj' => $objectif->getTypeObj(),
                'val_cible_obj' => $objectif->getValCibleObj(),
                'val_init_obj' => $objectif->getValInitObj(),
                'date_deb_obj' => $objectif->getDateDebObj(),
                'date_fin_obj' => $objectif->getDateFinObj(),
                'status_obj' => $data['status_obj'] ?? 'en_attente',  // Valeur par défaut
                'frequency_rappel_obj' => $objectif->getFrequencyRappelObj(),
                'consistancy_sport_obj' => $data['consistancy_sport_obj'] ?? 0,  // Valeur par défaut
                'consistency_alim_obj' => $data['consistency_alim_obj'] ?? 0,  // Valeur par défaut
                'obj_cal_obj' => $objectif->getObjCalObj(),
                'obj_fat_obj' => $objectif->getObjFatObj(),
                'obj_prot_obj' => $objectif->getObjProtObj(),
                'obj_carb_obj' => $objectif->getObjCarbObj()
            ]);
            echo "<p style='color:green'>Objectif ajouté avec succès !</p>";
            return true;
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return false;
        }
    }

    public function list_objectifs(): array {
        $sql = "SELECT id_obj, id_user, type_obj, val_cible_obj, val_init_obj, date_deb_obj, date_fin_obj, status_obj, frequency_rappel_obj, consistancy_sport_obj, consistency_alim_obj, obj_cal_obj, obj_fat_obj, obj_prot_obj, obj_carb_obj FROM objectiflongterme ORDER BY date_deb_obj DESC, id_obj DESC";
        $db = config::getConnexion();

        try {
            $query = $db->query($sql);
            return $query->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function get_objectif_by_id(int $id_obj): ?array {
        $sql = "SELECT id_obj, val_cible_obj, date_deb_obj, date_fin_obj, obj_cal_obj, obj_fat_obj, obj_prot_obj, obj_carb_obj FROM objectiflongterme WHERE id_obj = :id_obj";
        $db = config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute(['id_obj' => $id_obj]);
            $result = $query->fetch();
            return $result ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function update_objectif_fields(int $id_obj, array $data): bool {
        $sql = "UPDATE objectiflongterme
                SET val_cible_obj = :val_cible_obj,
                    date_deb_obj = :date_deb_obj,
                    date_fin_obj = :date_fin_obj,
                    obj_cal_obj = :obj_cal_obj,
                    obj_fat_obj = :obj_fat_obj,
                    obj_prot_obj = :obj_prot_obj,
                    obj_carb_obj = :obj_carb_obj
                WHERE id_obj = :id_obj";
        $db = config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id_obj' => $id_obj,
                'val_cible_obj' => $data['val_cible_obj'],
                'date_deb_obj' => $data['date_deb_obj'],
                'date_fin_obj' => $data['date_fin_obj'],
                'obj_cal_obj' => $data['obj_cal_obj'],
                'obj_fat_obj' => $data['obj_fat_obj'],
                'obj_prot_obj' => $data['obj_prot_obj'],
                'obj_carb_obj' => $data['obj_carb_obj']
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete_objectif(int $id_obj): bool {
        $sql = "DELETE FROM objectiflongterme WHERE id_obj = :id_obj";
        $db = config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute(['id_obj' => $id_obj]);
            return $query->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    

    // Add other methods like list_objectif, delete_objectif, etc. as needed

    /*public function validate_objectif($data) {
        $errors = [];
        
        // Validation ID (max 4 chiffres)
        if (!isset($data['id_obj']) || !is_numeric($data['id_obj']) || $data['id_obj'] > 9999) {
            $errors[] = "L'ID de l'objectif doit être un nombre à maximum 4 chiffres.";
        }
        
        // Validation valeur cible selon type
        $type = $data['type_obj'];
        $val_cible = $data['val_cible_obj'];
        $val_init = $data['val_init_obj'];
        
        if ($type == 'prise_de_poids' && $val_cible <= $val_init) {
            $errors[] = "Pour une prise de poids, la valeur cible doit être supérieure à la valeur initiale.";
        }
        if ($type == 'perte_de_poids' && $val_cible >= $val_init) {
            $errors[] = "Pour une perte de poids, la valeur cible doit être inférieure à la valeur initiale.";
        }
        if ($type == 'maintien_de_poids' && abs($val_cible - $val_init) > 0.5) {
            $errors[] = "Pour un maintien de poids, la valeur cible doit être proche de la valeur initiale (±0.5).";
        }
        
        // Validation dates
        $date_deb = new DateTime($data['date_deb_obj']);
        $date_fin = new DateTime($data['date_fin_obj']);
        $diff = $date_deb->diff($date_fin);
        $days = $diff->days;
        
        if ($date_deb > $date_fin) {
            $errors[] = "La date de début ne peut pas être postérieure à la date de fin.";
        }
        if ($days < 30) {
            $errors[] = "La durée minimale d'un objectif est d'un mois (30 jours).";
        }
        
        return $errors;
    }*/

    public function validate_objectif($data) {
        $errors = [];
        
        // Validation ID (max 4 chiffres)
        if (!isset($data['id_obj']) || !is_numeric($data['id_obj']) || $data['id_obj'] > 9999) {
            $errors[] = "L'ID de l'objectif doit être un nombre à maximum 4 chiffres.";
        }
        
        // Validation des valeurs strictement positives
        $positive_fields = [
            'val_cible_obj' => 'Valeur cible',
            'val_init_obj' => 'Valeur initiale',
            'obj_cal_obj' => 'Objectif calorique',
            'obj_fat_obj' => 'Objectif lipides',
            'obj_prot_obj' => 'Objectif protéines',
            'obj_carb_obj' => 'Objectif glucides',
            'frequency_rappel_obj' => 'Fréquence de rappel'
        ];
        
        foreach ($positive_fields as $field => $label) {
            if (isset($data[$field]) && $data[$field] <= 0) {
                $errors[] = "$label doit être strictement positif.";
            }
        }
        
        // Validation des consistances (entre 0 et 100)
        if (isset($data['consistancy_sport_obj']) && ($data['consistancy_sport_obj'] < 0 || $data['consistancy_sport_obj'] > 100)) {
            $errors[] = "La constance sportive doit être comprise entre 0 et 100.";
        }
        if (isset($data['consistency_alim_obj']) && ($data['consistency_alim_obj'] < 0 || $data['consistency_alim_obj'] > 100)) {
            $errors[] = "La constance alimentaire doit être comprise entre 0 et 100.";
        }
        
        // Validation valeur cible selon type
        $type = $data['type_obj'];
        $val_cible = $data['val_cible_obj'];
        $val_init = $data['val_init_obj'];
        
        if ($type == 'prise_de_poids' && $val_cible <= $val_init) {
            $errors[] = "Pour une prise de poids, la valeur cible doit être supérieure à la valeur initiale.";
        }
        if ($type == 'perte_de_poids' && $val_cible >= $val_init) {
            $errors[] = "Pour une perte de poids, la valeur cible doit être inférieure à la valeur initiale.";
        }
        if ($type == 'maintien_de_poids' && abs($val_cible - $val_init) > 0.5) {
            $errors[] = "Pour un maintien de poids, la valeur cible doit être proche de la valeur initiale (±0.5).";
        }
        
        // Validation dates
        $date_deb = new DateTime($data['date_deb_obj']);
        $date_fin = new DateTime($data['date_fin_obj']);
        $diff = $date_deb->diff($date_fin);
        $days = $diff->days;
        
        if ($date_deb > $date_fin) {
            $errors[] = "La date de début ne peut pas être postérieure à la date de fin.";
        }
        if ($days < 30) {
            $errors[] = "La durée minimale d'un objectif est d'un mois (30 jours).";
        }
        
        return $errors;
    }

}
?>