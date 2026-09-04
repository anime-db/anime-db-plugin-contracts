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

use AnimeDb\PluginContracts\CandidateSearch\DownloadCandidateSearchInterface;
use AnimeDb\PluginContracts\Download\DownloadServiceInterface;
use AnimeDb\PluginContracts\Filler\FillerInterface;
use AnimeDb\PluginContracts\Manifest\InvalidManifestException;
use AnimeDb\PluginContracts\Manifest\InvalidManifestJsonException;
use AnimeDb\PluginContracts\Manifest\ManifestParser;
use AnimeDb\PluginContracts\Manifest\PluginType;
use AnimeDb\PluginContracts\Search\SearchByPluginInterface;
use AnimeDb\PluginContracts\Sync\SyncInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Forbids a class belonging to a plugin declared as `type: "local"` in its `manifest.json`
 * from declaring a constructor parameter or a property typed as a PSR-18 HTTP client, a
 * PSR-17 request factory, or one of this package's own contract interfaces whose
 * implementation talks to an external source by definition. `type: "local"` means the
 * plugin's code never talks to an external source — neither directly (covered by
 * {@see NoDangerousPrimitivesRule}, which does not know about plugin types) nor through a
 * host-provided abstraction, which is what this rule checks instead.
 *
 * ## Network types checked
 *
 * - `Psr\Http\Client\ClientInterface` — the PSR-18 transport a host-preconfigured HTTP
 *   client implements; sending a request through it *is* a network call.
 * - `Psr\Http\Message\RequestFactoryInterface` — builds a request object whose only
 *   purpose is to be handed to a `ClientInterface`; declaring it makes sense only as a step
 *   toward a network call. `Psr\Http\Message\StreamFactoryInterface`, the third PSR-17
 *   factory {@see \AnimeDb\PluginContracts\OAuth\AbstractOAuthClient} depends on, is
 *   deliberately not included here: it builds an in-memory stream, equally useful for local
 *   data, and carries no network-specific meaning by itself.
 * - {@see SearchByPluginInterface} — `find()` is defined as searching/matching a title
 *   against an external source while scanning.
 * - {@see FillerInterface} (extends `SearchByPluginInterface`) — `findById()` fetches full
 *   details from that same external source. Listed explicitly, alongside the interface it
 *   extends, so the network verdict for each role interface stays visible in one place
 *   instead of relying on a reader to trace the inheritance chain.
 * - {@see SyncInterface} (extends `FillerInterface`) — `push()`/`pull()` synchronise state
 *   with an external source.
 * - {@see DownloadCandidateSearchInterface} — `search()` is an interactive user search
 *   against an external source, by the same definition as `SearchByPluginInterface`'s
 *   `find()`.
 * - {@see DownloadServiceInterface} — `enqueue()` starts a download from an external
 *   network source (a magnet link or torrent file); this is the package author's own
 *   canonical "definitely network" example.
 *
 * ## Contract types deliberately NOT treated as network
 *
 * - {@see \AnimeDb\PluginContracts\ExternalIdResolutionInterface} — `resolveExternalId()`
 *   only matches against a list of URLs the caller already has; the method itself makes no
 *   network call. (`SearchByPluginInterface` and friends extend it and remain network
 *   through their *other* methods, which is why they are listed above independently rather
 *   than relying on this base interface.)
 * - {@see \AnimeDb\PluginContracts\Widget\CatalogWidgetInterface} and
 *   {@see \AnimeDb\PluginContracts\Widget\EntryWidgetInterface} — `render()` produces
 *   markup from whatever the widget already has; nothing about the interface requires an
 *   external source (a "download status" or "local recommendations" widget needs neither).
 * - {@see \AnimeDb\PluginContracts\Settings\SettingsPageInterface} — `render()` produces
 *   markup for a settings form; the interactive parts that may talk to a vendor (OAuth
 *   start, token exchange) go through the plugin's own routes, not this interface.
 * - {@see \AnimeDb\PluginContracts\Catalog\CatalogReaderInterface} — a read-only projection
 *   of already-merged local catalog state; the package author's own canonical
 *   "definitely not network" example.
 * - {@see \AnimeDb\PluginContracts\PluginData\PluginDataStoreInterface} and
 *   {@see \AnimeDb\PluginContracts\Settings\SettingsStoreInterface} — local read/write
 *   stores keyed by plugin id, no external source involved.
 * - {@see \AnimeDb\PluginContracts\Llm\LlmServiceInterface} — gives access to the host's
 *   *local* LLM. Its implementation happens to go over a PSR-18 client (documented in
 *   README.md), but that client talks to the local model the host runs, not to the
 *   internet; the interface's whole point is to be usable without connectivity, which is
 *   exactly what `type: "local"` promises to its own caller. Declaring `ClientInterface`
 *   directly is still forbidden — only this specific, narrower abstraction is exempt.
 *
 * {@see \AnimeDb\PluginContracts\OAuth\AbstractOAuthClient} is not in either list: it is an
 * abstract *class*, meant to be extended by a plugin's own OAuth client, not declared as a
 * constructor parameter or property type — and `extends` is outside what this rule checks
 * (see "Scope" below). Its own constructor already depends on `ClientInterface` and
 * `RequestFactoryInterface`, both covered above, so a `local` plugin that instantiates one
 * of its own subclasses through DI is still caught there.
 *
 * ## How the plugin type is determined
 *
 * The rule walks up from the analysed file's directory, looking for the nearest
 * `manifest.json` (checking the file's own directory first, then each parent directory in
 * turn). This mirrors how a plugin ships: exactly one `manifest.json` at the root of its
 * ZIP, with the plugin's PHP classes somewhere underneath it. If a nested directory (e.g. a
 * bundled example or a fixture plugin) carries its own `manifest.json` closer to the
 * analysed file than the real plugin root, that nearer file wins — the search stops at the
 * first match, it does not keep looking for a "better" one further up.
 *
 * Two distinct failure modes, handled differently:
 *
 * - **No `manifest.json` found anywhere above the file** — the rule stays silent for that
 *   file. A missing manifest is {@see \AnimeDb\PluginContracts\Manifest\ManifestValidator}'s
 *   problem, not this rule's; it is also the expected state when PHPStan analyses this
 *   contracts package's own source (which ships no plugin manifest) or a plugin's test
 *   fixtures directory that intentionally has none.
 * - **A `manifest.json` was found but could not be trusted to answer "is this plugin
 *   local?"** — unreadable, not valid JSON, failing
 *   {@see \AnimeDb\PluginContracts\Manifest\ManifestValidator} content checks, missing the
 *   `type` field, or carrying a `type` value this installed version of the contracts
 *   package does not recognise (e.g. a value introduced by a newer contract version than
 *   the one analysing the code) — this is always reported as an error, regardless of what
 *   the type would have turned out to be. Silently skipping here would mean a plugin passes
 *   this gate for the wrong reason: "we could not tell what it is", not "it is not local".
 *
 * ## Scope
 *
 * Only constructor parameter and property *type declarations* are checked, not expressions
 * — this is what makes this rule distinct from {@see NoDangerousPrimitivesRule}, which
 * checks call/instantiation expressions and does not know about plugin types at all. A
 * class extending a network-capable base class, or receiving a network type through a
 * method parameter rather than through the constructor or a property, is not checked by
 * this rule.
 *
 * ## Why this rule is enabled by default
 *
 * `type: "local"` in a manifest is the plugin author's own claim that the plugin's code
 * never talks to the network, directly or through a host-provided abstraction. Checking
 * that claim by hand does not scale as a plugin's codebase grows. Enabling this rule by
 * default turns the claim into something verified automatically, from constructor and
 * property type declarations, by whatever PHPStan run a consumer points at the plugin's
 * source tree (see README.md, "PHPStan-правила").
 *
 * @implements Rule<InClassNode>
 */
