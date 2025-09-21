<?php

declare(strict_types=1);

namespace BeLocal;

/**
 * BeLocalEngine - A PHP library for text translation via API
 * 
 * This library provides functionality to translate text using a translation API.
 * It uses cURL for HTTP requests with keep-alive connections.
 */
class BeLocalEngine
{
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
     * @param string $baseUrl Base URL for the translation API
     * @param int $timeout Timeout in seconds for API requests
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://dynamic.belocal.dev/v1/translate',
        int $timeout = 30
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
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
            'Authorization: Bearer ' . $this->apiKey
        ));
    }

    /**
     * Translate text
     * 
     * @param string $text Text to translate
     * @param string $lang Target language code
     * @param array $context Additional context for translation (optional)
     * @return string Translated text or original text on error
     * @throws \Exception If API request fails
     */
    public function t(string $text, string $lang, array $context = [])
    {
        if (empty($text)) {
            return $text;
        }

        try {
            $data = array(
                'text' => $text,
                'lang' => $lang
            );

            if (!empty($context)) {
                $data['ctx'] = $context;
            }

            curl_setopt($this->curlHandle, CURLOPT_URL, $this->baseUrl);
            curl_setopt($this->curlHandle, CURLOPT_POST, true);
            curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, json_encode($data));

            $response = curl_exec($this->curlHandle);

            if ($response === false) {
                $error = curl_error($this->curlHandle);
                $errno = curl_errno($this->curlHandle);
                throw new \Exception("cURL error ($errno): $error");
            }

            $httpCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200) {
                throw new \Exception("API returned non-200 status code: $httpCode");
            }

            $result = json_decode($response, true);

            return $result['text'];
        } catch (\Exception $e) {
            return $text;
        }
    }

    public function __destruct()
    {
        if (is_resource($this->curlHandle)) {
            curl_close($this->curlHandle);
        }
    }
}
