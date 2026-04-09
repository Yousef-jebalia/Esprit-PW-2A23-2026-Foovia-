<?php
include(__DIR__ . '/../Model/config.php');
include(__DIR__ . '/../Model/reclamation.php');

class Controller_reclamation {

    public function add_reclamation(Reclamations $reclamation) {
        $sql = "INSERT INTO reclamation (id_reclam, id_user, description_reclam, etat_reclam, type_reclam, dateouvert_reclam, dateferm_reclam) 
                VALUES (:id_reclamation, :id_user, :description, :etat, :type, :date_overture, :date_fermiture)";
        $db = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id_reclamation' => $reclamation->getIdReclamation(),
                'id_user' => $reclamation->getIdUser(),
                'description' => $reclamation->getDescription(),
                'etat' => $reclamation->getEtat(),
                'type' => $reclamation->getType(),
                'date_overture' => $reclamation->getDateOverture(),
                'date_fermiture' => $reclamation->getDateFermiture()
            ]);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
    

    // Add other methods like list_reclamations, delete_reclamation, etc. as needed
}
?>