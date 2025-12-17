<?php

namespace Metamorphose\Kernel\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Logger nulo que descarta todas as mensagens
 * 
 * Usado quando logs estão desabilitados via configuração.
 */
class NullLogger extends AbstractLogger implements LoggerInterface
{
    public function log($level, $message, array $context = []): void
    {
        // Descarta todas as mensagens
    }
}

