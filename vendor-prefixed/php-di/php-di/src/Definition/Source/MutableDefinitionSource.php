<?php
/**
 * @license MIT
 *
 * Modified by plugin on 30-July-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace OWCGravityFormsZGW\Vendor_Prefixed\DI\Definition\Source;

use OWCGravityFormsZGW\Vendor_Prefixed\DI\Definition\Definition;

/**
 * Describes a definition source to which we can add new definitions.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface MutableDefinitionSource extends DefinitionSource
{
    public function addDefinition(Definition $definition) : void;
}
