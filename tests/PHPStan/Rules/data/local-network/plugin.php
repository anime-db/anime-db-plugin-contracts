<?php

declare(strict_types=1);

namespace AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data\LocalNetwork;

use AnimeDb\PluginContracts\CandidateSearch\DownloadCandidateSearchInterface;
use AnimeDb\PluginContracts\Settings\SettingsStoreInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class NetworkInjectingPlugin
{
    private RequestFactoryInterface $requestFactory;

    public function __construct(
        private readonly ClientInterface $client,
        ?DownloadCandidateSearchInterface $downloadSearch,
        private readonly SettingsStoreInterface $settings,
        RequestFactoryInterface $requestFactory,
    ) {
        $this->requestFactory = $requestFactory;
    }
}
