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

namespace AnimeDb\PluginContracts\Tests\LinkRules;

use AnimeDb\PluginContracts\LinkRules\LinkRulesExamplesChecker;
use PHPUnit\Framework\TestCase;

class LinkRulesExamplesCheckerTest extends TestCase
{
    private const RULES = [
        'https://a\.example/(\d+)' => 'https://canon.example/a/$1',
        'https://b\.example/(\d+)' => 'https://canon.example/b/$1',
    ];

    /**
     * @param array<array-key, mixed> $examples
     * @param array<string, string>   $rules
     *
     * @return list<string>
     */
    private function messages(array $examples, array $rules = self::RULES): array
    {
        return array_map(
            static fn ($error) => $error->field.': '.$error->message,
            (new LinkRulesExamplesChecker())->check($rules, $examples),
        );
    }

    public function testAllExamplesMatch(): void
    {
        self::assertSame([], $this->messages(['version' => 1, 'examples' => [
            'https://a.example/1' => 'https://canon.example/a/1',
            'https://b.example/2' => 'https://canon.example/b/2',
            'https://c.example/3' => null,
        ]]));
    }

    public function testMismatchedString(): void
    {
        self::assertSame(
            ['examples.https://a.example/1: expected "https://wrong.example/", got "https://canon.example/a/1"'],
            $this->messages(['version' => 1, 'examples' => [
                'https://a.example/1' => 'https://wrong.example/',
                'https://b.example/2' => 'https://canon.example/b/2',
            ]]),
        );
    }

    public function testExpectedNullButRuleMatched(): void
    {
        self::assertSame(
            ['examples.https://a.example/1: expected null, got "https://canon.example/a/1"'],
            $this->messages(['version' => 1, 'examples' => [
                'https://a.example/1' => null,
                'https://b.example/2' => 'https://canon.example/b/2',
            ]]),
        );
    }

    public function testExpectedStringButNothingMatched(): void
    {
        self::assertContains(
            'examples.https://c.example/3: expected "https://canon.example/c/3", got null',
            $this->messages(['version' => 1, 'examples' => [
                'https://a.example/1' => 'https://canon.example/a/1',
                'https://b.example/2' => 'https://canon.example/b/2',
                'https://c.example/3' => 'https://canon.example/c/3',
            ]]),
        );
    }

    public function testRuleWithoutExample(): void
    {
        self::assertSame(
            ['rules.https://b\.example/(\d+): no example matches this rule'],
            $this->messages(['version' => 1, 'examples' => ['https://a.example/1' => 'https://canon.example/a/1']]),
        );
    }

    public function testShadowedRuleCountsAsUncovered(): void
    {
        $rules = [
            'https://a\.example/(\d+)' => 'https://canon.example/$1',
            'https://a\.example/(1)' => 'https://other.example/$1',
        ];

        self::assertSame(
            ['rules.https://a\.example/(1): no example matches this rule'],
            $this->messages(['version' => 1, 'examples' => ['https://a.example/1' => 'https://canon.example/1']], $rules),
        );
    }

    public function testInvalidShape(): void
    {
        self::assertSame(['version: Field "version" must be the integer 1.'], $this->messages(
            ['version' => 2, 'examples' => ['https://a.example/1' => 'https://canon.example/a/1', 'https://b.example/2' => 'https://canon.example/b/2']],
        ));
        self::assertSame([
            'version: Field "version" must be the integer 1.',
            'examples: Field "examples" must be a map of link to expected result.',
        ], $this->messages(['examples' => 'x']));
        self::assertSame(
            ['examples: Field "examples" must be a map of link to expected result.'],
            $this->messages(['version' => 1]),
        );
    }

    public function testInvalidKeysAndValuesAreAllReported(): void
    {
        $messages = $this->messages(['version' => 1, 'examples' => [
            'http://a.example/1' => 'https://canon.example/a/1',
            5 => null,
            'https://a.example/2' => 42,
            'https://b.example/2' => 'https://canon.example/b/2',
        ]], ['https://b\.example/(\d+)' => 'https://canon.example/b/$1']);

        self::assertSame([
            'examples: Example key "http://a.example/1" must be a string starting with "https://".',
            'examples: Example key "5" must be a string starting with "https://".',
            'examples.https://a.example/2: Expected result must be a string or null.',
        ], $messages);
    }

    public function testAllErrorsAreReturnedAtOnce(): void
    {
        $messages = $this->messages(['version' => 3, 'examples' => [
            'https://a.example/1' => 'https://wrong.example/',
            'ftp://x' => null,
        ]]);

        self::assertCount(4, $messages);
    }

    public function testRuleFailingOnExampleIsReported(): void
    {
        $rules = [
            'https://(?:a+)+(?:b|\.example/x)(\d*)' => 'https://bad.example/$1',
            'https://(\w+)\.example/' => 'https://good.example/$1',
        ];
        $long = str_repeat('a', 40);

        $messages = $this->messages(['version' => 1, 'examples' => [
            'https://ab' => 'https://bad.example/',
            'https://'.$long.'.example/' => 'https://good.example/'.$long,
        ]], $rules);

        $this->assertCount(1, $messages);
        $this->assertStringStartsWith('rules.https://(?:a+)+(?:b|\.example/x)(\d*): rule failed on example "https://'.$long, $messages[0]);
    }

    public function testNonCompilingRuleIsReportedWithRealCause(): void
    {
        $messages = $this->messages(['version' => 1, 'examples' => ['https://x.example/' => null]], ['https://(' => 'https://a.example/']);

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('rule failed on example', $messages[0]);
        $this->assertStringNotContainsString('no example matches', $messages[0]);
    }
}
