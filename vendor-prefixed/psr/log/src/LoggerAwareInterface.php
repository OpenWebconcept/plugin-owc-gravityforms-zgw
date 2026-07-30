<?php
/**
 * @license MIT
 *
 * Modified by plugin on 30-July-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace OWCGravityFormsZGW\Vendor_Prefixed\Psr\Log;

/**
 * Describes a logger-aware instance.
 */
interface LoggerAwareInterface
{
    /**
     * Sets a logger instance on the object.
     */
    public function setLogger(LoggerInterface $logger): void;
}
