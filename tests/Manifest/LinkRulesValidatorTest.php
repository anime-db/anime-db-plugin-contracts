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

namespace AnimeDb\PluginContracts\Tests\Manifest;

use AnimeDb\PluginContracts\Manifest\LinkRulesValidator;
use PHPUnit\Framework\TestCase;

class LinkRulesValidatorTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function exampleFile(): array
    {
        return json_decode(<<<'JSON'
            {
                "version": 1,
                "rules": [
                    {
                        "host": "allcinema\\.net",
                        "path": "^/prog/show_c\\.php$",
                        "query": {"num_c": "^(\\d+)$"},
                        "canonical": "https://allcinema.net/cinema/{query.num_c.1}"
                    },
                    {
                        "host": "allcinema\\.net",
                        "path": "^/cinema/(\\d+)(?:/.*)?$",
                        "canonical": "https://allcinema.net/cinema/{path.1}"
                    },
                    {
                        "host": "(?<lang>[a-z-]+)\\.(?:m\\.)?wikipedia\\.org",
                        "path": "^/wiki/(.+)$",
                        "canonical": "https://{host.lang}.wikipedia.org/wiki/{path.1}"
                    }
                ]
            }
            JSON, true, flags: \JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function withRule(array $override): array
    {
        $rule = ['host' => 'example\.org', 'path' => '^/a/(\d+)$', 'canonical' => 'https://example.org/a/{path.1}'];

        return ['version' => 1, 'rules' => [array_replace($rule, $override)]];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<string>
     */
    private function fields(array $data): array
    {
        return array_map(static fn ($error) => $error->field, (new LinkRulesValidator())->validate($data));
    }

    public function testExampleFileHasNoErrors(): void
    {
        self::assertSame([], (new LinkRulesValidator())->validate($this->exampleFile()));
    }

    public function testVersionMustBeOne(): void
    {
        $data = $this->exampleFile();
        $data['version'] = 2;

        self::assertSame(['version'], $this->fields($data));
    }

    public function testRulesMustBeAList(): void
    {
        self::assertSame(['rules'], $this->fields(['version' => 1, 'rules' => ['a' => []]]));
    }

    public function testQueryMustBeStringToStringObject(): void
    {
        self::assertSame(['rules.0.query.p'], $this->fields($this->withRule(['query' => ['p' => 5]])));
        self::assertSame(['rules.0.query'], $this->fields($this->withRule(['query' => ['x']])));
    }

    public function testUncompilableExpressionIsRejected(): void
    {
        self::assertSame(['rules.0.path'], $this->fields($this->withRule(['path' => '^/a/(\d+$'])));
    }

    public function testCanonicalMustBeHttps(): void
    {
        self::assertSame(['rules.0.canonical'], $this->fields($this->withRule(['canonical' => 'http://example.org/a/{path.1}'])));
    }

    public function testLiteralCanonicalHostMustMatchHost(): void
    {
        self::assertSame(['rules.0.canonical'], $this->fields($this->withRule(['canonical' => 'https://other.org/a/{path.1}'])));
    }

    public function testSubstitutionMustReferToExistingGroup(): void
    {
        self::assertSame(['rules.0.canonical'], $this->fields($this->withRule(['canonical' => 'https://example.org/a/{path.2}'])));
        self::assertSame(['rules.0.canonical'], $this->fields($this->withRule(['canonical' => 'https://example.org/a/{query.p.1}'])));
        self::assertSame(
            ['rules.0.canonical'],
            $this->fields($this->withRule(['host' => '(?<lang>[a-z]+)\.example\.org', 'canonical' => 'https://{host.zz}.example.org/a/{path.1}'])),
        );
    }

    public function testCanonicalWithoutPathOrQuerySubstitutionIsRejected(): void
    {
        self::assertSame(['rules.0.canonical'], $this->fields($this->withRule(['canonical' => 'https://example.org/'])));
    }

    public function testCanonicalWithOnlyHostSubstitutionIsRejected(): void
    {
        $data = $this->withRule(['host' => '(?<lang>[a-z]+)\.example\.org', 'canonical' => 'https://{host.lang}.example.org/']);

        self::assertSame(['rules.0.canonical'], $this->fields($data));
    }

    public function testAtMost500RulesAreAllowed(): void
    {
        $rule = $this->withRule([])['rules'][0];

        self::assertSame([], (new LinkRulesValidator())->validate(['version' => 1, 'rules' => array_fill(0, 500, $rule)]));
        self::assertSame(['rules'], $this->fields(['version' => 1, 'rules' => array_fill(0, 501, $rule)]));
    }

    public function testExpressionLengthIsLimitedTo256Characters(): void
    {
        self::assertSame([], (new LinkRulesValidator())->validate($this->withRule(['path' => '('.str_repeat('a', 254).')'])));
        self::assertSame(['rules.0.path'], $this->fields($this->withRule(['path' => '('.str_repeat('a', 255).')'])));
    }

    public function testAllErrorsAreReported(): void
    {
        $data = $this->withRule(['path' => '(', 'canonical' => 'http://example.org/']);
        $data['version'] = 3;

        self::assertSame(['version', 'rules.0.path', 'rules.0.canonical'], $this->fields($data));
    }
}