final class NoNetworkAccessInLocalPluginsRule implements Rule
{
    private const ERROR_IDENTIFIER_NETWORK_ACCESS = 'animedb.localPluginNetworkAccess';
    private const ERROR_IDENTIFIER_MANIFEST = 'animedb.localPluginManifestUnreadable';

    /**
     * @var string[]
     */
    private const NETWORK_TYPES = [
        ClientInterface::class,
        RequestFactoryInterface::class,
        SearchByPluginInterface::class,
        FillerInterface::class,
        SyncInterface::class,
        DownloadCandidateSearchInterface::class,
        DownloadServiceInterface::class,
    ];

    private ManifestParser $manifestParser;

    /**
     * @var array<string, array{0: ?PluginType, 1: ?string}>
     */
    private array $manifestCache = [];

    public function __construct()
    {
        $this->manifestParser = new ManifestParser();
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
        $originalNode = $node->getOriginalNode();
        if (!$originalNode instanceof Node\Stmt\Class_) {
            // Interfaces, traits and enums are not plugin classes a host would
            // instantiate through DI; nothing here declares a checkable dependency.
            return [];
        }

        [$type, $manifestError] = $this->resolvePluginType($scope->getFile());

        if ($manifestError !== null) {
            return [
                RuleErrorBuilder::message($manifestError)->identifier(self::ERROR_IDENTIFIER_MANIFEST)->build(),
            ];
        }

        if ($type !== PluginType::Local) {
            // No manifest.json found above this file, or the plugin is of a type
            // other than "local": this rule has nothing to check either way.
            return [];
        }

        return $this->checkNetworkTypes($originalNode, $node->getClassReflection());
    }

