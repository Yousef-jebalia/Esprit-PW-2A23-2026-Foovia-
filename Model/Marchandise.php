<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Magasin.php';

final class Marchandise
{
    private PDO $db;
    private Magasin $magasin;

    public function __construct(?PDO $db = null, ?Magasin $magasin = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->magasin = $magasin ?? new Magasin($this->db);
    }

    public function fetchAllWithStores(): array
    {
        $statement = $this->db->query(
            'SELECT
                m.id_march,
                m.name_march,
                m.description_march,
                m.price_march,
                m.quantity_march,
                m.date_expiration_march,
                m.point_acces_march,
                GROUP_CONCAT(DISTINCT mag.id_mag ORDER BY mag.name_mag SEPARATOR ",") AS store_ids,
                GROUP_CONCAT(DISTINCT mag.name_mag ORDER BY mag.name_mag SEPARATOR ", ") AS store_names,
                GROUP_CONCAT(DISTINCT mag.email_mag ORDER BY mag.name_mag SEPARATOR ", ") AS store_emails,
                GROUP_CONCAT(DISTINCT mag.phone_mag ORDER BY mag.name_mag SEPARATOR ", ") AS store_phones,
                GROUP_CONCAT(DISTINCT mag.adress_mag ORDER BY mag.name_mag SEPARATOR " | ") AS store_addresses
             FROM marchandise m
             LEFT JOIN vendre v ON v.id_march = m.id_march
             LEFT JOIN magasin mag ON mag.id_mag = v.id_mag
             GROUP BY
                m.id_march,
                m.name_march,
                m.description_march,
                m.price_march,
                m.quantity_march,
                m.date_expiration_march,
                m.point_acces_march
             ORDER BY m.id_march DESC'
        );

        return $statement->fetchAll();
    }

    public function findById(int $productId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                m.id_march,
                m.name_march,
                m.description_march,
                m.price_march,
                m.quantity_march,
                m.date_expiration_march,
                m.point_acces_march,
                GROUP_CONCAT(DISTINCT mag.id_mag ORDER BY mag.name_mag SEPARATOR ",") AS store_ids
             FROM marchandise m
             LEFT JOIN vendre v ON v.id_march = m.id_march
             LEFT JOIN magasin mag ON mag.id_mag = v.id_mag
             WHERE m.id_march = :id_march
             GROUP BY
                m.id_march,
                m.name_march,
                m.description_march,
                m.price_march,
                m.quantity_march,
                m.date_expiration_march,
                m.point_acces_march'
        );
        $statement->execute(['id_march' => $productId]);
        $result = $statement->fetch();

