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

    public function wrapInjectedClient(\Psr\Http\Client\ClientInterface $inner): \Psr\Http\Client\ClientInterface
    {
        return new \AnimeDb\PluginContracts\Tests\PHPStan\Rules\Fixtures\ClientDecorator($inner);
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

    public function processSimplexmlLoadFile(): void
    {
        simplexml_load_file('https://example.com/feed.xml');
    }

    public function processGetimagesize(): void
    {
        getimagesize('https://example.com/cover.jpg');
    }

    public function processFilePutContents(): void
    {
        file_put_contents('https://example.com/upload.txt', 'data');
    }

    public function processDnsGetRecord(): void
    {
        dns_get_record('example.com');
    }

    public function processGethostbyname(): void
    {
        gethostbyname('example.com');
    }

    public function processDomDocumentLoad(): void
    {
        $document = new \DOMDocument();
        $document->load('https://example.com/feed.xml');
    }

    public function processConcatenatedUrl(string $id): void
    {
        file_get_contents('https://example.com/anime/'.$id);
    }

    public function processSymfonyHttpClientStaticCall(string $url): void
    {
        \Symfony\Component\HttpClient\HttpClient::create()->request('GET', $url);
    }

    public function processPsr18ClientDiscovery(): void
    {
        \Http\Discovery\Psr18ClientDiscovery::find();
    }

    public function processSymfonyProcess(): void
    {
        $process = new \Symfony\Component\Process\Process(['ls', '-la']);
        $process->run();
    }

    public function processSoapClient(): void
    {
        new \SoapClient('http://example.com/service.wsdl');
    }

    public function processSoapClientSubclass(): void
    {
        new \AnimeDb\PluginContracts\Tests\PHPStan\Rules\Fixtures\SoapClientSubclass('http://example.com/service.wsdl');
    }

    public function processSymfonyProcessSubclass(): void
    {
        $process = new \AnimeDb\PluginContracts\Tests\PHPStan\Rules\Fixtures\ProcessSubclass(['ls', '-la']);
        $process->run();
    }

    public function processCurlHttpClient(): void
    {
        new \Symfony\Component\HttpClient\CurlHttpClient();
    }

    public function processNativeHttpClient(): void
    {
        new \Symfony\Component\HttpClient\NativeHttpClient();
    }

    public function processPsr18Client(): void
    {
        new \Symfony\Component\HttpClient\Psr18Client();
    }
}
