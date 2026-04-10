<?php
include(__DIR__ . '/../Model/config.php');
include(__DIR__ . '/../Model/menu.php');

class Controller_menu {

    public function add_recipe(Recipe $recipe) {
        $sql = "INSERT INTO recipe (id_rec, name_rec, categorie_rec, description_rec, prot_rec, fat_rec, carb_rec, cal_rec, instruction_rec, origin_rec, img_rec) 
                VALUES (:id_recipe, :nom, :categorie, :description, :prot, :fat, :carb, :cal, :instructions, :origin, :imag)";
        $db = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id_recipe' => $recipe->getIdRec(),
                'nom' => $recipe->getNomRec(),
                'categorie' => $recipe->getCategorieRec(),
                'description' => $recipe->getDescriptionRec(),
                'prot' => $recipe->getProtRec(),
                'fat' => $recipe->getFatRec(),
                'carb' => $recipe->getCarbRec(),
                'cal' => $recipe->getCalRec(),
                'instructions' => $recipe->getInstructionsRec(),
                'origin' => $recipe->getOriginRec(),
                'imag' => $recipe->getImagRec()
            ]);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
    

    // Add other methods like list_reclamations, delete_reclamation, etc. as needed
}
?>