<?php

declare(strict_types=1);

namespace AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data;

use AnimeDb\PluginContracts\FillerInterface;
use AnimeDb\PluginContracts\PluginAnimeData;
use AnimeDb\PluginContracts\SyncInterface;
use AnimeDb\PluginContracts\SyncItem;

class ConformingFillerPlugin implements FillerInterface
{
    public function resolveExternalId(array $urls): ?string
    {
        return null;
    }

    public function find(string $name, ?callable $onHeartbeat = null): array
    {
        return [];
    }

    public function findById(string $externalId): ?PluginAnimeData
    {
        return null;
    }

    public function getFillableFields(): array
    {
        return [];
    }
}

class RenamedParameterPlugin implements SyncInterface
{
    public function resolveExternalId(array $urls): ?string
    {
        return null;
    }

    public function find(string $name, ?callable $onHeartbeat = null): array
    {
        return [];
    }

    public function findById(string $externalId): ?PluginAnimeData
    {
        return null;
    }

    public function getFillableFields(): array
    {
        return [];
    }

    public function push(SyncItem $syncItem): void
    {
    }

    public function pull(): iterable
    {
        return [];
    }
}

class WrongParameterTypePlugin implements SyncInterface
{
    public function resolveExternalId(array $urls): ?string
    {
        return null;
    }

    public function find(string $name, ?callable $onHeartbeat = null): array
    {
        return [];
    }

    public function findById(string $externalId): ?PluginAnimeData
    {
        return null;
    }

    public function getFillableFields(): array
    {
        return [];
    }

    public function push(object $item): void
    {
    }

    public function pull(): iterable
    {
        return [];
    }
}

class WrongReturnTypePlugin implements SyncInterface
{
    public function resolveExternalId(array $urls): ?string
    {
        return null;
    }

    public function find(string $name, ?callable $onHeartbeat = null): array
    {
        return [];
    }

    public function findById(string $externalId): ?PluginAnimeData
    {
        return null;
    }

    public function getFillableFields(): array
    {
        return [];
    }

    public function push(SyncItem $item): void
    {
    }

    public function pull(): array
    {
        return [];
    }
}

abstract class AbstractSyncPlugin implements SyncInterface
{
    public function resolveExternalId(array $urls): ?string
    {
        return null;
    }
}
