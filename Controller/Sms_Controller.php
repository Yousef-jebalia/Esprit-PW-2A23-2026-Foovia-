<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/TwilioSms.php';

final class SmsController
{
    private TwilioSms $sms;

    public function __construct(?TwilioSms $sms = null)
    {
        $this->sms = $sms ?? new TwilioSms();
    }

    public function handle(): void
    {
        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');

        try {
            if ($action === 'delivery_done' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->sendDeliveryDoneMessage();
                return;
            }

            http_response_code(404);
            $this->respond(['ok' => false, 'message' => 'Unknown SMS action.']);
        } catch (Throwable $exception) {
            http_response_code(400);
            $this->respond(['ok' => false, 'message' => $exception->getMessage()]);
        }
    }

    private function sendDeliveryDoneMessage(): void
    {
        $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
        $phone = (string) ($payload['phone'] ?? '');
        $reference = (string) ($payload['reference'] ?? 'Foovia order');
        $hub = (string) ($payload['hubName'] ?? 'your selected store');

        if ($phone === '') {
            throw new InvalidArgumentException('Missing phone number.');
        }

        $message = 'dil';

        $result = $this->sms->sendMessage($phone, $message);
        $this->respond(['ok' => true, 'result' => $result]);
    }

    private function respond(array $payload): void
    {
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_THROW_ON_ERROR);
    }
}

$controller = new SmsController();
$controller->handle();
