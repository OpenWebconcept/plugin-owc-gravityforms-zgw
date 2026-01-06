<?php
/**
 * @license MIT
 *
 * Modified by plugin on 06-January-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace OWCGravityFormsZGW\Vendor_Prefixed\DI\Invoker;

use OWCGravityFormsZGW\Vendor_Prefixed\Invoker\ParameterResolver\ParameterResolver;
use OWCGravityFormsZGW\Vendor_Prefixed\Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;
use ReflectionNamedType;

/**
 * Inject the container, the definition or any other service using type-hints.
 *
 * {@internal This class is similar to TypeHintingResolver and TypeHintingContainerResolver,
 *            we use this instead for performance reasons}
 *
 * @author Quim Calpe <quim@kalpe.com>
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class FactoryParameterResolver implements ParameterResolver
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters,
    ) : array {
        $parameters = $reflection->getParameters();

        // Skip parameters already resolved
        if (! empty($resolvedParameters)) {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            $parameterType = $parameter->getType();
            if (!$parameterType) {
                // No type
                continue;
            }
            if (!$parameterType instanceof ReflectionNamedType) {
                // Union types are not supported
                continue;
            }
            if ($parameterType->isBuiltin()) {
                // Primitive types are not supported
                continue;
            }

            $parameterClass = $parameterType->getName();

            if ($parameterClass === 'OWCGravityFormsZGW\Vendor_Prefixed\Psr\Container\ContainerInterface') {
                $resolvedParameters[$index] = $this->container;
            } elseif ($parameterClass === 'OWCGravityFormsZGW\Vendor_Prefixed\DI\Factory\RequestedEntry') {
                // By convention the second parameter is the definition
                $resolvedParameters[$index] = $providedParameters[1];
            } elseif ($this->container->has($parameterClass)) {
                $resolvedParameters[$index] = $this->container->get($parameterClass);
            }
        }

        return $resolvedParameters;
    }
}
