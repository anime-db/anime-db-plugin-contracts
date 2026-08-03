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

namespace AnimeDb\PluginContracts\Tests\OAuth;

use AnimeDb\PluginContracts\OAuth\AbstractOAuthClient;
use AnimeDb\PluginContracts\OAuth\OAuthStateMismatchException;
use AnimeDb\PluginContracts\OAuth\OAuthTokenExchangeException;
use AnimeDb\PluginContracts\Settings\SettingsStoreInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class AbstractOAuthClientTest extends TestCase
{
    private string|false $originalCallbackOrigin;

    protected function setUp(): void
    {
        $this->originalCallbackOrigin = $_SERVER['OAUTH_CALLBACK_ORIGIN'] ?? false;
        $_SERVER['OAUTH_CALLBACK_ORIGIN'] = 'http://127.0.0.1:38080';
    }

    protected function tearDown(): void
    {
        if ($this->originalCallbackOrigin === false) {
            unset($_SERVER['OAUTH_CALLBACK_ORIGIN']);
        } else {
            $_SERVER['OAUTH_CALLBACK_ORIGIN'] = $this->originalCallbackOrigin;
        }
    }

    public function testBuildAuthorizeUrlIncludesPkceStateAndRedirectUri(): void
    {
        $settings = $this->createSettingsStore();
        $client = $this->createClient($settings);

        $url = $client->buildAuthorizeUrl();

        self::assertStringStartsWith('https://vendor.example/oauth/authorize?', $url);

        parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);

        self::assertSame('code', $query['response_type']);
        self::assertSame('test-client-id', $query['client_id']);
        self::assertSame('http://127.0.0.1:38080/oauth/test/callback', $query['redirect_uri']);
        self::assertSame('read write', $query['scope']);
        self::assertSame('S256', $query['code_challenge_method']);

        $storedState = $settings->data['oauth_state'] ?? null;
        $storedVerifier = $settings->data['oauth_code_verifier'] ?? null;

        self::assertIsString($storedState);
        self::assertIsString($storedVerifier);
        self::assertSame($storedState, $query['state']);

        $expectedChallenge = rtrim(strtr(base64_encode(hash('sha256', $storedVerifier, true)), '+/', '-_'), '=');
        self::assertSame($expectedChallenge, $query['code_challenge']);
    }

    public function testHandleCallbackThrowsOnStateMismatch(): void
    {
        $settings = $this->createSettingsStore([
            'oauth_state' => 'expected-state',
            'oauth_code_verifier' => 'expected-verifier',
        ]);
        $client = $this->createClient($settings);

        $this->expectException(OAuthStateMismatchException::class);

        $client->handleCallback('wrong-state', 'the-code');
    }

    public function testHandleCallbackThrowsWhenNoAuthorizationIsPending(): void
    {
        $settings = $this->createSettingsStore();
        $client = $this->createClient($settings);

        $this->expectException(OAuthStateMismatchException::class);

        $client->handleCallback('any-state', 'the-code');
    }

    public function testHandleCallbackExchangesCodeForTokenAndPersistsIt(): void
    {
        $settings = $this->createSettingsStore([
            'apiKey' => 'unrelated-setting',
            'oauth_state' => 'expected-state',
            'oauth_code_verifier' => 'expected-verifier',
        ]);

        $capturedBody = null;
        [$httpClient, $requestFactory, $streamFactory] = $this->createHttpStack(
            200,
            ['access_token' => 'new-access', 'refresh_token' => 'new-refresh'],
            $capturedBody,
        );

        $client = $this->createClient($settings, $httpClient, $requestFactory, $streamFactory);

        $client->handleCallback('expected-state', 'the-code');

        parse_str((string) $capturedBody, $sentParams);
        self::assertSame('authorization_code', $sentParams['grant_type']);
        self::assertSame('the-code', $sentParams['code']);
        self::assertSame('expected-verifier', $sentParams['code_verifier']);
        self::assertSame('http://127.0.0.1:38080/oauth/test/callback', $sentParams['redirect_uri']);
        self::assertSame('test-client-id', $sentParams['client_id']);

        self::assertSame('unrelated-setting', $settings->data['apiKey']);
        self::assertSame('new-access', $settings->data['oauth_access_token']);
        self::assertSame('new-refresh', $settings->data['oauth_refresh_token']);
        self::assertArrayNotHasKey('oauth_state', $settings->data);
        self::assertArrayNotHasKey('oauth_code_verifier', $settings->data);
    }

    public function testHandleCallbackThrowsOnNonSuccessfulTokenResponse(): void
    {
        $settings = $this->createSettingsStore([
            'oauth_state' => 'expected-state',
            'oauth_code_verifier' => 'expected-verifier',
        ]);

        $capturedBody = null;
        [$httpClient, $requestFactory, $streamFactory] = $this->createHttpStack(
            400,
            ['error' => 'invalid_grant'],
            $capturedBody,
        );

        $client = $this->createClient($settings, $httpClient, $requestFactory, $streamFactory);

        $this->expectException(OAuthTokenExchangeException::class);

        $client->handleCallback('expected-state', 'the-code');
    }

    public function testRefreshAccessTokenPersistsRotatedRefreshTokenBeforeNewAccessToken(): void
    {
        $settings = $this->createSettingsStore([
            'oauth_access_token' => 'old-access',
            'oauth_refresh_token' => 'old-refresh',
        ]);

        $capturedBody = null;
        [$httpClient, $requestFactory, $streamFactory] = $this->createHttpStack(
            200,
            ['access_token' => 'new-access', 'refresh_token' => 'new-refresh'],
            $capturedBody,
        );

        $client = $this->createClient($settings, $httpClient, $requestFactory, $streamFactory);

        $client->refreshAccessToken();

        parse_str((string) $capturedBody, $sentParams);
        self::assertSame('refresh_token', $sentParams['grant_type']);
        self::assertSame('old-refresh', $sentParams['refresh_token']);

        self::assertCount(2, $settings->writes);
        self::assertSame('new-refresh', $settings->writes[0]['oauth_refresh_token']);
        // The rotated refresh token is safely on disk in the first write,
        // before the second write even touches the access token.
        self::assertSame('old-access', $settings->writes[0]['oauth_access_token']);
        self::assertSame('new-access', $settings->writes[1]['oauth_access_token']);
        self::assertSame('new-refresh', $settings->writes[1]['oauth_refresh_token']);
    }

    public function testRefreshAccessTokenThrowsWhenNoRefreshTokenStored(): void
    {
        $settings = $this->createSettingsStore();
        $client = $this->createClient($settings);

        $this->expectException(\LogicException::class);

        $client->refreshAccessToken();
    }

    public function testAccessTokenReturnsNullBeforeAuthorization(): void
    {
        $settings = $this->createSettingsStore();
        $client = $this->createClient($settings);

        self::assertNull($client->accessToken());
    }

    /**
     * @param array<string, mixed> $initial
     */
    private function createSettingsStore(array $initial = []): SettingsStoreInterface
    {
        return new class($initial) implements SettingsStoreInterface {
            /** @var array<string, mixed> */
            public array $data;

            /** @var list<array<string, mixed>> */
            public array $writes = [];

            /**
             * @param array<string, mixed> $data
             */
            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function read(): array
            {
                return $this->data;
            }

            public function write(array $settings): void
            {
                $this->data = $settings;
                $this->writes[] = $settings;
            }
        };
    }

    /**
     * @param array<string, mixed> $responsePayload
     *
     * @return array{0: ClientInterface, 1: RequestFactoryInterface, 2: StreamFactoryInterface}
     */
    private function createHttpStack(int $statusCode, array $responsePayload, ?string &$capturedBody): array
    {
        $requestBodyStream = $this->createMock(StreamInterface::class);

        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $streamFactory->method('createStream')
            ->willReturnCallback(function (string $content) use (&$capturedBody, $requestBodyStream) {
                $capturedBody = $content;

                return $requestBodyStream;
            });

        $request = $this->createMock(RequestInterface::class);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);

        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('__toString')->willReturn(json_encode($responsePayload, \JSON_THROW_ON_ERROR));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($responseBody);

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn($response);

        return [$httpClient, $requestFactory, $streamFactory];
    }

    private function createClient(
        SettingsStoreInterface $settings,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ): AbstractOAuthClient {
        $httpClient ??= $this->createMock(ClientInterface::class);
        $requestFactory ??= $this->createMock(RequestFactoryInterface::class);
        $streamFactory ??= $this->createMock(StreamFactoryInterface::class);

        return new class($httpClient, $requestFactory, $streamFactory, $settings) extends AbstractOAuthClient {
            protected function authorizeEndpoint(): string
            {
                return 'https://vendor.example/oauth/authorize';
            }

            protected function tokenEndpoint(): string
            {
                return 'https://vendor.example/oauth/token';
            }

            protected function clientId(): string
            {
                return 'test-client-id';
            }

            protected function clientSecret(): ?string
            {
                return null;
            }

            protected function scopes(): array
            {
                return ['read', 'write'];
            }

            protected function pkceMethod(): string
            {
                return 'S256';
            }

            protected function callbackPath(): string
            {
                return '/oauth/test/callback';
            }
        };
    }
}
