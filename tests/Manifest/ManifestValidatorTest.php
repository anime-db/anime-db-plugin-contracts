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

use AnimeDb\PluginContracts\Manifest\ManifestValidator;
use PHPUnit\Framework\TestCase;

class ManifestValidatorTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function validIntegrationManifest(): array
    {
        return [
            'id' => 'vendor-shikimori',
            'name' => 'Shikimori',
            'version' => '1.0.0',
            'type' => 'integration',
            'features' => ['filler' => true, 'related_widget' => true, 'sync' => true],
            'require' => [
                'core' => '>=2.0.0',
                'php' => '>=8.2',
                'plugin-contracts' => '^2.0',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validTranslationManifest(): array
    {
        return [
            'id' => 'vendor-locale-ru',
            'name' => 'Russian translation',
            'version' => '1.0.0',
            'type' => 'translation',
            'locales' => ['ru', 'uk'],
            'require' => [
                'core' => '>=2.0.0',
                'php' => '>=8.2',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validLocalManifest(): array
    {
        return [
            'id' => 'vendor-auto-tagger',
            'name' => 'Auto Tagger',
            'version' => '1.0.0',
            'type' => 'local',
            'require' => [
                'core' => '>=2.0.0',
                'php' => '>=8.2',
            ],
        ];
    }

    public function testValidIntegrationManifestHasNoErrors(): void
    {
        $errors = (new ManifestValidator())->validate($this->validIntegrationManifest());

        self::assertSame([], $errors);
    }

    public function testValidTranslationManifestHasNoErrors(): void
    {
        $errors = (new ManifestValidator())->validate($this->validTranslationManifest());

        self::assertSame([], $errors);
    }

    public function testValidLocalManifestHasNoErrors(): void
    {
        $errors = (new ManifestValidator())->validate($this->validLocalManifest());

        self::assertSame([], $errors);
    }

    public function testFeaturesIsNotAllowedForLocalType(): void
    {
        $data = $this->validLocalManifest();
        $data['features'] = ['filler' => true];

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('features', array_map(static fn ($error) => $error->field, $errors));
    }

    public function testLocalesIsAllowedForLocalType(): void
    {
        $data = $this->validLocalManifest();
        $data['locales'] = ['ru'];

        $errors = (new ManifestValidator())->validate($data);

        self::assertSame([], $errors);
    }

    public function testMissingRequiredFieldsAreReported(): void
    {
        $errors = (new ManifestValidator())->validate([]);
        $fields = array_map(static fn ($error) => $error->field, $errors);

        self::assertContains('id', $fields);
        self::assertContains('name', $fields);
        self::assertContains('version', $fields);
        self::assertContains('type', $fields);
        self::assertContains('require', $fields);
    }

    public function testInvalidTypeValueIsReported(): void
    {
        $data = $this->validIntegrationManifest();
        $data['type'] = 'unknown-type';

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('type', array_map(static fn ($error) => $error->field, $errors));
    }

    public function testFeaturesIsRequiredForIntegrationType(): void
    {
        $data = $this->validIntegrationManifest();
        unset($data['features']);

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('features', array_map(static fn ($error) => $error->field, $errors));
    }

    public function testLocalesIsAllowedForIntegrationType(): void
    {
        $data = $this->validIntegrationManifest();
        $data['locales'] = ['ru'];

        $errors = (new ManifestValidator())->validate($data);

        self::assertSame([], $errors);
    }

    public function testFeaturesIsNotAllowedForTranslationType(): void
    {
        $data = $this->validTranslationManifest();
        $data['features'] = ['filler' => true];

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('features', array_map(static fn ($error) => $error->field, $errors));
    }

    public function testFeatureFlagMustBeBoolean(): void
    {
        $data = $this->validIntegrationManifest();
        $data['features']['filler'] = 'yes';

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('features.filler', array_map(static fn ($error) => $error->field, $errors));
    }

    public function testLocaleMustBeNonEmptyString(): void
    {
        $data = $this->validTranslationManifest();
        $data['locales'] = ['ru', ''];

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('locales.1', array_map(static fn ($error) => $error->field, $errors));
    }

    public function testLocaleMustBeNonEmptyStringForOptionalTypes(): void
    {
        $data = $this->validIntegrationManifest();
        $data['locales'] = ['ru', ''];

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('locales.1', array_map(static fn ($error) => $error->field, $errors));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidLocaleCodes(): iterable
    {
        yield 'language and region' => ['pt-BR'];
        yield 'language and script' => ['zh-Hans'];
        yield 'underscore separator' => ['ru_RU'];
        yield 'uppercase' => ['RU'];
        yield 'single letter' => ['r'];
        yield 'not a language code' => ['russian'];
    }

    /**
     * @dataProvider invalidLocaleCodes
     */
    public function testLocaleMustBeABareLanguageSubtag(string $locale): void
    {
        $data = $this->validTranslationManifest();
        $data['locales'] = [$locale];

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('locales.0', array_map(static fn ($error) => $error->field, $errors));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function validLocaleCodes(): iterable
    {
        yield 'english' => ['en'];
        yield 'russian' => ['ru'];
        yield 'german' => ['de'];
        yield 'japanese' => ['ja'];
        yield 'three-letter code' => ['fil'];
    }

    /**
     * @dataProvider validLocaleCodes
     */
    public function testLocaleAcceptsBareLanguageSubtag(string $locale): void
    {
        $data = $this->validTranslationManifest();
        $data['locales'] = [$locale];

        $errors = (new ManifestValidator())->validate($data);

        self::assertSame([], $errors);
    }

    public function testVersionMustBeAValidSemverVersion(): void
    {
        $data = $this->validIntegrationManifest();
        $data['version'] = 'not-a-version';

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('version', array_map(static fn ($error) => $error->field, $errors));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function disallowedCoreConstraints(): iterable
    {
        yield 'caret range' => ['^2.0'];
        yield 'range with upper bound' => ['>=2.0.0,<3.0.0'];
        yield 'exact version' => ['2.0.0'];
        yield 'not a constraint' => ['not-a-constraint'];
    }

    /**
     * @dataProvider disallowedCoreConstraints
     */
    public function testRequireCoreRejectsAnythingOtherThanASingleLowerBound(string $constraint): void
    {
        $data = $this->validIntegrationManifest();
        $data['require']['core'] = $constraint;

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('require.core', array_map(static fn ($error) => $error->field, $errors));
    }

    public function testRequirePhpRejectsAnythingOtherThanASingleLowerBound(): void
    {
        $data = $this->validIntegrationManifest();
        $data['require']['php'] = '^8.2';

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('require.php', array_map(static fn ($error) => $error->field, $errors));
    }

    public function testRequirePluginContractsAcceptsAnyValidConstraint(): void
    {
        $data = $this->validIntegrationManifest();
        $data['require']['plugin-contracts'] = '>=2.0.0,<3.0.0';

        $errors = (new ManifestValidator())->validate($data);

        self::assertSame([], $errors);
    }

    public function testRequirePluginContractsRejectsInvalidConstraintSyntax(): void
    {
        $data = $this->validIntegrationManifest();
        $data['require']['plugin-contracts'] = 'not-a-constraint';

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('require.plugin-contracts', array_map(static fn ($error) => $error->field, $errors));
    }

    public function testRequireCoreAndPhpAreRequired(): void
    {
        $data = $this->validIntegrationManifest();
        unset($data['require']['core'], $data['require']['php']);

        $errors = (new ManifestValidator())->validate($data);
        $fields = array_map(static fn ($error) => $error->field, $errors);

        self::assertContains('require.core', $fields);
        self::assertContains('require.php', $fields);
    }

    public function testUpdateUrlMustBeAValidUrl(): void
    {
        $data = $this->validIntegrationManifest();
        $data['update_url'] = 'not a url';

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('update_url', array_map(static fn ($error) => $error->field, $errors));
    }

    public function testValidUpdateUrlProducesNoError(): void
    {
        $data = $this->validIntegrationManifest();
        $data['update_url'] = 'https://example.com/plugins/registry.json';

        $errors = (new ManifestValidator())->validate($data);

        self::assertSame([], $errors);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function nonHttpUpdateUrls(): iterable
    {
        yield 'file scheme' => ['file:///etc/passwd'];
        yield 'ftp scheme' => ['ftp://example.com/x'];
    }

    /**
     * @dataProvider nonHttpUpdateUrls
     */
    public function testUpdateUrlRejectsNonHttpSchemes(string $url): void
    {
        $data = $this->validIntegrationManifest();
        $data['update_url'] = $url;

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('update_url', array_map(static fn ($error) => $error->field, $errors));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidIds(): iterable
    {
        yield 'underscore' => ['acme_demo'];
        yield 'uppercase' => ['Acme-demo'];
        yield 'space' => ['acme demo'];
        yield 'no separator' => ['acme'];
        yield 'leading hyphen' => ['-acme-demo'];
        yield 'trailing hyphen' => ['acme-demo-'];
        yield 'double hyphen' => ['acme--demo'];
    }

    /**
     * @dataProvider invalidIds
     */
    public function testIdRejectsInvalidSlugFormat(string $id): void
    {
        $data = $this->validIntegrationManifest();
        $data['id'] = $id;

        $errors = (new ManifestValidator())->validate($data);

        self::assertContains('id', array_map(static fn ($error) => $error->field, $errors));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function validIds(): iterable
    {
        yield 'two segments' => ['vendor-name'];
        yield 'three segments' => ['vendor-some-name'];
    }

    /**
     * @dataProvider validIds
     */
    public function testIdAcceptsValidSlugFormat(string $id): void
    {
        $data = $this->validIntegrationManifest();
        $data['id'] = $id;

        $errors = (new ManifestValidator())->validate($data);

        self::assertSame([], $errors);
    }

    public function testErrorContainsFieldAndMessage(): void
    {
        $errors = (new ManifestValidator())->validate([]);
        $idError = array_values(array_filter($errors, static fn ($error) => $error->field === 'id'))[0];

        self::assertSame('id', $idError->field);
        self::assertNotSame('', $idError->message);
    }
}
