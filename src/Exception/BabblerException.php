<?php

declare(strict_types=1);

namespace NestboxPHP\Nestbox\Babbler\Exception;

use NestboxPHP\Nestbox\Exception;

class BabblerException extends NestboxException
{
    public function __construct(string $message = "", int $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}