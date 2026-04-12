<?php
include '../../controle/controle_Menu.php';

$controller = new Controller_menu();
$error = "";
$recipeData = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        isset($_POST["id_rec"], $_POST["nom_rec"], $_POST["categorie_rec"], $_POST["description_rec"], $_POST["prot_rec"],
              $_POST["fat_rec"], $_POST["carb_rec"], $_POST["cal_rec"], $_POST["instructions_rec"], $_POST["origin_rec"])
    ) {
        $id = (int)$_POST["id_rec"];
        $imagePath = isset($_POST["current_img_rec"]) ? $_POST["current_img_rec"] : "";

        if (isset($_FILES['imag_rec']) && $_FILES['imag_rec']['error'] === 0) {
            $uploadDir = 'assets/images/recipes/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = basename($_FILES['imag_rec']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExt, $allowedExts)) {
                $newFileName = uniqid('recipe_') . '.' . $fileExt;
                $uploadPath = $uploadDir . $newFileName;
                if (move_uploaded_file($_FILES['imag_rec']['tmp_name'], $uploadPath)) {
                    $imagePath = $uploadPath;
                } else {
                    $error = "Failed to upload image file.";
                }
            } else {
                $error = "Invalid image file format. Only JPG, PNG, GIF, WebP allowed.";
            }
        }

        if (empty($error)) {
            $recipe = new Recipe(
                $id,
                trim($_POST["nom_rec"]),
                trim($_POST["categorie_rec"]),
                trim($_POST["description_rec"]),
                (float)$_POST["prot_rec"],
                (float)$_POST["fat_rec"],
                (float)$_POST["carb_rec"],
                (float)$_POST["cal_rec"],
                trim($_POST["instructions_rec"]),
                trim($_POST["origin_rec"]),
                $imagePath
            );

            $controller->update_recipe($recipe);
            header('Location: form-elements-component.php');
            exit;
        }

        $recipeData = [
            'id_rec' => $id,
            'name_rec' => $_POST["nom_rec"],
            'categorie_rec' => $_POST["categorie_rec"],
            'description_rec' => $_POST["description_rec"],
            'prot_rec' => $_POST["prot_rec"],
            'fat_rec' => $_POST["fat_rec"],
            'carb_rec' => $_POST["carb_rec"],
            'cal_rec' => $_POST["cal_rec"],
            'instruction_rec' => $_POST["instructions_rec"],
            'origin_rec' => $_POST["origin_rec"],
            'img_rec' => $imagePath
        ];
    } else {
        $error = "Missing form data.";
    }
}

