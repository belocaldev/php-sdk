<?php

declare(strict_types=1);

namespace BeLocal;

/**
 * BeLocalEngine - A PHP library for text translation via API
 * 
 * This library provides functionality to translate text using a translation API.
 */
class BeLocalEngine
{
    /**
     * @var Transport The transport layer for API communication
     */
    private Transport $transport;

    /**
     * Constructor
     *
     * @param Transport $transport
     */
    public function __construct(Transport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Factory for creating without creating transport
     *
     * @param string $apiKey API key for authentication
     * @param int $timeout Timeout in seconds for API requests
     */
    public static function withApiKey(
        string $apiKey,
        int $timeout = 30
    ): self {
        return new self(new Transport($apiKey, Transport::BASE_URL, $timeout));
    }

    /**
     * Translate a single text
     * 
     * Returns the translated text, or the original text if translation fails.
     * 
     * @param string $text The text to translate
     * @param string $lang Target language code (e.g., 'en', 'es', 'fr')
     * @param string|null $sourceLang Source language code (optional, auto-detect if null)
     * @param string $userContext User context string for translation context
     * @param bool $managed Use managed translations cache type (editable translations)
     * @return string Translated text, or original text if translation fails
     */
    public function t(string $text, string $lang, ?string $sourceLang, string $userContext, bool $managed = false): string
    {
        $ctx = [TranslateRequest::CTX_KEY_USER_CONTEXT => $userContext];
        if ($managed) {
            $ctx[TranslateRequest::CTX_KEY_CACHE_TYPE] = TranslateRequest::CACHE_TYPE_MANAGED;
        }

        $result = $this->translateRequest(new TranslateRequest([$text], $lang, $sourceLang, $ctx))->getResult();

        $texts = $result->getTexts();
        return ($result->isOk() && $texts !== null && isset($texts[0])) ? $texts[0] : $text;
    }

    /**
     * Translate multiple texts
     * 
     * Returns an array of translated texts, or the original texts if translation fails.
     * 
     * @param array<string> $texts Array of texts to translate
     * @param string $lang Target language code (e.g., 'en', 'es', 'fr')
     * @param string|null $sourceLang Source language code (optional, auto-detect if null)
     * @param string $userContext User context string for translation context
     * @param bool $managed Use managed translations cache type (editable translations)
     * @return array<string> Array of translated texts, or original texts if translation fails
     */
    public function tMany(array $texts, string $lang, ?string $sourceLang, string $userContext, bool $managed = false): array
    {
        $ctx = [TranslateRequest::CTX_KEY_USER_CONTEXT => $userContext];
        if ($managed) {
            $ctx[TranslateRequest::CTX_KEY_CACHE_TYPE] = TranslateRequest::CACHE_TYPE_MANAGED;
        }

        $result = $this->translateRequest(new TranslateRequest($texts, $lang, $sourceLang, $ctx))->getResult();

        $translatedTexts = $result->getTexts();
        return ($result->isOk() && $translatedTexts !== null) ? $translatedTexts : $texts;
    }

    /**
     * Translate a single TranslateRequest object (works like translateMultiRequest for one entity)
     *
     * @param TranslateRequest $request TranslateRequest object to translate
     * @return TranslateRequest The same TranslateRequest object with filled result property
     */
    public function translateRequest(TranslateRequest $request): TranslateRequest
    {
        $results = $this->translateMultiRequest([$request]);

        return $results[0];
    }


    /**
     * Translate multiple TranslateRequest objects in a single API call
     *
     * @param array<TranslateRequest> $requests Array of TranslateRequest objects
     * @return array<TranslateRequest> The same array of TranslateRequest objects with filled result property
     * @throws \InvalidArgumentException If requests array is empty or contains non-TranslateRequest elements
     */
    public function translateMultiRequest(array $requests): array
    {
        if (count($requests) === 0) {
            throw new \InvalidArgumentException('Requests array cannot be empty');
        }

        // Validate that all elements are TranslateRequest instances
        foreach ($requests as $index => $request) {
            if (!($request instanceof TranslateRequest)) {
                throw new \InvalidArgumentException(
                    sprintf('Expected array<TranslateRequest>, but element at index %d is %s', $index, gettype($request))
                );
            }
        }

        $requestBody = ['requests' => []];
        foreach ($requests as $request) {
            $requestBody['requests'][] = $request->toRequestArray();
        }

        $response = $this->transport->sendMulti($requestBody);

        $resultMap = TranslateManyResult::fromMultiResponse($response);

        foreach ($requests as $request) {
            $requestId = $request->getRequestId();
            if (isset($resultMap[$requestId])) {
                $request->setResult($resultMap[$requestId]);
            } else {
                $error = $response->getError() ?? new BeLocalError(BeLocalError::UNCAUGHT, 'No result found for request_id: ' . $requestId);
                $request->setResult(new TranslateManyResult(
                    null,
                    false,
                    $error,
                    $response->getHttpCode(),
                    $response->getCurlErrno(),
                    $response->getRaw()
                ));
            }
        }

        return $requests;
    }
}
