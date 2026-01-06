<?php
/**
 * @license MIT
 *
 * Modified by plugin on 06-January-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace OWCGravityFormsZGW\Vendor_Prefixed\DI\Definition;

/**
 * A definition that extends a previous definition with the same name.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface ExtendsPreviousDefinition extends Definition
{
    public function setExtendedDefinition(Definition $definition) : void;
}
