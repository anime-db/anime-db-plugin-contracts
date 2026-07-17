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
            return $this->processFuncCall($node);
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
    private function processFuncCall(Node\Expr\FuncCall $node): array
    {
        if (!$node->name instanceof Node\Name) {
            return [
                RuleErrorBuilder::message(
                    'Dynamic function calls through a variable are forbidden, use the abstraction provided by the host application instead.',
                )->identifier(self::ERROR_IDENTIFIER)->build(),
            ];
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
}
