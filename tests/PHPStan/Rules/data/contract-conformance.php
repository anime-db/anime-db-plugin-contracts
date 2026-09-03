<?php

declare(strict_types=1);

namespace AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data;

use AnimeDb\PluginContracts\CandidateSearch\AnimeSearchResult;
use AnimeDb\PluginContracts\CandidateSearch\DownloadCandidateSearchInterface;
use AnimeDb\PluginContracts\Filler\FillerInterface;
use AnimeDb\PluginContracts\Filler\PluginAnimeData;
use AnimeDb\PluginContracts\Model\AnimeId;
use AnimeDb\PluginContracts\Search\SearchByPluginCandidate;
use AnimeDb\PluginContracts\Settings\SettingsPageInterface;
use AnimeDb\PluginContracts\Sync\SyncInterface;
use AnimeDb\PluginContracts\Sync\SyncItem;

class ConformingFillerPlugin implements FillerInterface
{
    public function resolveExternalId(array $urls): ?string
    {
        return null;
    }

    /**
     * @return list<SearchByPluginCandidate>
     */
    public function find(string $name, ?callable $onHeartbeat = null): array
    {
        return [];
    }

    public function findById(string $externalId): ?PluginAnimeData
    {
        return null;
    }

    /**
     * @return list<string>
     */
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

    /**
     * @return list<SearchByPluginCandidate>
     */
    public function find(string $name, ?callable $onHeartbeat = null): array
    {
        return [];
    }

    public function findById(string $externalId): ?PluginAnimeData
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function getFillableFields(): array
    {
        return [];
    }

    public function push(SyncItem $syncItem): SyncItem
    {
        return $syncItem;
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

    /**
     * @return list<SearchByPluginCandidate>
     */
    public function find(string $name, ?callable $onHeartbeat = null): array
    {
        return [];
    }

    public function findById(string $externalId): ?PluginAnimeData
    {
        return null;
    }

    /**
     * @return list<string>
     */
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

    /**
     * @return list<SearchByPluginCandidate>
     */
    public function find(string $name, ?callable $onHeartbeat = null): array
    {
        return [];
    }

    public function findById(string $externalId): ?PluginAnimeData
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function getFillableFields(): array
    {
        return [];
    }

    public function push(SyncItem $item): SyncItem
    {
        return $item;
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

class ConformingDownloadCandidateSearchPlugin implements DownloadCandidateSearchInterface
{
    public function search(string $query): AnimeSearchResult
    {
        return new AnimeSearchResult([]);
    }

    public function runAction(string $actionId, string $meta, AnimeId $anime): void
    {
    }
}

class ConformingSettingsPagePlugin implements SettingsPageInterface
{
    public function render(): string
    {
        return '';
    }
}
