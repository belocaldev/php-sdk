<?php

declare(strict_types=1);

namespace BeLocal;

/**
 * Transport - Handles HTTP communication with the BeLocal API
 * 
 * This class is responsible for sending HTTP requests to the BeLocal API
 * and processing the responses.
 */
class Transport
{
    const SDK_VERSION = '0.4.3';

    const SDK_NAME = 'php';

    const BASE_URL = 'https://dynamic.belocal.dev';

    /**
     * @var string Base URL for the translation API
     */
    private $baseUrl;

    /**
     * @var string API key for authentication
     */
    private $apiKey;

    /**
     * @var int Timeout in seconds for API requests
     */
    private $timeout;

    /**
     * @var resource cURL handle
     */
    private $curlHandle;

    /**
     * Constructor
     *
     * @param string $apiKey API key for authentication
     * @param string $baseUrl Base URL for the translation API (optional, for testing)
     * @param int $timeout Timeout in seconds for API requests
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = self::BASE_URL,
        int $timeout = 30
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;

        $this->initCurl();
    }

    private function initCurl()
    {
        $this->curlHandle = curl_init();

        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curlHandle, CURLOPT_MAXREDIRS, 5);
        curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, $this->timeout);

        curl_setopt($this->curlHandle, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($this->curlHandle, CURLOPT_FORBID_REUSE, false);
        curl_setopt($this->curlHandle, CURLOPT_FRESH_CONNECT, false);
        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, array(
            'Connection: keep-alive',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'X-Sdk: ' . self::SDK_NAME,
            'X-Sdk-Version: ' . self::SDK_VERSION,
        ));
    }

    /**
     * Send a request to the API
     *
     * @param array $data The data to send in the request
     * @param string $endpoint The API endpoint to use
     * @return TranslateResponse The result of the request
     */
    private function sendRequest(array $data, string $endpoint): TranslateResponse
    {
        try {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);

            if ($json === false) {
                $err = json_last_error();
                if ($err === JSON_ERROR_UTF8) {
                    throw new BeLocalException(
                        new BeLocalError(BeLocalErrorCode::JSON_UTF8, 'Invalid UTF-8 string passed to json_encode()')
                    );
                }

                throw new BeLocalException(
                    new BeLocalError(BeLocalErrorCode::JSON_ENCODE, 'json_encode() failed with error code: ' . $err)
                );
            }

            curl_setopt($this->curlHandle, CURLOPT_URL, $this->baseUrl . $endpoint);
            curl_setopt($this->curlHandle, CURLOPT_POST, true);
            curl_setopt(
                $this->curlHandle,
                CURLOPT_POSTFIELDS,
                $json
            );

            $response = curl_exec($this->curlHandle);

            if ($response === false) {
                $errno = curl_errno($this->curlHandle);
                $error = curl_error($this->curlHandle);

                return new TranslateResponse(
                    null,
                    false,
                    new BeLocalError(BeLocalErrorCode::NETWORK, "cURL error ($errno): $error"),
                    null,
                    $errno
                );
            }

            $httpCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200) {
                return match ($httpCode) {
                    402 => new TranslateResponse(
                        null,
                        false,
                        new BeLocalError(BeLocalErrorCode::PAYMENT_REQUIRED, 'Insufficient balance'),
                        $httpCode,
                        null,
                        $response
                    ),
                    default => new TranslateResponse(
                        null,
                        false,
                        new BeLocalError(BeLocalErrorCode::HTTP_NON_200, 'API returned non-200 status code: ' . $httpCode),
                        $httpCode,
                        null,
                        $response
                    ),
                };
            }

            $decoded = json_decode($response, true);
            if (!is_array($decoded)) {
                return new TranslateResponse(
                    null,
                    false,
                    new BeLocalError(BeLocalErrorCode::DECODE, 'Invalid JSON response'),
                    $httpCode,
                    null,
                    $response
                );
            }

            return new TranslateResponse($decoded, true, null, $httpCode, null, $response);
        } catch (\Throwable $e) {
            return new TranslateResponse(
                null,
                false,
                new BeLocalError(BeLocalErrorCode::UNCAUGHT, $e->getMessage())
            );
        }
    }

    /**
     * Send a single translation request
     *
     * @param array $data The data to send in the request
     * @return TranslateResponse The result of the request
     */
    public function send(array $data): TranslateResponse
    {
        return $this->sendRequest($data, '/v1/translate');
    }

    /**
     * Send a batch translation request
     *
     * @param array $data The data to send in the request
     * @return TranslateResponse The result of the request
     */
    public function sendBatch(array $data): TranslateResponse
    {
        return $this->sendRequest($data, '/v1/translate/batch');
    }

    /**
     * Clean up resources
     */
    public function __destruct()
    {
        if ($this->curlHandle !== null) {
            curl_close($this->curlHandle);
        }
    }
}
