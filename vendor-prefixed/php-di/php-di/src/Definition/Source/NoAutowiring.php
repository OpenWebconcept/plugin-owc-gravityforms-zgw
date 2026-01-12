<?php
/**
 * @license MIT
 *
 * Modified by plugin on 12-January-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace OWCGravityFormsZGW\Vendor_Prefixed\DI\Definition\Source;

use OWCGravityFormsZGW\Vendor_Prefixed\DI\Definition\Exception\InvalidDefinition;
use OWCGravityFormsZGW\Vendor_Prefixed\DI\Definition\ObjectDefinition;

/**
 * Implementation used when autowiring is completely disabled.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class NoAutowiring implements Autowiring
{
    public function autowire(string $name, ?ObjectDefinition $definition = null) : ?ObjectDefinition
    {
        throw new InvalidDefinition(sprintf(
            'Cannot autowire entry "%s" because autowiring is disabled',
            $name
        ));
    }
}
