<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;

class ConfigureStructuredLogger
{
    /**
     * Configure JSON formatting and sensitive-data redaction for all handlers.
     */
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getLogger()->getHandlers() as $handler) {
            $handler->setFormatter(new JsonFormatter());
        }

        $logger->pushProcessor(new SensitiveDataRedactionProcessor());
    }
}