if ($recipeData === null) {
    if (!isset($_GET["id_rec"]) || !is_numeric($_GET["id_rec"])) {
        $error = "Invalid recipe ID.";
    } else {
        $recipeData = $controller->get_recipe_by_id((int)$_GET["id_rec"]);
        if (!$recipeData) {
            $error = "Recipe not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Recipe</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width:900px;padding-top:30px;padding-bottom:30px;">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="margin:0;">Edit Recipe</h5>
                <a href="form-elements-component.php" class="btn btn-secondary btn-sm">Back to list</a>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (!empty($recipeData)): ?>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="id_rec" value="<?php echo (int)$recipeData['id_rec']; ?>">
                        <input type="hidden" name="current_img_rec" value="<?php echo htmlspecialchars($recipeData['img_rec']); ?>">

                        <div class="form-group">
                            <label>Recipe Name</label>
                            <input type="text" name="nom_rec" class="form-control" value="<?php echo htmlspecialchars($recipeData['name_rec']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="categorie_rec" class="form-control" value="<?php echo htmlspecialchars($recipeData['categorie_rec']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description_rec" class="form-control" rows="3"><?php echo htmlspecialchars($recipeData['description_rec']); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Protein</label>
                                <input type="text" name="prot_rec" class="form-control" value="<?php echo htmlspecialchars($recipeData['prot_rec']); ?>">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Fat</label>
                                <input type="text" name="fat_rec" class="form-control" value="<?php echo htmlspecialchars($recipeData['fat_rec']); ?>">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Carbs</label>
                                <input type="text" name="carb_rec" class="form-control" value="<?php echo htmlspecialchars($recipeData['carb_rec']); ?>">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Calories</label>
                                <input type="text" name="cal_rec" class="form-control" value="<?php echo htmlspecialchars($recipeData['cal_rec']); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Instructions</label>
                            <textarea name="instructions_rec" class="form-control" rows="4"><?php echo htmlspecialchars($recipeData['instruction_rec']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Origin</label>
                            <input type="text" name="origin_rec" class="form-control" value="<?php echo htmlspecialchars($recipeData['origin_rec']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Current Image</label><br>
                            <?php if (!empty($recipeData['img_rec'])): ?>
                                <img src="<?php echo htmlspecialchars($recipeData['img_rec']); ?>" alt="Current recipe image" style="width:100px;height:100px;object-fit:cover;border-radius:6px;">
                            <?php else: ?>
                                <span>No image</span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Upload New Image (optional)</label>
                            <input type="file" name="imag_rec" class="form-control" accept="image/*">
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        (function() {
            const form = document.querySelector('form[enctype="multipart/form-data"]');
            if (!form) return;

            const idInput = form.querySelector('input[name="id_rec"]');
            const nameInput = form.querySelector('input[name="nom_rec"]');
            const protInput = form.querySelector('input[name="prot_rec"]');
            const fatInput = form.querySelector('input[name="fat_rec"]');
            const carbInput = form.querySelector('input[name="carb_rec"]');
            const calInput = form.querySelector('input[name="cal_rec"]');
            const originInput = form.querySelector('input[name="origin_rec"]');
            const descInput = form.querySelector('textarea[name="description_rec"]');
            const instInput = form.querySelector('textarea[name="instructions_rec"]');

            const floatFields = [protInput, fatInput, carbInput, calInput];

            const restrictDigits = function(input, maxLength) {
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '').slice(0, maxLength);
                });
            };

            const restrictText = function(input, maxLength) {
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/[^A-Za-z\s]/g, '').slice(0, maxLength);
                });
            };

            const restrictMaxLength = function(input, maxLength) {
                input.addEventListener('input', function() {
                    if (this.value.length > maxLength) {
                        this.value = this.value.slice(0, maxLength);
                    }
                });
            };

            const restrictFloatField = function(input) {
                input.addEventListener('input', function() {
                    let value = this.value.replace(',', '.').replace(/[^0-9.]/g, '');

                    const firstDotIndex = value.indexOf('.');
                    if (firstDotIndex !== -1) {
                        value = value.slice(0, firstDotIndex + 1) + value.slice(firstDotIndex + 1).replace(/\./g, '');
                    }

                    const parts = value.split('.');
                    if (parts.length > 1) {
                        parts[1] = parts[1].slice(0, 2);
                        value = parts[0] + '.' + parts[1];
                    }

                    if (value !== '' && Number(value) > 2000) {
                        value = '2000';
                    }

                    this.value = value;
                });
            };

            const isValidStep001 = function(value) {
                const scaled = Number(value) * 100;
                return Math.abs(scaled - Math.round(scaled)) < 1e-9;
            };

            const isTextValue = function(value) {
                return /[A-Za-z]/.test(value);
            };

            restrictDigits(idInput, 4);
            restrictText(nameInput, 20);
            restrictText(originInput, 20);
            restrictMaxLength(descInput, 500);
            restrictMaxLength(instInput, 500);
            floatFields.forEach(restrictFloatField);

            form.addEventListener('submit', function(e) {
                const errors = [];

                const idRaw = idInput.value.trim();
                if (!idRaw) {
                    errors.push('ID is required.');
                } else if (!/^\d{1,4}$/.test(idRaw)) {
                    errors.push('ID must be a number between 0001 and 9999.');
                } else {
                    const idNumber = Number(idRaw);
                    if (idNumber < 1 || idNumber > 9999) {
                        errors.push('ID must be a number between 0001 and 9999.');
                    }
                }

                const nameRaw = nameInput.value.trim();
                if (nameRaw.length > 20) {
                    errors.push('Name max length is 20.');
                }
                if (nameRaw && !isTextValue(nameRaw)) {
                    errors.push('Name must be a string value.');
                }

                floatFields.forEach(function(field) {
                    const label = field.name === 'prot_rec' ? 'Protein' :
                        field.name === 'fat_rec' ? 'Fat' :
                        field.name === 'carb_rec' ? 'Carbs' : 'Calories';
                    const raw = field.value.trim();
                    const val = Number(raw);

                    if (raw === '' || !Number.isFinite(val)) {
                        errors.push(label + ' must be a float value.');
                        return;
                    }
                    if (!isValidStep001(raw)) {
                        errors.push(label + ' must use a 0.01 step.');
                    }
                    if (val > 2000) {
                        errors.push(label + ' max value is 2000.');
                    }
                });

                const originRaw = originInput.value.trim();
                if (originRaw.length > 20) {
                    errors.push('Origin max length is 20.');
                }
                if (originRaw && !isTextValue(originRaw)) {
                    errors.push('Origin must be a string value.');
                }

                if (descInput.value.trim().length > 500) {
                    errors.push('Description max length is 500.');
                }
                if (instInput.value.trim().length > 500) {
                    errors.push('Instructions max length is 500.');
                }

                if (errors.length > 0) {
                    e.preventDefault();
                    alert(errors.join('\n'));
                }
            });
        })();
    </script>
</body>
</html>
