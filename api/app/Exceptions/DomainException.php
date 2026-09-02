<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Base for business-rule failures. Each subclass names the HTTP status the
 * API should return; bootstrap/app.php renders them to a JSON envelope.
 */
abstract class DomainException extends RuntimeException
{
    abstract public function status(): int;
}
