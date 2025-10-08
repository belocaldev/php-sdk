<?php

declare(strict_types=1);

namespace BeLocal;

final class BeLocalError
{
    /** @var string */
    private $code;

    /** @var string */
    private $message;

    /**
     * @param string $code
     * @param string $message
     */
    public function __construct($code, $message)
    {
        $this->code = (string)$code;
        $this->message = (string)$message;
    }

    /** @return string */
    public function getCode()
    {
        return $this->code;
    }

    /** @return string */
    public function getMessage()
    {
        return $this->message;
    }
}