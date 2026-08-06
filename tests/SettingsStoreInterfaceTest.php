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

namespace AnimeDb\PluginContracts\Tests;

use AnimeDb\PluginContracts\Settings\SettingsStoreInterface;
use PHPUnit\Framework\TestCase;

class SettingsStoreInterfaceTest extends TestCase
{
    public function testUpdateReplacesStoredSettingsWithModifierResult(): void
    {
        $store = new class implements SettingsStoreInterface {
            /** @var array<string, mixed> */
            private array $storage = [];

            public function read(): array
            {
                return $this->storage;
            }

            public function update(callable $modifier): void
            {
                $this->storage = $modifier($this->storage);
            }
        };

        self::assertSame([], $store->read());

        $store->update(static fn (): array => ['apiKey' => 'abc', 'syncEnabled' => true]);
        self::assertSame(['apiKey' => 'abc', 'syncEnabled' => true], $store->read());

        $store->update(static fn (): array => ['syncEnabled' => true]);
        self::assertSame(['syncEnabled' => true], $store->read());
    }

    public function testUpdatePassesCurrentSettingsToModifierForMerging(): void
    {
        $store = new class implements SettingsStoreInterface {
            /** @var array<string, mixed> */
            private array $storage = ['apiKey' => 'abc', 'syncEnabled' => true];

            public function read(): array
            {
                return $this->storage;
            }

            public function update(callable $modifier): void
            {
                $this->storage = $modifier($this->storage);
            }
        };

        $store->update(static fn (array $settings): array => [...$settings, 'apiKey' => 'new']);

        self::assertSame(['apiKey' => 'new', 'syncEnabled' => true], $store->read());
    }
}
