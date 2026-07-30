<?php

declare(strict_types=1);

/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2026, Peter Gribanov
 * @license   https://gnu.org GPL-3.0-or-later
 */

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://gnu.org>.
 */

namespace AnimeDb\PluginContracts\PHPStan\Rules;

use AnimeDb\PluginContracts\CatalogWidgetInterface;
use AnimeDb\PluginContracts\DownloadCandidateSearchInterface;
use AnimeDb\PluginContracts\EntryWidgetInterface;
use AnimeDb\PluginContracts\ExternalIdResolutionInterface;
use AnimeDb\PluginContracts\FillerInterface;
use AnimeDb\PluginContracts\SearchByPluginInterface;
use AnimeDb\PluginContracts\SyncInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * Verifies that classes declaring implementation of one of the plugin
 * contract interfaces actually have up-to-date method signatures matching
 * the installed version of the contracts package.
 *
 * PHP itself already refuses to declare a class whose method signatures are
 * not compatible (contravariant/covariant) with the interfaces it
 * implements, but it still allows signatures that are technically legal
 * under variance rules yet drifted away from the contract as written (e.g.
 * a widened parameter type or a narrowed return type). This rule reports
 * that drift explicitly instead of silently accepting it.
 *
 * @implements Rule<InClassNode>
 */
final class ContractConformanceRule implements Rule
{
    private const ERROR_IDENTIFIER = 'animedb.contractConformance';

    /**
     * Role interfaces of the plugin contract. Every one of them, directly
     * or through inheritance (e.g. FillerInterface extends
     * SearchByPluginInterface extends ExternalIdResolutionInterface), is checked.
     *
     * @var string[]
     */
    private const CONTRACT_INTERFACES = [
        ExternalIdResolutionInterface::class,
        FillerInterface::class,
        SearchByPluginInterface::class,
        SyncInterface::class,
        CatalogWidgetInterface::class,
        EntryWidgetInterface::class,
        DownloadCandidateSearchInterface::class,
    ];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if ($classReflection->isInterface() || $classReflection->isAbstract()) {
            return [];
        }

        $errors = [];
        $checkedMethods = [];

        foreach (self::CONTRACT_INTERFACES as $interfaceName) {
            if (!$this->reflectionProvider->hasClass($interfaceName)) {
                continue;
            }

            if (!$classReflection->implementsInterface($interfaceName)) {
                continue;
            }

            $interfaceReflection = $this->reflectionProvider->getClass($interfaceName);

            foreach ($interfaceReflection->getNativeReflection()->getMethods() as $interfaceMethod) {
                $methodName = $interfaceMethod->getName();

                // A method inherited through more than one role interface
                // (e.g. resolveExternalId() from ExternalIdResolutionInterface,
                // reached both directly and through FillerInterface) is
                // checked once.
                if (isset($checkedMethods[$methodName])) {
                    continue;
                }
                $checkedMethods[$methodName] = true;

                $errors = array_merge(
                    $errors,
                    $this->checkMethod($classReflection, $interfaceReflection, $methodName),
                );
            }
        }

        return $errors;
    }

    /**
     * @return list<\PHPStan\Rules\RuleError>
     */
    private function checkMethod(
        ClassReflection $classReflection,
        ClassReflection $interfaceReflection,
        string $methodName,
    ): array {
        if (!$classReflection->hasNativeMethod($methodName)) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Class %s implements %s but does not implement its method %s(), required by the installed version of anime-db/plugin-contracts.',
                    $classReflection->getName(),
                    $interfaceReflection->getName(),
                    $methodName,
                ))->identifier(self::ERROR_IDENTIFIER)->build(),
            ];
        }

        $classMethod = $classReflection->getNativeMethod($methodName);

        $interfaceVariant = ParametersAcceptorSelector::selectSingle(
            $interfaceReflection->getNativeMethod($methodName)->getVariants(),
        );
        $classVariant = ParametersAcceptorSelector::selectSingle($classMethod->getVariants());

        $expected = $this->describeSignature($methodName, $interfaceVariant);
        $actual = $this->describeSignature($methodName, $classVariant);

        if ($expected === $actual) {
            return [];
        }

        $line = $classReflection->getNativeReflection()->getMethod($methodName)->getStartLine();

        $errorBuilder = RuleErrorBuilder::message(sprintf(
            'Method %s::%s() does not match the contract declared by %s for the installed version of anime-db/plugin-contracts: expected `%s`, got `%s`.',
            $classReflection->getName(),
            $methodName,
            $interfaceReflection->getName(),
            $expected,
            $actual,
        ))->identifier(self::ERROR_IDENTIFIER);

        if ($line !== false) {
            $errorBuilder->line($line);
        }

        return [$errorBuilder->build()];
    }

    private function describeSignature(string $methodName, \PHPStan\Reflection\ParametersAcceptor $variant): string
    {
        // Parameter names are intentionally excluded: PHP does not require them to
        // match an interface for a valid implementation, so a renamed parameter must
        // not be reported as a contract violation.
        $params = [];
        foreach ($variant->getParameters() as $parameter) {
            $type = $parameter->getType()->describe(VerbosityLevel::typeOnly());
            $prefix = $parameter->isVariadic() ? '...' : '';
            $suffix = $parameter->isOptional() ? ' = default' : '';
            $params[] = sprintf('%s%s%s', $prefix, $type, $suffix);
        }

        return sprintf(
            '%s(%s): %s',
            $methodName,
            implode(', ', $params),
            $variant->getReturnType()->describe(VerbosityLevel::typeOnly()),
        );
    }
}
