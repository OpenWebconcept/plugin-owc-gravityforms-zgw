<?php
/**
 * @license MIT
 *
 * Modified by plugin on 17-October-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OWCGravityFormsZGW\Vendor_Prefixed\Monolog\Handler;

use OWCGravityFormsZGW\Vendor_Prefixed\Monolog\Formatter\FormatterInterface;

/**
 * Interface to describe loggers that have a formatter
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
interface FormattableHandlerInterface
{
    /**
     * Sets the formatter.
     *
     * @return HandlerInterface self
     */
    public function setFormatter(FormatterInterface $formatter): HandlerInterface;

    /**
     * Gets the formatter.
     */
    public function getFormatter(): FormatterInterface;
}