    /**
     * @return array{0: ?PluginType, 1: ?string} [$type, $errorMessage]. Both null means no
     *                                           manifest.json was found above the file — this rule does not
     *                                           apply. A non-null $errorMessage with a null $type means a
     *                                           manifest.json was found but this rule could not trust it to
     *                                           answer whether the plugin is "local".
     */
    private function resolvePluginType(string $filePath): array
    {
        $directory = \dirname($filePath);

        if (isset($this->manifestCache[$directory])) {
            return $this->manifestCache[$directory];
        }

        $manifestPath = $this->findManifestUpward($directory);
        if ($manifestPath === null) {
            return $this->manifestCache[$directory] = [null, null];
        }

        $json = @file_get_contents($manifestPath);
        if ($json === false) {
            return $this->manifestCache[$directory] = [
                null,
                sprintf('%s could not be read.', $manifestPath),
            ];
        }

        try {
            $manifest = $this->manifestParser->parse($json);
        } catch (InvalidManifestJsonException|InvalidManifestException $exception) {
            return $this->manifestCache[$directory] = [
                null,
                sprintf(
                    '%s could not be parsed, so it is unknown whether this plugin is of type "%s": %s',
                    $manifestPath,
                    PluginType::Local->value,
                    $exception->getMessage(),
                ),
            ];
        }

        return $this->manifestCache[$directory] = [$manifest->type, null];
    }

    /**
     * Walks up from $directory looking for the nearest manifest.json, checking $directory
     * itself first. Stops at the first one found — see the class docblock for why a
     * manifest.json belonging to a nested/bundled plugin closer to $directory is the
     * intended match, not a reason to keep looking further up.
     */
    private function findManifestUpward(string $directory): ?string
    {
        $current = $directory;
        $parent = \dirname($current);

        while (true) {
            $candidate = $current.\DIRECTORY_SEPARATOR.'manifest.json';
            if (is_file($candidate)) {
                return $candidate;
            }

            if ($parent === $current) {
                // Reached the filesystem root without finding one.
                return null;
            }

            $current = $parent;
            $parent = \dirname($current);
        }
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function checkNetworkTypes(Node\Stmt\Class_ $classNode, ClassReflection $classReflection): array
    {
        $errors = [];

        $constructorLine = null;
        $paramLines = [];
        $propertyLines = [];

        foreach ($classNode->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === '__construct') {
                $constructorLine = $stmt->getLine();
                foreach ($stmt->params as $param) {
                    if ($param->var instanceof Node\Expr\Variable && \is_string($param->var->name)) {
                        $paramLines[$param->var->name] = $param->getLine();
                    }
                }
            }

            if ($stmt instanceof Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    $propertyLines[$prop->name->toString()] = $stmt->getLine();
                }
            }
        }

