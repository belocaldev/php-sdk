<?php

declare(strict_types=1);

namespace BeLocal;

class TranslateManyResult
{
    /** @var array<string>|null */
    private $texts;

    /** @var bool */
    private $ok;

    /** @var BeLocalError|null */
    private $error;

    /** @var int|null */
    private $httpCode;

    /** @var int|null */
    private $curlErrno;

    /** @var string|null */
    private $raw;

    /**
     * @param array<string>|null $texts
     * @param bool            $ok
     * @param BeLocalError|null $error
     * @param int|null        $httpCode
     * @param int|null        $curlErrno
     * @param string|null     $raw
     */
    public function __construct($texts, bool $ok, BeLocalError $error = null, $httpCode = null, $curlErrno = null, $raw = null)
    {
        $this->texts = $texts;
        $this->ok = $ok;
        $this->error = $error;
        $this->httpCode = $httpCode;
        $this->curlErrno = $curlErrno;
        $this->raw = $raw;
    }

    /** @return array<string>|null */
    public function getTexts()
    {
        return $this->texts;
    }

    /** @return bool */
    public function isOk()
    {
        return $this->ok;
    }

    /** @return BeLocalError|null */
    public function getError()
    {
        return $this->error;
    }

    /** @return int|null */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /** @return int|null */
    public function getCurlErrno()
    {
        return $this->curlErrno;
    }

    /** @return string|null */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * Creates a TranslateManyResult from a TranslateResponse and requestIds
     *
     * @param array $requestIds
     * @param TranslateResponse $response
     * @return self
     */
    public static function fromResponseAndRequestIds(array $requestIds, TranslateResponse $response): self
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

        return new self(
            $texts,
            $isOk,
            $response->getError(),
            $response->getHttpCode(),
            $response->getCurlErrno(),
            $response->getRaw()
        );
    }
}
