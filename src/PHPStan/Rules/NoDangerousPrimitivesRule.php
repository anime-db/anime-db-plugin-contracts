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
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Forbids plugins from directly calling dangerous low-level primitives
 * (process execution, raw network sockets, curl, eval, URL-scoped file/include
 * operations, dynamic calls through variables, instantiation of or static
 * calls into known network/process client classes) that bypass the
 * abstractions provided by the host application.
 *
 * This is a denylist, not an allowlist: it enumerates specific functions and
 * classes rather than restricting a plugin to a set of permitted namespaces.
 * A denylist inherently lags behind the host application's dependency tree —
 * a new host dependency that exposes a network/process/filesystem primitive
 * is a potential bypass until this list is extended to cover it. There is no
 * automation that detects this drift; when a dependency is added to (or
 * updated in) the host application, it must be manually reviewed for such
 * primitives and, if found, added to the relevant list below.
 *
 * This is not a defense against a deliberately obfuscated bypass: it raises
 * the bar for the typical case and acts as an automated gate before a
 * plugin is accepted into the registry.
 *
 * What a plugin is allowed to depend on via constructor injection (as
 * opposed to calling directly, which is what this rule checks) is a
 * separate concern this rule does not enforce: the boundary is "interfaces
 * defined by this contract package, or PSR interfaces it re-exports (e.g.
 * `Psr\Http\Client\ClientInterface` for a host-preconfigured HTTP client)"
 * versus "concrete client classes from the host application's dependency
 * tree" (e.g. `Symfony\Component\HttpClient\HttpClient`,
 * `GuzzleHttp\Client`, `Symfony\Component\Process\Process`). Enforcing that
 * boundary requires inspecting constructor/property type declarations, not
 * expression nodes, and auditing how the host application's DI container
 * wires plugin services — both out of scope for this rule and for a
 * contract-only package that does not contain the host application's
 * source.
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
        'simplexml_load_file',
        'getimagesize',
        'file_put_contents',
    ];

    /**
     * Functions that always perform a live DNS query — unlike URL_SCOPED_FUNCTIONS,
     * there is no local/non-network form of these calls to allow, so they are
     * forbidden regardless of arguments (like FORBIDDEN_FUNCTIONS).
     *
     * @var string[]
     */
    private const FORBIDDEN_DNS_FUNCTIONS = [
        'dns_get_record',
        'gethostbyname',
    ];

    /**
     * Classes and interfaces forbidden to instantiate (as themselves, a subclass, or
     * an implementation) regardless of constructor arguments: each is either a network
     * client (bypassing the host's PSR-18 client) or a process launcher (bypassing
     * DownloadServiceInterface / exec()'s ban). Matched by type (see processNew()), not
     * by literal class name, so a subclass (`class MySoap extends SoapClient {}`) or a
     * concrete client implementing one of the listed interfaces
     * (`final class CurlHttpClient implements HttpClientInterface {}`) is caught too —
     * instantiating that concrete client directly is the same bypass as calling the
     * factory in FORBIDDEN_STATIC_CALL_CLASSES below, one line shorter.
     *
     * @var string[]
     */
    private const FORBIDDEN_INSTANTIATIONS = [
        'SoapClient',
        'Symfony\\Component\\Process\\Process',
        'Symfony\\Contracts\\HttpClient\\HttpClientInterface',
        'Psr\\Http\\Client\\ClientInterface',
    ];

    /**
     * Classes forbidden to call a static method on, regardless of which method:
     * each is a factory for a network client that PHPStan can't otherwise tell
     * apart from a legitimate PSR-18 client obtained through DI. Matched by type
     * (see processStaticCall()), so a subclass of one of these factories is caught
     * too, not just the exact class.
     *
     * @var string[]
     */
    private const FORBIDDEN_STATIC_CALL_CLASSES = [
        'Symfony\\Component\\HttpClient\\HttpClient',
        'Http\\Discovery\\Psr18ClientDiscovery',
        'Http\\Discovery\\HttpClientDiscovery',
    ];

    /**
     * Method calls forbidden when the first argument is a statically known URL,
     * keyed by the class the receiver must be an instance of. Same rationale as
     * URL_SCOPED_FUNCTIONS, extended to instance methods that don't go through a
     * global function.
     *
     * @var array<string, string[]>
     */
    private const URL_SCOPED_METHODS = [
        \DOMDocument::class => ['load', 'loadHTMLFile'],
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

        if ($node instanceof Node\Expr\New_) {
            return $this->processNew($node, $scope);
        }

        if ($node instanceof Node\Expr\StaticCall) {
            return $this->processStaticCall($node, $scope);
        }

        if ($node instanceof Node\Expr\MethodCall) {
            return $this->processMethodCall($node, $scope);
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

        if (
            in_array($functionName, self::FORBIDDEN_FUNCTIONS, true)
            || in_array($functionName, self::FORBIDDEN_DNS_FUNCTIONS, true)
        ) {
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
     * @return list<\PHPStan\Rules\RuleError>
     */
    private function processNew(Node\Expr\New_ $node, Scope $scope): array
    {
        if (!$node->class instanceof Node\Name) {
            // Anonymous class or a dynamic class expression (`new $class()`) — not one
            // of the specific known-dangerous classes this list enumerates.
            return [];
        }

        $className = ltrim($node->class->toString(), '\\');
        $instantiatedType = $scope->resolveTypeByName($node->class);

        foreach (self::FORBIDDEN_INSTANTIATIONS as $forbiddenType) {
            if ((new ObjectType($forbiddenType))->isSuperTypeOf($instantiatedType)->yes()) {
                return [
                    RuleErrorBuilder::message(sprintf(
                        'Instantiating %s is forbidden, use the abstraction provided by the host application instead.',
                        $className,
                    ))->identifier(self::ERROR_IDENTIFIER)->build(),
                ];
            }
        }

        return [];
    }

    /**
     * @return list<\PHPStan\Rules\RuleError>
     */
    private function processStaticCall(Node\Expr\StaticCall $node, Scope $scope): array
    {
        if (!$node->class instanceof Node\Name) {
            return [];
        }

        $className = ltrim($node->class->toString(), '\\');
        $classType = $scope->resolveTypeByName($node->class);

        foreach (self::FORBIDDEN_STATIC_CALL_CLASSES as $forbiddenClass) {
            if ((new ObjectType($forbiddenClass))->isSuperTypeOf($classType)->yes()) {
                $methodName = $node->name instanceof Node\Identifier ? $node->name->toString() : '{expr}';

                return [
                    RuleErrorBuilder::message(sprintf(
                        'Calling %s::%s() is forbidden, use the abstraction provided by the host application instead.',
                        $className,
                        $methodName,
                    ))->identifier(self::ERROR_IDENTIFIER)->build(),
                ];
            }
        }

        return [];
    }

    /**
     * @return list<\PHPStan\Rules\RuleError>
     */
    private function processMethodCall(Node\Expr\MethodCall $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        $methodName = $node->name->toString();

        foreach (self::URL_SCOPED_METHODS as $className => $methods) {
            if (!in_array($methodName, $methods, true)) {
                continue;
            }

            if (!(new ObjectType($className))->isSuperTypeOf($scope->getType($node->var))->yes()) {
                continue;
            }

            $args = $node->getArgs();
            if ($args !== [] && $this->isUrlExpression($args[0]->value, $scope)) {
                return [
                    RuleErrorBuilder::message(sprintf(
                        'Calling %s::%s() with a URL is forbidden, use the abstraction provided by the host application instead.',
                        $className,
                        $methodName,
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
     * A statically known URL scheme (e.g. `https://`) counts as a URL, whether the
     * whole expression is a constant string or only its leftmost part is — PHP
     * concatenation is left-associative, so `'https://api.example.com/' . $id` has
     * the literal scheme as the left operand of the outermost Concat even though
     * the expression as a whole isn't a constant string. Recursing into the left
     * operand catches that common "literal scheme, dynamic path" pattern.
     *
     * An expression with no literal scheme anywhere in it (e.g. a bare
     * `$this->endpoint`) is deliberately left alone: telling apart "this variable
     * holds a URL" from "this variable holds a local path" for an arbitrary
     * dynamic expression needs value/taint tracking this rule doesn't do, and
     * guessing from a variable or property name would be an unreliable heuristic
     * that produces both false positives (a local path named `$url`) and false
     * negatives (a URL held in a variable not named suggestively).
     */
    private function isUrlExpression(Node\Expr $expr, Scope $scope): bool
    {
        $type = $scope->getType($expr);

        foreach ($type->getConstantStrings() as $constantString) {
            if (preg_match(self::URL_SCHEME_PATTERN, $constantString->getValue()) === 1) {
                return true;
            }
        }

        if ($expr instanceof Node\Expr\BinaryOp\Concat) {
            return $this->isUrlExpression($expr->left, $scope);
        }

        return false;
    }
}
