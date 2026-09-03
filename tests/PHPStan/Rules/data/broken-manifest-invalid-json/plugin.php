<?php

declare(strict_types=1);

namespace AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data\BrokenManifestInvalidJson;

final class SomePlugin
{
    public function __construct(private readonly string $label)
    {
    }
}
