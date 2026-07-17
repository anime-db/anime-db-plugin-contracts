<?php

declare(strict_types=1);

namespace AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data;

class SafeUsage
{
    public function run(string $name, callable $httpGet): string
    {
        $upper = strtoupper($name);
        $formatted = sprintf('%s', $upper);

        return implode(',', array_map('strtolower', [$formatted, $httpGet('https://example.com')]));
    }

    public function callArrayCallable(): void
    {
        $callback = [$this, 'target'];
        $callback();
    }

    public function target(): void
    {
    }

    public function readLocalFile(): string
    {
        return file_get_contents(__DIR__.'/dangerous-primitives.php');
    }

    public function openLocalFile(): void
    {
        $handle = fopen(__DIR__.'/dangerous-primitives.php', 'r');
        fclose($handle);
    }

    public function copyLocalFile(): void
    {
        copy(__DIR__.'/dangerous-primitives.php', __DIR__.'/copy.php');
    }

    public function readLocalFileLines(): array
    {
        return file(__DIR__.'/dangerous-primitives.php');
    }

    public function outputLocalFile(): void
    {
        readfile(__DIR__.'/dangerous-primitives.php');
    }

    public function includeLocalFile(): void
    {
        require __DIR__.'/dangerous-primitives.php';
    }
}

class DangerousUsage
{
    public function processExec(): void
    {
        exec('ls -la');
    }

    public function processShellExec(): void
    {
        shell_exec('ls -la');
    }

    public function processBackticks(): void
    {
        $result = `ls -la`;
    }

    public function processEval(): void
    {
        eval('1 + 1;');
    }

    public function processProcOpen(): void
    {
        proc_open('ls', [], $pipes);
    }

    public function processFsockopen(): void
    {
        fsockopen('example.com', 80);
    }

    public function processPfsockopen(): void
    {
        pfsockopen('example.com', 80);
    }

    public function processStreamSocketClient(): void
    {
        stream_socket_client('tcp://example.com:80');
    }

    public function processStreamSocketServer(): void
    {
        stream_socket_server('tcp://0.0.0.0:8080');
    }

    public function processCurl(): void
    {
        $ch = curl_init('https://example.com');
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
    }

    public function processFileGetContents(): void
    {
        file_get_contents('https://example.com');
    }

    public function processFopen(): void
    {
        fopen('https://example.com', 'r');
    }

    public function processCopy(): void
    {
        copy('https://example.com/file.txt', __DIR__.'/file.txt');
    }

    public function processFile(): void
    {
        file('https://example.com');
    }

    public function processReadfile(): void
    {
        readfile('https://example.com');
    }

    public function processRequire(): void
    {
        require 'https://example.com/malicious.php';
    }

    public function processDynamicFunctionCall(): void
    {
        $function = 'exec';
        $function('ls -la');
    }

    public function processVariableVariable(): void
    {
        $name = 'value';
        $value = ${$name};
    }
}
