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

namespace AnimeDb\PluginContracts\Tests\Manifest;

use AnimeDb\PluginContracts\Manifest\InvalidManifestJsonException;
use AnimeDb\PluginContracts\Manifest\ManifestParser;
use AnimeDb\PluginContracts\Manifest\PluginType;
use PHPUnit\Framework\TestCase;

class ManifestParserTest extends TestCase
{
    public function testParseValidIntegrationManifest(): void
    {
        $json = <<<'JSON'
            {
                "id": "vendor-shikimori",
                "name": "Shikimori",
                "version": "1.0.0",
                "description": "Описание плагина",
                "author": "Vendor Name",
                "type": "integration",
                "features": {"filler": true, "related_widget": true, "sync": true},
                "require": {
                    "core": ">=2.0.0",
                    "php": ">=8.2",
                    "plugin-contracts": "^2.0"
                },
                "update_url": "https://example.com/plugins/registry.json"
            }
            JSON;

        $manifest = (new ManifestParser())->parse($json);

        self::assertSame('vendor-shikimori', $manifest->id);
        self::assertSame('Shikimori', $manifest->name);
        self::assertSame('1.0.0', $manifest->version);
        self::assertSame(PluginType::Integration, $manifest->type);
        self::assertSame('Описание плагина', $manifest->description);
        self::assertSame('Vendor Name', $manifest->author);
        self::assertSame(['filler' => true, 'related_widget' => true, 'sync' => true], $manifest->features);
        self::assertNull($manifest->locales);
        self::assertSame('>=2.0.0', $manifest->require->core);
        self::assertSame('>=8.2', $manifest->require->php);
        self::assertSame('^2.0', $manifest->require->pluginContracts);
        self::assertSame('https://example.com/plugins/registry.json', $manifest->updateUrl);
    }

    public function testParseValidTranslationManifest(): void
    {
        $json = <<<'JSON'
            {
                "id": "vendor-locale-ru",
                "name": "Russian translation",
                "version": "1.0.0",
                "type": "translation",
                "locales": ["ru", "uk"],
                "require": {"core": ">=2.0.0", "php": ">=8.2"}
            }
            JSON;

        $manifest = (new ManifestParser())->parse($json);

        self::assertSame(PluginType::Translation, $manifest->type);
        self::assertSame(['ru', 'uk'], $manifest->locales);
        self::assertNull($manifest->features);
        self::assertNull($manifest->require->pluginContracts);
        self::assertNull($manifest->updateUrl);
        self::assertNull($manifest->description);
        self::assertNull($manifest->author);
    }

    public function testParseThrowsOnMalformedJson(): void
    {
        $this->expectException(InvalidManifestJsonException::class);

        (new ManifestParser())->parse('{"id": "broken",');
    }

    public function testParseThrowsOnTopLevelJsonArray(): void
    {
        $this->expectException(InvalidManifestJsonException::class);

        (new ManifestParser())->parse('["id", "name"]');
    }

    public function testParseThrowsOnTopLevelJsonScalar(): void
    {
        $this->expectException(InvalidManifestJsonException::class);

        (new ManifestParser())->parse('"just a string"');
    }

    public function testDecodeReturnsRawAssociativeArray(): void
    {
        $data = (new ManifestParser())->decode('{"id": "vendor-shikimori", "type": "integration"}');

        self::assertSame(['id' => 'vendor-shikimori', 'type' => 'integration'], $data);
    }
}
