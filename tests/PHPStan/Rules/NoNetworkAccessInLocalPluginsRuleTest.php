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

namespace AnimeDb\PluginContracts\Tests\PHPStan\Rules;

use AnimeDb\PluginContracts\PHPStan\Rules\NoNetworkAccessInLocalPluginsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoNetworkAccessInLocalPluginsRule>
 */
class NoNetworkAccessInLocalPluginsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoNetworkAccessInLocalPluginsRule();
    }

    public function testLocalPluginDeclaringNetworkTypesIsReported(): void
    {
        $this->analyse([__DIR__.'/data/local-network/plugin.php'], [
            [
                'Constructor parameter $client of AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data\LocalNetwork\NetworkInjectingPlugin declares a network-capable type (Psr\Http\Client\ClientInterface), but the plugin\'s manifest.json declares it as type "local", which must not access the network even through a host-provided abstraction.',
                17,
            ],
            [
                'Constructor parameter $downloadSearch of AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data\LocalNetwork\NetworkInjectingPlugin declares a network-capable type (AnimeDb\PluginContracts\CandidateSearch\DownloadCandidateSearchInterface|null), but the plugin\'s manifest.json declares it as type "local", which must not access the network even through a host-provided abstraction.',
                18,
            ],
            [
                'Constructor parameter $requestFactory of AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data\LocalNetwork\NetworkInjectingPlugin declares a network-capable type (Psr\Http\Message\RequestFactoryInterface), but the plugin\'s manifest.json declares it as type "local", which must not access the network even through a host-provided abstraction.',
                20,
            ],
            [
                'Property $requestFactory of AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data\LocalNetwork\NetworkInjectingPlugin declares a network-capable type (Psr\Http\Message\RequestFactoryInterface), but the plugin\'s manifest.json declares it as type "local", which must not access the network even through a host-provided abstraction.',
                14,
            ],
        ]);
    }

    /**
     * The same network-typed constructor parameter as the "local" fixture above, but the
     * plugin's manifest.json declares it as type "integration" — declaring network
     * abstractions is exactly what an "integration" plugin is for.
     */
    public function testIntegrationPluginDeclaringNetworkTypesIsAllowed(): void
    {
        $this->analyse([__DIR__.'/data/integration-network/plugin.php'], []);
    }

    /**
     * No manifest.json exists in this fixture's directory or any of its parents, so the
     * rule has no way to tell whether this plugin is "local" — and stays silent, same as it
     * would for this contracts package's own source (which ships no plugin manifest either).
     */
    public function testFileWithoutAnyManifestIsSkipped(): void
    {
        $this->analyse([__DIR__.'/data/no-manifest/plugin.php'], []);
    }

    public function testManifestMissingTypeFieldIsReported(): void
    {
        $manifestPath = __DIR__.'/data/broken-manifest-missing-type/manifest.json';

        $this->analyse([__DIR__.'/data/broken-manifest-missing-type/plugin.php'], [
            [
                sprintf(
                    '%s could not be parsed, so it is unknown whether this plugin is of type "local": manifest.json is invalid: type: Field "type" is required.',
                    $manifestPath,
                ),
                7,
            ],
        ]);
    }

    public function testManifestWithUnrecognizedTypeIsReported(): void
    {
        $manifestPath = __DIR__.'/data/broken-manifest-unknown-type/manifest.json';

        $this->analyse([__DIR__.'/data/broken-manifest-unknown-type/plugin.php'], [
            [
                sprintf(
                    '%s could not be parsed, so it is unknown whether this plugin is of type "local": manifest.json is invalid: type: Field "type" must be one of "integration", "translation", "local".',
                    $manifestPath,
                ),
                7,
            ],
        ]);
    }

    public function testManifestWithInvalidJsonIsReported(): void
    {
        $manifestPath = __DIR__.'/data/broken-manifest-invalid-json/manifest.json';

        $this->analyse([__DIR__.'/data/broken-manifest-invalid-json/plugin.php'], [
            [
                sprintf(
                    '%s could not be parsed, so it is unknown whether this plugin is of type "local": manifest.json is not valid JSON: Syntax error',
                    $manifestPath,
                ),
                7,
            ],
        ]);
    }
}
