<?php

declare(strict_types=1);

require_once __DIR__ . '/TwilioSmsConfig.php';

final class TwilioSms
{
    public function isConfigured(): bool
    {
        return TwilioSmsConfig::isConfigured();
    }

    public function sendMessage(string $phone, string $message): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Twilio SMS is not configured.');
        }

        $endpoint = sprintf(
            '%s/Accounts/%s/Messages.json',
            rtrim(TwilioSmsConfig::BASE_URL, '/'),
            TwilioSmsConfig::accountSid()
        );

        $payload = http_build_query([
            'To' => $this->normalizePhone($phone),
            'From' => TwilioSmsConfig::fromNumber(),
            'Body' => $message,
        ]);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_USERPWD => TwilioSmsConfig::accountSid() . ':' . TwilioSmsConfig::authToken(),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            throw new RuntimeException('Could not reach Twilio SMS.');
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $data = json_decode($raw, true);
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            $apiMessage = is_array($data) ? (string) (($data['message'] ?? $data['detail']) ?? '') : '';
            throw new RuntimeException(
                $apiMessage !== ''
                    ? 'Twilio SMS error: ' . $apiMessage
                    : 'Twilio SMS could not send the message.'
            );
        }

        return $data;
    }

    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', $phone) ?? '';
        if ($clean === '') {
            throw new InvalidArgumentException('Missing phone number.');
        }

        if ($clean[0] !== '+') {
            $clean = '+' . ltrim($clean, '+');
        }

        return $clean;
    }
}
