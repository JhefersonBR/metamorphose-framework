<?php

namespace Metamorphose\CLI;

/**
 * Interface para comandos CLI
 */
interface CommandInterface
{
    public function name(): string;
    
    public function description(): string;
    
    public function handle(array $args): int;
}

