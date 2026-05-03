<?php

declare(strict_types=1);

final class TwilioSmsConfig
{
    public const BASE_URL = 'https://api.twilio.com/2010-04-01';

    private function __construct()
    {
    }

    public static function isConfigured(): bool
    {
        return self::accountSid() !== ''
            && self::authToken() !== ''
            && self::fromNumber() !== ''
            && self::BASE_URL !== '';
    }

    public static function accountSid(): string
    {
        return trim((string) getenv('TWILIO_ACCOUNT_SID'));
    }

    public static function authToken(): string
    {
        return trim((string) getenv('TWILIO_AUTH_TOKEN'));
    }

    public static function fromNumber(): string
    {
        return trim((string) getenv('TWILIO_FROM_NUMBER'));
    }
}
