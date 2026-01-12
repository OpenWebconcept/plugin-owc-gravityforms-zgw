<?php
/**
 * @license MIT
 *
 * Modified by plugin on 12-January-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace OWCGravityFormsZGW\Vendor_Prefixed\DI\Definition\Helper;

use OWCGravityFormsZGW\Vendor_Prefixed\DI\Definition\Definition;

/**
 * Helps defining container entries.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface DefinitionHelper
{
    /**
     * @param string $entryName Container entry name
     */
    public function getDefinition(string $entryName) : Definition;
}
