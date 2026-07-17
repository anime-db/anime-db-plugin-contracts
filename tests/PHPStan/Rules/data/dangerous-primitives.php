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

    public function processCurl(): void
    {
        $ch = curl_init('https://example.com');
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
    }

    public function processFileGetContents(): void
    {
        file_get_contents('https://example.com');
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
