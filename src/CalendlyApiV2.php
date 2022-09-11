<?php

namespace Calendly;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;
use Log;
use GuzzleHttp\Client;

class CalendlyApiV2 extends CalendlyApi
{
    /**
     * @const string
     */
    protected const API_URL = 'https://api.calendly.com';

    /**
     * @param string      $apiKey
     * @param Client|null $client
     */
    public function __construct($apiKeyV2, Client $client = null)
    {
        parent::__construct($apiKeyV2, $client);
        $this->client = $client ?? new Client([
                'base_uri' => self::API_URL,
                'headers'  => [
                    'Authorization' => 'Bearer '.$apiKeyV2
                ],
            ]);
    }

    /**
     * Create a webhook subscription.
     *
     * @param string $url
     * @param array  $events
     *
     * @return array
     *
     * @throws CalendlyApiException
     */
    public function createWebhookV2($url, $events, $organizationUrl, $userUrl): array
    {
        if (array_diff($events, [self::EVENT_CREATED, self::EVENT_CANCELED])) {
            throw new CalendlyApiException('The specified event types do not exist');
        }

        return $this->callApi(self::METHOD_POST, 'webhook_subscriptions', [
            'url'    => $url,
            'events' => $events,
            "organization" => $organizationUrl,
            "user" => $userUrl,
            "scope" => "user",
        ]);
    }

    /**
     * Get a webhook subscription by ID.
     *
     * @param int $id
     *
     * @return array
     *
     * @throws CalendlyApiException
     */
    public function getWebhookV2($id): array
    {
        return $this->callApi(self::METHOD_GET, 'webhook_subscriptions/' . $id);
    }

    /**
     * Get list of a webhooks subscription.
     *
     * @return array
     *
     * @throws CalendlyApiException
     */
    public function getWebhooksV2(): array
    {
        return $this->callApi(self::METHOD_GET, 'webhook_subscriptions');
    }

    /**
     * Get current user details.
     *
     * @return array
     *
     * @throws CalendlyApiException
     */
    public function getCurrentUser(): array
    {
        return $this->callApi(self::METHOD_GET, 'users/me');
    }

    /**
     * Delete a webhook subscription.
     *
     * @return void
     *
     * @throws CalendlyApiException
     */
    public function deleteWebhook($id): void
    {
        try {
            $this->callApi(self::METHOD_DELETE, 'webhook_subscriptions/' . $id);
        } catch (CalendlyApiException $e) {
            if ($e->getCode() != 404) {
                throw $e;
            }
        }
    }

}
