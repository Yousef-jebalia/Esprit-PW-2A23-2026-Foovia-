<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Model/MARKETPLACE_MODULE/Magasin.php';

final class MagasinController
{
    private Magasin $magasin;

    public function __construct(?Magasin $magasin = null)
    {
        $this->magasin = $magasin ?? new Magasin();
    }

    public function handle(): void
    {
        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'index');

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save();
            return;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->delete();
            return;
        }

        if ($action === 'image') {
            $this->showImage();
            return;
        }

        $this->redirect('/integration%20foovia/MVC/View/back_office/MARKETPLACE_MODULE/magasins.php');
    }

    private function save(): void
    {
        $payload = $this->buildStorePayload();
        $image = $_FILES['img_mag'] ?? null;

        try {
            if ($payload['id_mag'] > 0) {
                $this->magasin->update($payload, is_array($image) ? $image : []);
                $this->redirect('/integration%20foovia/MVC/View/back_office/MARKETPLACE_MODULE/magasins.php?status=updated');
            }

            $this->magasin->create($payload, is_array($image) ? $image : []);
            $this->redirect('/integration%20foovia/MVC/View/back_office/MARKETPLACE_MODULE/magasins.php?status=success');
        } catch (Throwable $exception) {
            $this->redirect('/integration%20foovia/MVC/View/back_office/MARKETPLACE_MODULE/magasins.php?status=dberror');
        }
    }

    private function showImage(): void
    {
        $storeId = (int) ($_GET['id'] ?? 0);

        if ($storeId <= 0) {
            http_response_code(404);
            return;
        }

        $imageBinary = $this->magasin->fetchImageById($storeId);

        if ($imageBinary === false || $imageBinary === null) {
            http_response_code(404);
            return;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageBinary) ?: 'image/jpeg';

        header('Content-Type: ' . $mimeType);
        echo $imageBinary;
    }

    private function delete(): void
    {
        try {
            $this->magasin->delete((int) ($_POST['id_mag'] ?? 0));
            $this->redirect('/integration%20foovia/MVC/View/back_office/MARKETPLACE_MODULE/magasins.php?status=deleted');
        } catch (Throwable $exception) {
            $this->redirect('/integration%20foovia/MVC/View/back_office/MARKETPLACE_MODULE/magasins.php?status=deleteerror');
        }
    }

    private function buildStorePayload(): array
    {
        return [
            'id_mag' => (int) ($_POST['id_mag'] ?? 0),
            'name_mag' => (string) ($_POST['name_mag'] ?? ''),
            'email_mag' => (string) ($_POST['email_mag'] ?? ''),
            'phone_mag' => (string) ($_POST['phone_mag'] ?? ''),
            'adress_mag' => (string) ($_POST['adress_mag'] ?? ''),
        ];
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}

(new MagasinController())->handle();