        if ($classReflection->hasConstructor()) {
            $constructor = $classReflection->getConstructor();

            // A constructor inherited from a parent class is not declared in this class's
            // own source; it is checked when the file declaring it is analysed instead.
            if ($constructor->getDeclaringClass()->getName() === $classReflection->getName()) {
                // A declared constructor is an ordinary method, never one of PHP's built-in
                // overloaded functions, so it always has exactly one variant.
                $variant = $constructor->getVariants()[0];

                foreach ($variant->getParameters() as $parameter) {
                    if (!$this->typeContainsNetworkType($parameter->getType())) {
                        continue;
                    }

                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'Constructor parameter $%s of %s declares a network-capable type (%s), but the plugin\'s manifest.json declares it as type "%s", which must not access the network even through a host-provided abstraction.',
                        $parameter->getName(),
                        $classReflection->getName(),
                        $parameter->getType()->describe(VerbosityLevel::typeOnly()),
                        PluginType::Local->value,
                    ))->identifier(self::ERROR_IDENTIFIER_NETWORK_ACCESS)
                        ->line($paramLines[$parameter->getName()] ?? $constructorLine ?? $classNode->getLine())
                        ->build();
                }
            }
        }

        foreach ($classReflection->getNativeReflection()->getProperties() as $reflectionProperty) {
            if ($reflectionProperty->getDeclaringClass()->getName() !== $classReflection->getName()) {
                continue;
            }

            if ($reflectionProperty->isPromoted()) {
                // Already checked as a constructor parameter above.
                continue;
            }

            $propertyName = $reflectionProperty->getName();
            if (!$classReflection->hasNativeProperty($propertyName)) {
                continue;
            }

            $propertyReflection = $classReflection->getNativeProperty($propertyName);
            if (!$propertyReflection->hasNativeType()) {
                continue;
            }

            if (!$this->typeContainsNetworkType($propertyReflection->getNativeType())) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Property $%s of %s declares a network-capable type (%s), but the plugin\'s manifest.json declares it as type "%s", which must not access the network even through a host-provided abstraction.',
                $propertyName,
                $classReflection->getName(),
                $propertyReflection->getNativeType()->describe(VerbosityLevel::typeOnly()),
                PluginType::Local->value,
            ))->identifier(self::ERROR_IDENTIFIER_NETWORK_ACCESS)
                ->line($propertyLines[$propertyName] ?? $classNode->getLine())
                ->build();
        }

        return $errors;
    }

    /**
     * A plain isSuperTypeOf() check against a union type (including a nullable type, which
     * PHPStan represents as a union with null) only returns "yes" when every member of the
     * union matches — so `?ClientInterface` would not be caught by checking the union as a
     * whole. Recursing into each member catches "network type OR null" declarations too. An
     * intersection type needs no such recursion: an object satisfying `Foo&ClientInterface`
     * is already a subtype of `ClientInterface` on its own.
     */
    private function typeContainsNetworkType(Type $type): bool
    {
        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $inner) {
                if ($this->typeContainsNetworkType($inner)) {
                    return true;
                }
            }

            return false;
        }

        foreach (self::NETWORK_TYPES as $networkType) {
            if ((new ObjectType($networkType))->isSuperTypeOf($type)->yes()) {
                return true;
            }
        }

        return false;
    }
}
