<?php
declare(strict_types=1);

namespace Tivins\PhpCore\Exception;

use RuntimeException;
use Throwable;

class MkDirException extends RuntimeException
{
    public function __construct(
        public readonly string $dir,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}