        return $result ?: null;
    }

    public function fetchSummary(): array
    {
        return [
            'products' => $this->countAll(),
            'stores' => $this->magasin->countAll(),
            'quantity' => (int) $this->db->query('SELECT COALESCE(SUM(quantity_march), 0) FROM marchandise')->fetchColumn(),
        ];
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM marchandise')->fetchColumn();
    }

    public function create(array $payload, array $image): int
    {
        $imageBinary = file_get_contents($image['tmp_name']);

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                'INSERT INTO marchandise
                    (name_march, description_march, price_march, quantity_march, date_expiration_march, point_acces_march, img_march)
                 VALUES (:name_march, :description_march, :price_march, :quantity_march, :date_expiration_march, :point_acces_march, :img_march)'
            );

            $statement->execute([
                'name_march' => $payload['name_march'],
                'description_march' => $payload['description_march'],
                'price_march' => $payload['price_march'],
                'quantity_march' => $payload['quantity_march'],
                'date_expiration_march' => $payload['date_expiration_march'],
                'point_acces_march' => $payload['point_acces_march'],
                'img_march' => $imageBinary,
            ]);

            $productId = (int) $this->db->lastInsertId();
            $this->linkToStores($productId, $payload['id_mag']);

            $this->db->commit();

            return $productId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function update(array $payload, array $image): void
    {
        $this->db->beginTransaction();

        try {
            if ($this->hasNewImage($image)) {
                $this->updateWithImage($payload, file_get_contents($image['tmp_name']));
            } else {
                $this->updateWithoutImage($payload);
            }

            $this->updateStoreLinks((int) $payload['id_march'], $payload['id_mag']);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function delete(int $productId): void
    {
        $this->db->beginTransaction();

        try {
            $linkStatement = $this->db->prepare('DELETE FROM vendre WHERE id_march = :id_march');
            $linkStatement->execute(['id_march' => $productId]);

            $productStatement = $this->db->prepare('DELETE FROM marchandise WHERE id_march = :id_march');
            $productStatement->execute(['id_march' => $productId]);

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function fetchImageById(int $productId): string|false|null
    {
        $statement = $this->db->prepare('SELECT img_march FROM marchandise WHERE id_march = :id_march');
        $statement->execute(['id_march' => $productId]);

        return $statement->fetchColumn();
    }

    public function fetchAvailabilityById(int $productId): array
    {
        $statement = $this->db->prepare(
            'SELECT
                mag.id_mag,
                mag.name_mag,
                mag.email_mag,
                mag.phone_mag,
                mag.adress_mag,
                mag.img_mag IS NOT NULL AS has_image,
                CASE WHEN v.id_march IS NULL THEN 0 ELSE 1 END AS is_available
             FROM magasin mag
             LEFT JOIN vendre v ON v.id_mag = mag.id_mag AND v.id_march = :id_march
             ORDER BY mag.name_mag ASC'
        );
        $statement->execute(['id_march' => $productId]);

        return $statement->fetchAll();
    }

    private function linkToStores(int $productId, array $storeIds): void
    {
        $statement = $this->db->prepare('INSERT INTO vendre (id_march, id_mag) VALUES (:id_march, :id_mag)');

        foreach ($storeIds as $storeId) {
            $statement->execute([
                'id_march' => $productId,
                'id_mag' => (int) $storeId,
            ]);
        }
    }

    private function updateStoreLinks(int $productId, array $storeIds): void
    {
        $deleteStatement = $this->db->prepare('DELETE FROM vendre WHERE id_march = :id_march');
        $deleteStatement->execute(['id_march' => $productId]);

        $this->linkToStores($productId, $storeIds);
    }

    private function hasNewImage(array $image): bool
    {
        return isset($image['tmp_name']) && $image['tmp_name'] !== '' && is_uploaded_file($image['tmp_name']);
    }

    private function updateWithImage(array $payload, string $imageBinary): void
    {
        $statement = $this->db->prepare(
            'UPDATE marchandise
             SET name_march = :name_march,
                 description_march = :description_march,
                 price_march = :price_march,
                 quantity_march = :quantity_march,
                 date_expiration_march = :date_expiration_march,
                 point_acces_march = :point_acces_march,
                 img_march = :img_march
             WHERE id_march = :id_march'
        );
        $statement->execute([
            'name_march' => $payload['name_march'],
            'description_march' => $payload['description_march'],
            'price_march' => $payload['price_march'],
            'quantity_march' => $payload['quantity_march'],
            'date_expiration_march' => $payload['date_expiration_march'],
            'point_acces_march' => $payload['point_acces_march'],
            'img_march' => $imageBinary,
            'id_march' => $payload['id_march'],
        ]);
    }

    private function updateWithoutImage(array $payload): void
    {
        $statement = $this->db->prepare(
            'UPDATE marchandise
             SET name_march = :name_march,
                 description_march = :description_march,
                 price_march = :price_march,
                 quantity_march = :quantity_march,
                 date_expiration_march = :date_expiration_march,
                 point_acces_march = :point_acces_march
             WHERE id_march = :id_march'
        );
        $statement->execute([
            'name_march' => $payload['name_march'],
            'description_march' => $payload['description_march'],
            'price_march' => $payload['price_march'],
            'quantity_march' => $payload['quantity_march'],
            'date_expiration_march' => $payload['date_expiration_march'],
            'point_acces_march' => $payload['point_acces_march'],
            'id_march' => $payload['id_march'],
        ]);
    }
}
