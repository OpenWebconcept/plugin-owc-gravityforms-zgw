<?php
/**
 * @license MIT
 *
 * Modified by plugin on 06-January-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace OWCGravityFormsZGW\Vendor_Prefixed\DI\Definition\Source;

use OWCGravityFormsZGW\Vendor_Prefixed\DI\Definition\Definition;
use OWCGravityFormsZGW\Vendor_Prefixed\DI\Definition\Exception\InvalidDefinition;

/**
 * Source of definitions for entries of the container.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface DefinitionSource
{
    /**
     * Returns the DI definition for the entry name.
     *
     * @throws InvalidDefinition An invalid definition was found.
     */
    public function getDefinition(string $name) : ?Definition;

    /**
     * @return array<string,Definition> Definitions indexed by their name.
     */
    public function getDefinitions() : array;
}
