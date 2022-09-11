<?php

namespace Calendly;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;

class CalendlyApiV2
{

    /**
     * @const string
     */
    public const EVENT_CREATED = 'invitee.created';

    /**
     * @const string
     */
    public const EVENT_CANCELED = 'invitee.canceled';

    /**
     * @const string
     */
    private const METHOD_GET = 'get';

    /**
     * @const string
     */
    private const METHOD_POST = 'post';

    /**
     * @const string
     */
    private const METHOD_DELETE = 'delete';

    /**
     * @const string
     */
    protected const API_URL = 'https://api.calendly.com';

    /**
     * @var Client
     */
    private $client;

    /**
     * @param string      $apiKey
     * @param Client|null $client
     */
    public function __construct($apiKeyV2, Client $client = null)
    {
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
    public function createWebhook($url, $events, $organizationUrl, $userUrl): array
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
    public function getWebhook($id): array
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
    public function getWebhooks(): array
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

    /**
     * Test authentication token.
     *
     * @return array
     *
     * @throws CalendlyApiException
     */
    public function echo(): array
    {
        return $this->callApi(self::METHOD_GET, 'echo');
    }


    /**
     * @param string $method
     * @param string $endpoint
     * @param array  $params
     *
     * @return array|null
     *
     * @throws CalendlyApiException
     */
    private function callApi($method, $endpoint, array $params = [])
    {
        $url = sprintf('/%s', $endpoint);

        $data = [
            RequestOptions::QUERY => $params,
        ];

        if ($method != self::METHOD_GET) {
            $data = [
                RequestOptions::JSON => $params,
            ];
        }

        try {
            try {
                $response = $this->client->request($method, $url, $data);
            } catch (GuzzleException $e) {
                if ($e instanceof ClientException && $e->getResponse()) {
                    $response = $e->getResponse();
                    $message  = (string)$response->getBody();
                    $headers  = $response->getHeader('content-type');

                    if (count($headers) && strpos($headers[0], 'application/json') === 0) {
                        $message = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                        $message = $message['message'];
                    }

                    throw new CalendlyApiException($message, $response->getStatusCode());
                } else {
                    throw new CalendlyApiException('Failed to get Calendly data: ' . $e->getMessage(), $e->getCode());
                }
            }

            $headers = $response->getHeader('content-type');

            if (count($headers) && strpos($headers[0], 'application/json') === 0) {
                $response = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (JsonException $e) {
            throw new CalendlyApiException('Invalid JSON: ' . $e->getMessage(), 500);
        }

        return $response;
    }
}
