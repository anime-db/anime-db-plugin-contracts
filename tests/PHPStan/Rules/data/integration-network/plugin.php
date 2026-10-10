<?php

declare(strict_types=1);

namespace AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data\IntegrationNetwork;

use AnimeDb\PluginContracts\Network\DatagramChannelFactoryInterface;
use Psr\Http\Client\ClientInterface;

final class NetworkInjectingPlugin
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly DatagramChannelFactoryInterface $datagrams,
    ) {
    }
}
