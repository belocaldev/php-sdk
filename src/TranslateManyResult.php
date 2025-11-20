<?php

declare(strict_types=1);

namespace BeLocal;

class TranslateManyResult
{
    /** @var array<string>|null */
    private ?array $texts;

    /** @var bool */
    private bool $ok;

    /** @var BeLocalError|null */
    private ?BeLocalError $error;

    /** @var int|null */
    private ?int $httpCode;

    /** @var int|null */
    private ?int $curlErrno;

    /** @var string|null */
    private ?string $raw;

    /**
     * @param array<string>|null $texts
     * @param bool            $ok
     * @param BeLocalError|null $error
     * @param int|null        $httpCode
     * @param int|null        $curlErrno
     * @param string|null     $raw
     */
    public function __construct($texts, bool $ok, ?BeLocalError $error = null, $httpCode = null, $curlErrno = null, $raw = null)
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

}
