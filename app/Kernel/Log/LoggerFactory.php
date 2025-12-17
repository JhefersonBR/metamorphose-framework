<?php

namespace Metamorphose\Kernel\Log;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;
use Monolog\Level;

/**
 * Factory para criar instâncias de Logger
 * 
 * Decide se usa logger real ou NullLogger baseado na configuração.
 */
class LoggerFactory
{
    private array $config;
    private ?LoggerInterface $logger = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function create(): LoggerInterface
    {
        if ($this->logger !== null) {
            return $this->logger;
        }

        if (!$this->config['enabled']) {
            $this->logger = new NullLogger();
            return $this->logger;
        }

        $logger = new MonologLogger($this->config['channel']);
        
        $level = $this->mapLevel($this->config['level']);
        
        if ($this->config['enabled']) {
            $logPath = $this->config['path'];
            
            if (!is_dir($logPath)) {
                @mkdir($logPath, 0755, true);
            }
            
            $handler = new StreamHandler(
                $logPath . '/' . date('Y-m-d') . '.log',
                $level
            );
            
            $logger->pushHandler($handler);
        } else {
            $logger->pushHandler(new NullHandler());
        }

        $this->logger = new MonologLoggerAdapter($logger);
        return $this->logger;
    }

    private function mapLevel(string $level): Level
    {
        return match (strtolower($level)) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Info,
        };
    }
}

