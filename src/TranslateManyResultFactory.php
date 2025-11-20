<?php

declare(strict_types=1);

namespace BeLocal;

/**
 * Factory for creating TranslateManyResult instances from API responses
 */
class TranslateManyResultFactory
{
    /**
     * Creates a TranslateManyResult from a TranslateResponse and requestIds
     *
     * @param array $requestIds
     * @param TranslateResponse $response
     * @return TranslateManyResult
     */
    public static function fromResponseAndRequestIds(array $requestIds, TranslateResponse $response): TranslateManyResult
    {
        $responseBody = $response->getResponseBody();
        $texts = [];

        $isOk = $response->isOk();

        if (is_array($responseBody) && isset($responseBody['results']) && is_array($responseBody['results'])) {
            $resultMap = [];
            foreach ($responseBody['results'] as $result) {
                if (
                    isset($result['requestId'])
                    && isset($result['data']['text'])
                    && isset($result['data']['status'])
                    && $result['data']['status'] !== 'error'
                ) {
                    $resultMap[$result['requestId']] = $result['data']['text'];
                }

                if (isset($result['data']['status']) && $result['data']['status'] === 'error') {
                    $isOk = false;
                }
            }

            foreach ($requestIds as $requestId) {
                $texts[] = $resultMap[$requestId] ?? null;
            }
        }

        return new TranslateManyResult(
            $texts,
            $isOk,
            $response->getError(),
            $response->getHttpCode(),
            $response->getCurlErrno(),
            $response->getRaw()
        );
    }

    /**
     * Creates a map of requestId => TranslateManyResult from a multi-response
     * Parses response format with data.texts (array) instead of data.text (string)
     *
     * @param TranslateResponse $response
     * @return array<string, TranslateManyResult> Map of requestId => TranslateManyResult
     */
    public static function fromMultiResponse(TranslateResponse $response): array
    {
        $responseBody = $response->getResponseBody();
        $resultMap = [];

        if ($response->isOk() && is_array($responseBody) && isset($responseBody['results']) && is_array($responseBody['results'])) {
            foreach ($responseBody['results'] as $result) {
                if (isset($result['requestId']) && isset($result['data'])) {
                    $resultRequestId = $result['requestId'];
                    $data = $result['data'];

                    $texts = null;
                    $isOk = true;

                    if (isset($data['texts']) && is_array($data['texts'])) {
                        $texts = $data['texts'];
                    }

                    if (isset($data['status']) && $data['status'] === 'error') {
                        $isOk = false;
                    }

                    $resultMap[$resultRequestId] = new TranslateManyResult(
                        $texts,
                        $isOk,
                        null,
                        $response->getHttpCode(),
                        $response->getCurlErrno(),
                        $response->getRaw()
                    );
                }
            }
        }

        return $resultMap;
    }
}

