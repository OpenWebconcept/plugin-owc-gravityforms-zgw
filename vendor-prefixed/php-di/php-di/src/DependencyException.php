<?php
/**
 * @license MIT
 *
 * Modified by plugin on 12-January-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace OWCGravityFormsZGW\Vendor_Prefixed\DI;

use OWCGravityFormsZGW\Vendor_Prefixed\Psr\Container\ContainerExceptionInterface;

/**
 * Exception for the Container.
 */
class DependencyException extends \Exception implements ContainerExceptionInterface
{
}
