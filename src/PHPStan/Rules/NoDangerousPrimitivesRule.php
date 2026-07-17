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

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\CallableType;
use PHPStan\Type\Type;

/**
 * Forbids plugins from directly calling dangerous low-level primitives
 * (process execution, raw curl, eval, dynamic calls through variables) that
 * bypass the abstractions provided by the host application.
 *
 * This is not a defense against a deliberately obfuscated bypass: it raises
 * the bar for the typical case and acts as an automated gate before a
 * plugin is accepted into the registry.
 *
 * @implements Rule<Node\Expr>
 */
final class NoDangerousPrimitivesRule implements Rule
{
    private const ERROR_IDENTIFIER = 'animedb.dangerousPrimitive';

    /**
     * Function names forbidden regardless of arguments.
     *
     * @var string[]
     */
    private const FORBIDDEN_FUNCTIONS = [
        'exec',
        'shell_exec',
        'passthru',
        'system',
        'popen',
        'proc_open',
        'proc_close',
        'pcntl_exec',
        'file_get_contents',
    ];

    /**
     * Function name prefixes forbidden regardless of arguments.
     *
     * @var string[]
     */
    private const FORBIDDEN_FUNCTION_PREFIXES = [
        'curl_',
    ];

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    /**
     * @param Node\Expr $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof Node\Expr\FuncCall) {
            return $this->processFuncCall($node, $scope);
        }

        if ($node instanceof Node\Expr\Eval_) {
            return [
                RuleErrorBuilder::message(
                    'Using eval() is forbidden, use the abstraction provided by the host application instead.',
                )->identifier(self::ERROR_IDENTIFIER)->build(),
            ];
        }

        if ($node instanceof Node\Expr\ShellExec) {
            return [
                RuleErrorBuilder::message(
                    'Using the shell exec operator (backticks) is forbidden, use the abstraction provided by the host application instead.',
                )->identifier(self::ERROR_IDENTIFIER)->build(),
            ];
        }

        if ($node instanceof Node\Expr\Variable && $node->name instanceof Node\Expr) {
            return [
                RuleErrorBuilder::message(
                    'Using variable variables is forbidden, use the abstraction provided by the host application instead.',
                )->identifier(self::ERROR_IDENTIFIER)->build(),
            ];
        }

        return [];
    }

    /**
     * @return list<\PHPStan\Rules\RuleError>
     */
    private function processFuncCall(Node\Expr\FuncCall $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Name) {
            // A callee statically typed as `callable` (e.g. SearchByPluginInterface::find()'s
            // $onHeartbeat) or as a Closure/invokable object is a legitimate, contract-sanctioned
            // callback invocation, not a dynamic dispatch to an attacker-controlled function name
            // built at runtime (e.g. `$fn = 'exec'; $fn();`, where the type is a plain string).
            if (!$this->isSafeCallableReference($scope->getType($node->name))) {
                return [
                    RuleErrorBuilder::message(
                        'Dynamic function calls through a variable are forbidden, use the abstraction provided by the host application instead.',
                    )->identifier(self::ERROR_IDENTIFIER)->build(),
                ];
            }

            return [];
        }

        $functionName = strtolower($node->name->toString());

        if (in_array($functionName, self::FORBIDDEN_FUNCTIONS, true)) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Calling %s() directly is forbidden, use the abstraction provided by the host application instead.',
                    $functionName,
                ))->identifier(self::ERROR_IDENTIFIER)->build(),
            ];
        }

        foreach (self::FORBIDDEN_FUNCTION_PREFIXES as $prefix) {
            if (str_starts_with($functionName, $prefix)) {
                return [
                    RuleErrorBuilder::message(sprintf(
                        'Calling %s() directly is forbidden, use the abstraction provided by the host application instead.',
                        $functionName,
                    ))->identifier(self::ERROR_IDENTIFIER)->build(),
                ];
            }
        }

        return [];
    }

    /**
     * A string is technically callable too (PHP resolves it to a function by name at
     * call time), so `isCallable()` alone can't tell a dynamic-name dispatch apart from
     * a genuine callback. Only the `callable` pseudo-type and Closure/invokable objects
     * are treated as safe; strings (including constant ones) are always forbidden.
     */
    private function isSafeCallableReference(Type $type): bool
    {
        if ($type instanceof CallableType) {
            return true;
        }

        return $type->isObject()->yes() && $type->isCallable()->yes();
    }
}
