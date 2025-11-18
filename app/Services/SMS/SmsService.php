<?php

namespace App\Services\SMS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $provider;
    protected ?string $apiKey;
    protected ?string $apiSecret;
    protected ?string $from;
    protected ?string $baseUrl;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'twilio');
        $this->apiKey = config('services.sms.api_key');
        $this->apiSecret = config('services.sms.api_secret');
        $this->from = config('services.sms.from');
        $this->baseUrl = config('services.sms.base_url');
    }

    public function send(string $to, string $message): bool
    {
        if (empty($to) || empty($message)) {
            Log::warning('SMS not sent: missing phone number or message', [
                'to' => $to,
                'message_length' => strlen($message),
            ]);
            return false;
        }

        // Clean phone number (remove spaces, dashes, etc.)
        $to = preg_replace('/[^0-9+]/', '', $to);

        if (config('services.sms.enabled', true) === false) {
            Log::info('SMS sending disabled', ['to' => $to, 'message' => $message]);
            return false;
        }

        try {
            return match ($this->provider) {
                'twilio' => $this->sendViaTwilio($to, $message),
                'vonage' => $this->sendViaVonage($to, $message),
                'http' => $this->sendViaHttp($to, $message),
                default => $this->sendViaTwilio($to, $message),
            };
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'provider' => $this->provider,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function sendViaTwilio(string $to, string $message): bool
    {
        $accountSid = $this->apiKey;
        $authToken = $this->apiSecret;
        $from = $this->from;

        if (empty($accountSid) || empty($authToken) || empty($from)) {
            Log::warning('Twilio credentials not configured');
            return false;
        }

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $from,
                'To' => $to,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            Log::info('SMS sent via Twilio', ['to' => $to]);
            return true;
        }

        Log::error('Twilio SMS failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    protected function sendViaVonage(string $to, string $message): bool
    {
        $apiKey = $this->apiKey;
        $apiSecret = $this->apiSecret;
        $from = $this->from;

        if (empty($apiKey) || empty($apiSecret) || empty($from)) {
            Log::warning('Vonage credentials not configured');
            return false;
        }

        $response = Http::withBasicAuth($apiKey, $apiSecret)
            ->post('https://rest.nexmo.com/sms/json', [
                'from' => $from,
                'to' => $to,
                'text' => $message,
            ]);

        if ($response->successful() && $response->json('messages.0.status') === '0') {
            Log::info('SMS sent via Vonage', ['to' => $to]);
            return true;
        }

        Log::error('Vonage SMS failed', [
            'response' => $response->json(),
        ]);

        return false;
    }

    protected function sendViaHttp(string $to, string $message): bool
    {
        $baseUrl = $this->baseUrl;

        if (empty($baseUrl)) {
            Log::warning('HTTP SMS base URL not configured');
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($baseUrl, [
            'to' => $to,
            'message' => $message,
            'from' => $this->from,
        ]);

        if ($response->successful()) {
            Log::info('SMS sent via HTTP', ['to' => $to]);
            return true;
        }

        Log::error('HTTP SMS failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    public function sendOrderStatusUpdate(string $to, string $orderNumber, string $status): bool
    {
        $statusMessages = [
            'pending' => "Your order {$orderNumber} is pending confirmation.",
            'processing' => "Your order {$orderNumber} is being processed.",
            'shipped' => "Great news! Your order {$orderNumber} has been shipped and is on its way to you.",
            'completed' => "Your order {$orderNumber} has been delivered. We hope you love it!",
            'cancelled' => "Your order {$orderNumber} has been cancelled. Contact support if this was unexpected.",
            'refunded' => "A refund has been processed for order {$orderNumber}.",
        ];

        $message = $statusMessages[strtolower($status)] ?? "Your order {$orderNumber} status has been updated to {$status}.";

        return $this->send($to, $message);
    }
}

