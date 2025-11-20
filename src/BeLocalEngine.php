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
     * Quick translation method for a single text
     * 
     * This is a convenience method that wraps translateRequest() and returns the translated text directly,
     * or the original text if translation fails.
     * 
     * @param string $text The text to translate
     * @param string $lang Target language code (e.g., 'en', 'es', 'fr')
     * @param string|null $sourceLang Source language code (optional, auto-detect if null)
     * @param string $userContext User context string for translation context
     * @return string Translated text, or original text if translation fails
     *
     */
    public function t(string $text, string $lang, ?string $sourceLang, string $userContext): string
    {
        $result = $this->translateRequest(new TranslateRequest([$text], $lang, $sourceLang, [TranslateRequest::CTX_KEY_USER_CONTEXT => $userContext]))->getResult();

        $texts = $result->getTexts();
        return ($result->isOk() && $texts !== null && isset($texts[0])) ? $texts[0] : $text;
    }

    /**
     * Quick translation method for a single text with editable cache type
     * 
     * This is a convenience method similar to t(), but uses editable cache type,
     * which allows translations to be edited later in the cache.
     * 
     * @param string $text The text to translate
     * @param string $lang Target language code (e.g., 'en', 'es', 'fr')
     * @param string|null $sourceLang Source language code (optional, auto-detect if null)
     * @param string $userContext User context string for translation context
     * @return string Translated text, or original text if translation fails
     *
     */
    public function tEditable(string $text, string $lang, ?string $sourceLang, string $userContext): string
    {
        $result = $this->translateRequest(new TranslateRequest([$text], $lang, $sourceLang, [
            TranslateRequest::CTX_KEY_USER_CONTEXT => $userContext,
            TranslateRequest::CTX_KEY_CACHE_TYPE => TranslateRequest::CACHE_TYPE_EDITABLE,
        ]))->getResult();

        $texts = $result->getTexts();
        return ($result->isOk() && $texts !== null && isset($texts[0])) ? $texts[0] : $text;
    }

    /**
     * Quick translation method for multiple texts
     * 
     * This is a convenience method that wraps translateRequest() and returns an array of translated texts,
     * or the original texts array if translation fails.
     * 
     * @param array<string> $texts Array of texts to translate
     * @param string $lang Target language code (e.g., 'en', 'es', 'fr')
     * @param string|null $sourceLang Source language code (optional, auto-detect if null)
     * @param string $userContext User context string for translation context
     * @return array<string> Array of translated texts, or original texts array if translation fails
     *
     */
    public function tMany(array $texts, string $lang, ?string $sourceLang, string $userContext): array
    {
        $result = $this->translateRequest(new TranslateRequest($texts, $lang, $sourceLang, [TranslateRequest::CTX_KEY_USER_CONTEXT => $userContext]))->getResult();

        $translatedTexts = $result->getTexts();
        return ($result->isOk() && $translatedTexts !== null) ? $translatedTexts : $texts;
    }

    /**
     * Quick translation method for multiple texts with editable cache type
     * 
     * This is a convenience method similar to tMany(), but uses editable cache type,
     * which allows translations to be edited later in the cache.
     * 
     * @param array<string> $texts Array of texts to translate
     * @param string $lang Target language code (e.g., 'en', 'es', 'fr')
     * @param string|null $sourceLang Source language code (optional, auto-detect if null)
     * @param string $userContext User context string for translation context
     * @return array<string> Array of translated texts, or original texts array if translation fails
     *
     */
    public function tManyEditable(array $texts, string $lang, ?string $sourceLang, string $userContext): array
    {
        $result = $this->translateRequest(new TranslateRequest($texts, $lang, $sourceLang, [
            TranslateRequest::CTX_KEY_USER_CONTEXT => $userContext,
            TranslateRequest::CTX_KEY_CACHE_TYPE => TranslateRequest::CACHE_TYPE_EDITABLE,
        ]))->getResult();

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

        $resultMap = TranslateManyResultFactory::fromMultiResponse($response);

        foreach ($requests as $request) {
            $requestId = $request->getRequestId();
            if (isset($resultMap[$requestId])) {
                $request->setResult($resultMap[$requestId]);
            } else {
                $error = $response->getError() ?? new BeLocalError(BeLocalErrorCode::UNCAUGHT, 'No result found for requestId: ' . $requestId);
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
