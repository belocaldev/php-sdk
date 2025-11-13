<?php

declare(strict_types=1);

namespace BeLocal;

class BeLocalException extends \RuntimeException
{
    /** @var BeLocalError */
    private $error;

    /** @var int|null */
    private $httpCode;

    /** @var int|null */
    private $curlErrno;

    /**
     * @param BeLocalError $error
     * @param int|null     $httpCode
     * @param int|null     $curlErrno
     */
    public function __construct(BeLocalError $error, $httpCode = null, $curlErrno = null)
    {
        $this->error = $error;
        $this->httpCode = $httpCode;
        $this->curlErrno = $curlErrno;

        parent::__construct($error->getMessage());
    }

    /** @return BeLocalError */
    public function getBeLocalError()
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
}