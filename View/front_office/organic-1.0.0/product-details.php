<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Model/Marchandise.php';

$marchandiseModel = new Marchandise();
$productId = (int) ($_GET['id'] ?? 0);
$product = $productId > 0 ? $marchandiseModel->findById($productId) : null;
$availability = $productId > 0 ? $marchandiseModel->fetchAvailabilityById($productId) : [];

if ($product === null) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product === null ? 'Product Not Found' : htmlspecialchars((string) $product['name_march'], ENT_QUOTES) ?> | Foovia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" type="text/css" href="../../assets/css/marketplace.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="foovia-detail-body">
    <header class="foovia-topbar">
        <div class="container-lg">
            <div class="row align-items-center g-3">
                <div class="col-lg-3 col-md-4 foovia-brand-col">
                    <a href="marketplace.php" class="foovia-brand">
                        <img src="../../assets/imges-autre/pic_logo.png" class="foovia-logo-img" style="height: 42px; width: auto; max-height: 42px;" alt="Foovia logo">
                        <img src="../../assets/imges-autre/pic_name.png" class="foovia-name-img" style="height: 22px; width: auto; max-height: 22px;" alt="Foovia">
                    </a>
                </div>
                <div class="col-lg-6">
                    <nav class="foovia-nav justify-content-lg-center">
                        <a href="marketplace.php">HOME</a>
                        <a href="marketplace.php#products">MARKETPLACE</a>
                    </nav>
                </div>
                <div class="col-lg-3 text-lg-end">
                    <a href="../../back_office/material_able-main/products.php" class="btn btn-outline-success rounded-pill px-4">Back Office</a>
                </div>
            </div>
        </div>
    </header>

    <main class="foovia-detail-page">
        <div class="container-lg">
            <?php if ($product === null): ?>
                <div class="market-empty">
                    <h1 class="h3 mb-3">Product not found</h1>
                    <p class="text-muted">This product does not exist anymore.</p>
                    <a href="marketplace.php" class="btn btn-success rounded-pill px-4">Back to Marketplace</a>
                </div>
            <?php else: ?>
                <div class="foovia-detail-layout">
                    <section class="foovia-detail-image-card">
                        <img src="../../../Controller/Marchandise_Controller.php?action=image&id=<?= (int) $product['id_march'] ?>" alt="<?= htmlspecialchars((string) $product['name_march'], ENT_QUOTES) ?>">
                    </section>

                    <section class="foovia-detail-info">
                        <a href="marketplace.php#products" class="foovia-detail-back">Back to marketplace</a>
                        <h1><?= htmlspecialchars((string) $product['name_march'], ENT_QUOTES) ?></h1>
                        <span class="foovia-arrival">Available in selected Foovia magasins</span>
                        <p class="foovia-reference">Reference#: <?= htmlspecialchars((string) $product['id_march'], ENT_QUOTES) ?></p>
                        <p class="foovia-detail-description"><?= htmlspecialchars((string) $product['description_march'], ENT_QUOTES) ?></p>
                        <div class="foovia-detail-specs">
                            <span>Stock: <strong><?= (int) $product['quantity_march'] ?></strong></span>
                            <span>Access point: <strong><?= htmlspecialchars((string) $product['point_acces_march'], ENT_QUOTES) ?></strong></span>
                            <span>Expires: <strong><?= htmlspecialchars((string) $product['date_expiration_march'], ENT_QUOTES) ?></strong></span>
                        </div>

                        <div class="foovia-availability-card">
                            <h2>Disponibilite</h2>
                            <div class="foovia-availability-list">
                                <?php foreach ($availability as $store): ?>
                                    <div class="foovia-availability-row">
                                        <strong>
                                            <?php if ((int) ($store['has_image'] ?? 0) === 1): ?>
                                                <img src="../../../Controller/Magasin_Controller.php?action=image&id=<?= (int) $store['id_mag'] ?>" alt="<?= htmlspecialchars($store['name_mag'], ENT_QUOTES) ?>">
                                            <?php endif; ?>
                                            <?= htmlspecialchars($store['name_mag'], ENT_QUOTES) ?>:
                                        </strong>
                                        <?php if ((int) $store['is_available'] === 1): ?>
                                            <span class="foovia-status-available">Disponible</span>
                                        <?php else: ?>
                                            <span class="foovia-status-empty">Epuisé</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>

                    <aside class="foovia-purchase-card">
                        <strong><?= (int) $product['price_march'] ?> TND</strong>
                        <div class="foovia-quantity-row">
                            <span>Quantité</span>
                            <input type="text" value="1" readonly>
                        </div>
                        <button type="button" class="foovia-cart-btn">Ajouter au panier</button>
                        <button type="button" class="foovia-quote-btn">Demander un devis</button>
                        <div class="foovia-action-row">
                            <span>♥ Souhaits</span>
                            <span>Comparer</span>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
