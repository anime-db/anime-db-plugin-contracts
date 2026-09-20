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

namespace AnimeDb\PluginContracts\Tests\Media;

use AnimeDb\PluginContracts\Media\MediaFile;
use AnimeDb\PluginContracts\Media\MediaLibraryInterface;
use AnimeDb\PluginContracts\Media\StorageUnavailableException;
use AnimeDb\PluginContracts\Model\AnimeId;
use PHPUnit\Framework\TestCase;

class MediaLibraryInterfaceTest extends TestCase
{
    public function testListFilesReturnsEmptyArrayWhenThereIsNothingToList(): void
    {
        $library = $this->createLibrary([], null);

        self::assertSame([], $library->listFiles(new AnimeId(1)));
    }

    public function testListFilesReturnsFilesFoundOnDisk(): void
    {
        $file = new MediaFile('01.mkv', '01.mkv', 734003200, new \DateTimeImmutable('2026-08-11T12:00:00+00:00'));
        $library = $this->createLibrary([$file], null);

        self::assertSame([$file], $library->listFiles(new AnimeId(1)));
    }

    public function testListFilesThrowsWhenStorageIsUnreachable(): void
    {
        $library = $this->createLibrary([], new StorageUnavailableException('storage is offline'));

        $this->expectException(StorageUnavailableException::class);

        $library->listFiles(new AnimeId(1));
    }

    /**
     * @param MediaFile[] $files
     */
    private function createLibrary(array $files, ?StorageUnavailableException $exception): MediaLibraryInterface
    {
        return new class($files, $exception) implements MediaLibraryInterface {
            /**
             * @param MediaFile[] $files
             */
            public function __construct(
                private readonly array $files,
                private readonly ?StorageUnavailableException $exception,
            ) {
            }

            public function listFiles(AnimeId $anime): array
            {
                if ($this->exception !== null) {
                    throw $this->exception;
                }

                return $this->files;
            }
        };
    }
}
