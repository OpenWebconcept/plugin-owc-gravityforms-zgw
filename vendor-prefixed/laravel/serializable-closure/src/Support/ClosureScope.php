<?php
/**
 * @license MIT
 *
 * Modified by plugin on 12-January-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace OWCGravityFormsZGW\Vendor_Prefixed\Laravel\SerializableClosure\Support;

use SplObjectStorage;

class ClosureScope extends SplObjectStorage
{
    /**
     * The number of serializations in current scope.
     *
     * @var int
     */
    public $serializations = 0;

    /**
     * The number of closures that have to be serialized.
     *
     * @var int
     */
    public $toSerialize = 0;
}
