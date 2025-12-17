<?php

namespace Metamorphose\Kernel\Log;

use Monolog\Logger as MonologLogger;
use Psr\Log\LogLevel;

/**
 * Adapter para Monolog Logger
 * 
 * Adapta Monolog\Logger para LoggerInterface do framework.
 */
class MonologLoggerAdapter extends MonologLogger implements LoggerInterface
{
    public function __construct(MonologLogger $logger)
    {
        parent::__construct($logger->getName());
        
        foreach ($logger->getHandlers() as $handler) {
            $this->pushHandler($handler);
        }
        
        foreach ($logger->getProcessors() as $processor) {
            $this->pushProcessor($processor);
        }
    }
}

