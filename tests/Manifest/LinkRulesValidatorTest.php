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
     * @param array<array-key, mixed> $data
     *
     * @return list<string>
     */
    private function fields(array $data): array
    {
        return array_map(static fn ($error) => $error->field, (new LinkRulesValidator())->validate($data));
    }

    /**
     * @return list<string>
     */
    private function ruleFields(string $expression, mixed $replacement): array
    {
        return $this->fields(['version' => 1, 'rules' => [$expression => $replacement]]);
    }

    public function testExampleFileHasNoErrors(): void
    {
        $data = [
            'version' => 1,
            'rules' => [
                'https://([a-z-]+)\.(?:m\.)?wikipedia\.org/wiki/([^?]+)' => 'https://$1.wikipedia.org/wiki/$2',
                'https://allcinema\.net/prog/show_c\.php\?num_c=(\d+)' => 'https://allcinema.net/cinema/$1',
            ],
        ];

        self::assertSame([], (new LinkRulesValidator())->validate($data));
    }

    public function testBracedSubstitutionIsAccepted(): void
    {
        self::assertSame([], $this->ruleFields('https://example\.org/(\d+)', 'https://example.org/a/${1}0'));
    }

    public function testEmptyRulesAreValid(): void
    {
        self::assertSame([], $this->fields(['version' => 1, 'rules' => []]));
    }

    public function testNoLimitOnRuleCount(): void
    {
        $rules = [];
        for ($i = 0; $i < 501; ++$i) {
            $rules['https://example\.org/'.$i.'/(\d+)'] = 'https://example.org/a/$1';
        }

        self::assertSame([], $this->fields(['version' => 1, 'rules' => $rules]));
    }

    public function testVersionMustBeOne(): void
    {
        self::assertSame(['version'], $this->fields(['version' => 2, 'rules' => []]));
        self::assertSame(['version'], $this->fields(['rules' => []]));
    }

    public function testRulesMustBeMap(): void
    {
        self::assertSame(['rules'], $this->fields(['version' => 1, 'rules' => 'x']));
        self::assertSame(['rules'], $this->fields(['version' => 1]));
    }

    public function testNonStringKeyIsRejected(): void
    {
        self::assertSame(['rules'], $this->fields(['version' => 1, 'rules' => [123 => 'https://example.org/$1']]));
    }

    public function testNonStringReplacementIsRejected(): void
    {
        self::assertSame(['rules.a(b)'], $this->ruleFields('a(b)', ['x']));
    }

    public function testUncompilableExpressionIsRejected(): void
    {
        self::assertCount(1, $this->ruleFields('https://example\.org/(\d+', 'https://example.org/$1'));
    }

    public function testExpressionBreakingOutOfWrapperIsRejected(): void
    {
        // Compiles inside \A(?:…)\z, but on its own it is unbalanced and would match any link.
        self::assertCount(1, $this->ruleFields('x)|(?:.*', 'https://example.org/$1'));
        self::assertCount(1, $this->ruleFields('x)|(.*', 'https://example.org/$1'));
    }

    public function testControlCharacterInExpressionIsRejected(): void
    {
        self::assertCount(1, $this->ruleFields("https://example\\.org/(\\d+)\n", 'https://example.org/$1'));
    }

    public function testExpressionLengthLimit(): void
    {
        $ok = '('.str_repeat('a', 254).')';
        $long = '('.str_repeat('a', 255).')';

        self::assertSame([], $this->ruleFields($ok, 'https://example.org/$1'));
        self::assertCount(1, $this->ruleFields($long, 'https://example.org/$1'));
    }

    public function testReplacementMustStartWithHttps(): void
    {
        self::assertCount(1, $this->ruleFields('(\d+)', 'http://example.org/$1'));
    }

    public function testReplacementWithoutSubstitutionIsRejected(): void
    {
        self::assertCount(1, $this->ruleFields('https://example\.org/.*', 'https://example.org/'));
    }

    public function testSubstitutionBeyondGroupCountIsRejected(): void
    {
        self::assertCount(1, $this->ruleFields('(\d+)', 'https://example.org/$2'));
        self::assertCount(1, $this->ruleFields('(\d+)', 'https://example.org/${2}'));
    }

    public function testZeroSubstitutionIsRejected(): void
    {
        self::assertCount(2, $this->ruleFields('(\d+)', 'https://example.org/$0/${0}')); // both, no valid one left
    }

    public function testReplacementWithWhitespaceIsRejected(): void
    {
        self::assertCount(1, $this->ruleFields('(\d+)', 'https://example.org/a b/$1'));
    }

    public function testReplacementWithBackslashIsRejected(): void
    {
        self::assertCount(1, $this->ruleFields('(\d+)', 'https://example.org\\a/$1'));
    }

    public function testReplacementWithControlCharacterIsRejected(): void
    {
        self::assertCount(1, $this->ruleFields('(\d+)', "https://example.org/\x01/\$1"));
    }

    public function testAllErrorsAreReported(): void
    {
        $data = ['version' => 1, 'rules' => [
            '(\d+)' => 'http://example.org/$1',
            'https://example\.org/(' => 'https://example.org/$1',
        ]];

        self::assertCount(2, $this->fields($data));
    }
}
