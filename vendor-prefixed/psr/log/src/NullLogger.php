<?php
/**
 * @license MIT
 *
 * Modified by plugin on 17-October-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace OWCGravityFormsZGW\Vendor_Prefixed\Psr\Log;

/**
 * This Logger can be used to avoid conditional log calls.
 *
 * Logging should always be optional, and if no logger is provided to your
 * library creating a NullLogger instance to have something to throw logs at
 * is a good way to avoid littering your code with `if ($this->logger) { }`
 * blocks.
 */
class NullLogger extends AbstractLogger
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed[] $context
     *
     * @throws \OWCGravityFormsZGW\Vendor_Prefixed\Psr\Log\InvalidArgumentException
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // noop
    }
}
