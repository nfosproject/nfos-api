<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EsewaPaymentService
{
    private const ESEWA_MERCHANT_ID = 'EPAYTEST';
    private const ESEWA_API_URL = 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';

    private function getSecretKey(): string
    {
        return config('services.esewa.secret_key');
    }

    private function getMerchantId(): string
    {
        return config('services.esewa.merchant_id', self::ESEWA_MERCHANT_ID);
    }

    public function generatePaymentFormData(array $orderData): array
    {
        $transactionUuid = $orderData['transaction_uuid'] ?? Str::uuid()->toString();
        $amount = (string) ($orderData['amount'] ?? 0);
        $taxAmount = (string) ($orderData['tax_amount'] ?? 0);
        $totalAmount = (string) ($orderData['total_amount'] ?? 0);
        $productCode = $orderData['product_code'] ?? $this->getMerchantId();
        $productName = $orderData['product_name'] ?? 'MERZi Order';
        $productServiceCharge = (string) ($orderData['product_service_charge'] ?? 0);
        $productDeliveryCharge = (string) ($orderData['product_delivery_charge'] ?? 0);
        $successUrl = $orderData['success_url'] ?? '';
        $failureUrl = $orderData['failure_url'] ?? '';

        $signedFieldNames = 'total_amount,transaction_uuid,product_code';
        $signature = $this->generateSignature([
            'total_amount' => $totalAmount,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $productCode,
        ]);

        return [
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $productCode,
            'product_name' => $productName,
            'product_service_charge' => $productServiceCharge,
            'product_delivery_charge' => $productDeliveryCharge,
            'success_url' => $successUrl,
            'failure_url' => $failureUrl,
            'signed_field_names' => $signedFieldNames,
            'signature' => $signature,
        ];
    }

    public function generateSignature(array $data): string
    {
        $message = '';
        $signedFields = ['total_amount', 'transaction_uuid', 'product_code'];

        foreach ($signedFields as $field) {
            if (isset($data[$field])) {
                $message .= $field . '=' . $data[$field] . ',';
            }
        }

        $message = rtrim($message, ',');
        $secretKey = $this->getSecretKey();

        return base64_encode(hash_hmac('sha256', $message, $secretKey, true));
    }

    public function verifySignature(array $data): bool
    {
        if (!isset($data['signature']) || !isset($data['signed_field_names'])) {
            return false;
        }

        $signedFields = explode(',', $data['signed_field_names']);
        $message = '';

        foreach ($signedFields as $field) {
            if (isset($data[$field])) {
                $message .= $field . '=' . $data[$field] . ',';
            }
        }

        $message = rtrim($message, ',');
        $secretKey = $this->getSecretKey();
        $expectedSignature = base64_encode(hash_hmac('sha256', $message, $secretKey, true));

        return hash_equals($expectedSignature, $data['signature']);
    }

    public function getApiUrl(): string
    {
        return self::ESEWA_API_URL;
    }
}

