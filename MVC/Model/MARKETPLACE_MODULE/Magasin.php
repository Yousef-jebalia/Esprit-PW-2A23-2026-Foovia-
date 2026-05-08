<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

final class Magasin
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function fetchAll(): array
    {
        $statement = $this->db->query(
            'SELECT id_mag, name_mag, email_mag, phone_mag, adress_mag, img_mag IS NOT NULL AS has_image
             FROM magasin
             ORDER BY name_mag ASC'
        );

        return $statement->fetchAll();
    }

    public function findById(int $storeId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id_mag, name_mag, email_mag, phone_mag, adress_mag, img_mag IS NOT NULL AS has_image
             FROM magasin
             WHERE id_mag = :id_mag'
        );
        $statement->execute(['id_mag' => $storeId]);
        $store = $statement->fetch();

        return $store ?: null;
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM magasin')->fetchColumn();
    }

    public function create(array $payload, array $image): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO magasin (name_mag, email_mag, phone_mag, adress_mag, img_mag)
             VALUES (:name_mag, :email_mag, :phone_mag, :adress_mag, :img_mag)'
        );
        $statement->execute([
            'name_mag' => $payload['name_mag'],
            'email_mag' => $payload['email_mag'],
            'phone_mag' => $payload['phone_mag'],
            'adress_mag' => $payload['adress_mag'],
            'img_mag' => $this->hasNewImage($image) ? file_get_contents($image['tmp_name']) : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(array $payload, array $image): void
    {
        if ($this->hasNewImage($image)) {
            $this->updateWithImage($payload, file_get_contents($image['tmp_name']));
            return;
        }

        $this->updateWithoutImage($payload);
    }

    public function fetchImageById(int $storeId): string|false|null
    {
        $statement = $this->db->prepare('SELECT img_mag FROM magasin WHERE id_mag = :id_mag');
        $statement->execute(['id_mag' => $storeId]);

        return $statement->fetchColumn();
    }

    private function hasNewImage(array $image): bool
    {
        return isset($image['tmp_name']) && $image['tmp_name'] !== '' && is_uploaded_file($image['tmp_name']);
    }

    private function updateWithImage(array $payload, string $imageBinary): void
    {
        $statement = $this->db->prepare(
            'UPDATE magasin
             SET name_mag = :name_mag,
                 email_mag = :email_mag,
                 phone_mag = :phone_mag,
                 adress_mag = :adress_mag,
                 img_mag = :img_mag
             WHERE id_mag = :id_mag'
        );
        $statement->execute([
            'name_mag' => $payload['name_mag'],
            'email_mag' => $payload['email_mag'],
            'phone_mag' => $payload['phone_mag'],
            'adress_mag' => $payload['adress_mag'],
            'img_mag' => $imageBinary,
            'id_mag' => $payload['id_mag'],
        ]);
    }

    private function updateWithoutImage(array $payload): void
    {
        $statement = $this->db->prepare(
            'UPDATE magasin
             SET name_mag = :name_mag,
                 email_mag = :email_mag,
                 phone_mag = :phone_mag,
                 adress_mag = :adress_mag
             WHERE id_mag = :id_mag'
        );
        $statement->execute([
            'name_mag' => $payload['name_mag'],
            'email_mag' => $payload['email_mag'],
            'phone_mag' => $payload['phone_mag'],
            'adress_mag' => $payload['adress_mag'],
            'id_mag' => $payload['id_mag'],
        ]);
    }

    public function delete(int $storeId): void
    {
        $this->db->beginTransaction();

        try {
            $linkStatement = $this->db->prepare('DELETE FROM vendre WHERE id_mag = :id_mag');
            $linkStatement->execute(['id_mag' => $storeId]);

            $storeStatement = $this->db->prepare('DELETE FROM magasin WHERE id_mag = :id_mag');
            $storeStatement->execute(['id_mag' => $storeId]);

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
