<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppTemplateService
{
    protected string $phoneNumberId;
    protected string $accessToken;
    protected string $apiVersion;

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->accessToken = config('services.whatsapp.token');
        $this->apiVersion = 'v18.0';
    }

    /**
     * Send first reply template message.
     *
     * @param  string  $to
     * @param  string  $customerName
     * @return array
     */
    public function sendFirstReply(string $to, string $customerName = 'there'): array
    {
        $templateName = 'first_reply';
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'en',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $customerName,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send price display template message.
     *
     * @param  string  $to
     * @param  string  $brand
     * @param  string  $model
     * @param  string  $problemType
     * @param  float  $priceMin
     * @param  float  $priceMax
     * @return array
     */
    public function sendPriceDisplay(
        string $to,
        string $brand,
        string $model,
        string $problemType,
        float $priceMin,
        float $priceMax
    ): array {
        $templateName = 'price_display';
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'en',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $brand,
                            ],
                            [
                                'type' => 'text',
                                'text' => $model,
                            ],
                            [
                                'type' => 'text',
                                'text' => $problemType,
                            ],
                            [
                                'type' => 'text',
                                'text' => number_format($priceMin, 2),
                            ],
                            [
                                'type' => 'text',
                                'text' => number_format($priceMax, 2),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send location request template message.
     *
     * @param  string  $to
     * @return array
     */
    public function sendLocationRequest(string $to): array
    {
        $templateName = 'location_request';
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'en',
                ],
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send success and coupon confirmation template message.
     *
     * @param  string  $to
     * @param  string  $ticketId
     * @param  string  $couponCode
     * @param  int  $discountPercent
     * @return array
     */
    public function sendSuccessCouponConfirmation(
        string $to,
        string $ticketId,
        string $couponCode,
        int $discountPercent
    ): array {
        $templateName = 'success_coupon_confirmation';
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'en',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $ticketId,
                            ],
                            [
                                'type' => 'text',
                                'text' => $couponCode,
                            ],
                            [
                                'type' => 'text',
                                'text' => (string) $discountPercent,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send WhatsApp message using Laravel Http.
     *
     * @param  array  $payload
     * @return array
     */
    protected function sendMessage(array $payload): array
    {
        try {
            $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->accessToken}",
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp template message sent', [
                    'to' => $payload['to'],
                    'template' => $payload['template']['name'] ?? 'unknown',
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->json('messages.0.id'),
                    'data' => $response->json(),
                ];
            }

            Log::error('WhatsApp template message failed', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp template message exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

