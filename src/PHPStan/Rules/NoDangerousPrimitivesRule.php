<?php

/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2026, Peter Gribanov
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AnimeDb\PluginContracts\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\CallableType;
use PHPStan\Type\Type;

/**
 * Forbids plugins from directly calling dangerous low-level primitives
 * (process execution, raw network sockets, curl, eval, URL-scoped file/include
 * operations, dynamic calls through variables) that bypass the abstractions
 * provided by the host application.
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
        'fsockopen',
        'pfsockopen',
        'stream_socket_client',
        'stream_socket_server',
    ];

    /**
     * Function name prefixes forbidden regardless of arguments.
     *
     * @var string[]
     */
    private const FORBIDDEN_FUNCTION_PREFIXES = [
        'curl_',
    ];

    /**
     * Functions whose first argument is a filename that PHP's stream wrapper layer
     * also lets be a URL (`https://`, `ftp://`, …), silently turning a local file
     * operation into a network request. Forbidden only when that argument is
     * statically known to carry a URL scheme; a local path is a legitimate read.
     *
     * @var string[]
     */
    private const URL_SCOPED_FUNCTIONS = [
        'file_get_contents',
        'fopen',
        'copy',
        'file',
        'readfile',
    ];

    /**
     * Matches the scheme of a PHP stream wrapper (e.g. `http://`, `https://`, `ftp://`),
     * used to tell a remote read/include apart from a local file access.
     */
    private const URL_SCHEME_PATTERN = '/^[a-z][a-z0-9+.\-]*:\/\//i';

    /**
     * @var array<int, string>
     */
    private const INCLUDE_TYPE_KEYWORDS = [
        Node\Expr\Include_::TYPE_INCLUDE => 'include',
        Node\Expr\Include_::TYPE_INCLUDE_ONCE => 'include_once',
        Node\Expr\Include_::TYPE_REQUIRE => 'require',
        Node\Expr\Include_::TYPE_REQUIRE_ONCE => 'require_once',
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

        if ($node instanceof Node\Expr\Include_ && $this->isUrlExpression($node->expr, $scope)) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Using %s with a URL is forbidden, use the abstraction provided by the host application instead.',
                    self::INCLUDE_TYPE_KEYWORDS[$node->type],
                ))->identifier(self::ERROR_IDENTIFIER)->build(),
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

        if (in_array($functionName, self::URL_SCOPED_FUNCTIONS, true)) {
            $args = $node->getArgs();
            if ($args !== [] && $this->isUrlExpression($args[0]->value, $scope)) {
                return [
                    RuleErrorBuilder::message(sprintf(
                        'Calling %s() with a URL is forbidden, use the abstraction provided by the host application instead.',
                        $functionName,
                    ))->identifier(self::ERROR_IDENTIFIER)->build(),
                ];
            }
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
     * a genuine callback. The `callable` pseudo-type, Closure/invokable objects, and
     * array callables (`[$this, 'method']`, `[Foo::class, 'method']`) are treated as
     * safe; strings (including constant ones) are always forbidden.
     */
    private function isSafeCallableReference(Type $type): bool
    {
        if ($type instanceof CallableType) {
            return true;
        }

        if ($type->isObject()->yes() && $type->isCallable()->yes()) {
            return true;
        }

        return $type->isArray()->yes() && $type->isCallable()->yes();
    }

    /**
     * Only a statically known URL scheme (e.g. `https://`) counts as a URL; a plain
     * local path, or an expression whose value can't be determined statically, is left
     * alone since the rule only targets remote reads, not local file access.
     */
    private function isUrlExpression(Node\Expr $expr, Scope $scope): bool
    {
        $type = $scope->getType($expr);

        foreach ($type->getConstantStrings() as $constantString) {
            if (preg_match(self::URL_SCHEME_PATTERN, $constantString->getValue()) === 1) {
                return true;
            }
        }

        return false;
    }
}
