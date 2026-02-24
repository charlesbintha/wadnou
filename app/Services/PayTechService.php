<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayTechService
{
    private string $apiKey;
    private string $apiSecret;
    private string $env;

    public function __construct()
    {
        $this->apiKey    = config('services.paytech.api_key', '');
        $this->apiSecret = config('services.paytech.api_secret', '');
        $this->env       = config('services.paytech.env', 'test');
    }

    /**
     * Initier une demande de paiement PayTech.
     *
     * @param  array{
     *   item_name: string,
     *   item_price: int,
     *   ref_command: string,
     *   command_name: string,
     *   ipn_url: string,
     *   success_url?: string,
     *   cancel_url?: string,
     *   custom_field?: string,
     *   target_payment?: string,
     * } $params
     * @return array{success: bool, token?: string, redirect_url?: string, error?: string}
     */
    public function requestPayment(array $params): array
    {
        $payload = array_merge([
            'currency'    => 'XOF',
            'env'         => $this->env,
            'success_url' => 'https://paytech.sn/mobile/success',
            'cancel_url'  => 'https://paytech.sn/mobile/cancel',
        ], $params);

        try {
            $response = Http::withHeaders([
                'API_KEY'    => $this->apiKey,
                'API_SECRET' => $this->apiSecret,
                'Accept'     => 'application/json',
            ])->post('https://paytech.sn/api/payment/request-payment', $payload);

            if (!$response->successful()) {
                Log::error('PayTech requestPayment HTTP error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['success' => false, 'error' => "Erreur PayTech HTTP {$response->status()}"];
            }

            $data = $response->json();

            if (($data['success'] ?? 0) !== 1) {
                Log::error('PayTech requestPayment rejet', $data);
                return ['success' => false, 'error' => 'PayTech a refuse la demande de paiement.'];
            }

            return [
                'success'      => true,
                'token'        => $data['token'],
                'redirect_url' => $data['redirect_url'] ?? $data['redirectUrl'],
            ];
        } catch (\Throwable $e) {
            Log::error('PayTech requestPayment exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Impossible de joindre PayTech.'];
        }
    }

    /**
     * Verifier la signature d'une notification IPN PayTech.
     * Methode recommandee : HMAC-SHA256
     * Fallback : SHA256 des cles.
     */
    public function verifyIpn(array $params): bool
    {
        // Methode 1 (recommandee) : HMAC-SHA256
        if (!empty($params['hmac_compute'])) {
            $amount     = $params['item_price'] ?? '';
            $refCommand = $params['ref_command'] ?? '';
            $message    = "{$amount}|{$refCommand}|{$this->apiKey}";
            $expected   = hash_hmac('sha256', $message, $this->apiSecret);

            return hash_equals($expected, $params['hmac_compute']);
        }

        // Methode 2 (fallback) : SHA256 des cles
        if (!empty($params['api_key_sha256']) && !empty($params['api_secret_sha256'])) {
            return hash_equals(hash('sha256', $this->apiKey), $params['api_key_sha256'])
                && hash_equals(hash('sha256', $this->apiSecret), $params['api_secret_sha256']);
        }

        return false;
    }
}
