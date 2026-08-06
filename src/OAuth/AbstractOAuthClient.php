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

namespace AnimeDb\PluginContracts\OAuth;

use AnimeDb\PluginContracts\Settings\SettingsStoreInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Thin base class for a plugin-side OAuth 2.0 Authorization Code + PKCE
 * client, so that a source plugin (Shikimori, MyAnimeList, ...) does not
 * reimplement PKCE/state handling/token exchange on its own.
 *
 * Deliberately built on the host-provided PSR-18 client (constructor
 * injection, same as {@see \AnimeDb\PluginContracts\Llm\LlmServiceInterface})
 * plus PSR-17 request/stream factories, not an external OAuth library:
 * a library such as `league/oauth2-client` drags its own HTTP client
 * (Guzzle) into every consumer of this contracts package, defeating the
 * point of a plugin shipping without its own `vendor/`.
 *
 * This class is pure flow logic — it has no notion of an HTTP request or
 * response *to the plugin's own callback route*. It deliberately does not
 * type-hint any Symfony HTTP type: doing so would make `symfony/http-foundation`
 * a dependency of this contracts package, which the project's "no runtime
 * framework dependency" rule forbids. A plugin wires this class into its own
 * thin routes:
 *
 * ```php
 * // GET /oauth/my-source/start
 * public function start(): RedirectResponse
 * {
 *     return new RedirectResponse($this->oauth->buildAuthorizeUrl());
 * }
 *
 * // GET /oauth/my-source/callback?state=...&code=...
 * public function callback(Request $request): Response
 * {
 *     $this->oauth->handleCallback($request->query->get('state'), $request->query->get('code'));
 *
 *     return new Response('Authorized, you can close this tab.');
 * }
 * ```
 *
 * A plugin whose vendor needs a non-standard flow does not use this class at
 * all (escape hatch) — it owns the flow itself through its own routes.
 *
 * Security properties a subclass cannot weaken:
 *
 * - {@see self::authorizeEndpoint()} and {@see self::tokenEndpoint()} are
 *   vendor domains a subclass hardcodes, never derived from a user-editable
 *   `api_endpoint` setting. If they were, a user tricked into pointing
 *   `api_endpoint` at an attacker's host (social engineering) would leak a
 *   long-lived refresh token to that host the next time
 *   {@see self::refreshAccessToken()} runs. Only *data* endpoints are meant
 *   to be user-configurable, never the auth ones.
 * - `state` is generated in {@see self::buildAuthorizeUrl()} and always
 *   compared in {@see self::handleCallback()}, with `hash_equals()`, before
 *   any token exchange happens. This is what makes it safe for the plugin's
 *   callback route to be an unauthenticated local HTTP endpoint: anything
 *   running on the same machine can hit it, so the callback must prove it
 *   is completing the session this class started, not just presenting some
 *   `code`.
 * - The redirect URI's origin is read from `$_SERVER['OAUTH_CALLBACK_ORIGIN']`
 *   (`$_SERVER`, not `getenv()`, so it is only what the host process set for
 *   this request — nothing a plugin's own code can override at runtime),
 *   populated by the host application, not this package. It is expected to
 *   already be a `http://127.0.0.1:<port>` origin (the literal loopback IP,
 *   per RFC 8252 §8.3 — not `localhost`, which depends on the system
 *   resolver) with no trailing slash; this class only concatenates it with
 *   {@see self::callbackPath()}, it does not construct or validate the
 *   origin itself.
 * - On {@see self::refreshAccessToken()}, a rotated refresh token (MyAnimeList
 *   rotates on every refresh) is persisted to {@see SettingsStoreInterface}
 *   in its own `update()` call *before* the new access token is added and
 *   persisted in a second `update()` call. If the host process crashes
 *   between the two, the new refresh token is already safe on disk; nothing
 *   is lost, unlike storing both in one call that could fail after the
 *   refresh token was already consumed by the vendor.
 */
abstract class AbstractOAuthClient
{
    private const SETTINGS_KEY_STATE = 'oauth_state';
    private const SETTINGS_KEY_CODE_VERIFIER = 'oauth_code_verifier';
    private const SETTINGS_KEY_ACCESS_TOKEN = 'oauth_access_token';
    private const SETTINGS_KEY_REFRESH_TOKEN = 'oauth_refresh_token';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly SettingsStoreInterface $settings,
    ) {
    }

    /**
     * The vendor's authorization endpoint, e.g. `https://shikimori.one/oauth/authorize`.
     *
     * Hardcoded by the subclass — never derived from a user-editable setting.
     */
    abstract protected function authorizeEndpoint(): string;

    /**
     * The vendor's token endpoint, e.g. `https://shikimori.one/oauth/token`.
     *
     * Hardcoded by the subclass — never derived from a user-editable setting.
     */
    abstract protected function tokenEndpoint(): string;

    abstract protected function clientId(): string;

    /**
     * @return string|null the client secret, or null for a vendor whose
     *                     public client (PKCE-only) needs none
     */
    abstract protected function clientSecret(): ?string;

    /**
     * @return string[] scopes to request, e.g. ['user_rates']
     */
    abstract protected function scopes(): array;

    /**
     * The PKCE code challenge method this vendor accepts.
     *
     * @return 'S256'|'plain'
     */
    abstract protected function pkceMethod(): string;

    /**
     * Path (with leading slash, no host) of the plugin's own callback route,
     * e.g. `/oauth/my-source/callback`. Combined with the host-provided
     * callback origin to form the `redirect_uri`.
     */
    abstract protected function callbackPath(): string;

    /**
     * Build the vendor authorize URL to send the user's browser to, and
     * persist the `state` and PKCE code verifier this flow needs to verify
     * and complete in {@see self::handleCallback()}.
     *
     * The plugin's settings page must open this URL as a top-level
     * navigation (a plain link/full-page redirect), not through an
     * HTMX-style in-page swap — see
     * {@see \AnimeDb\PluginContracts\Settings\SettingsPageInterface}.
     */
    public function buildAuthorizeUrl(): string
    {
        $state = bin2hex(random_bytes(32));
        $codeVerifier = self::base64UrlEncode(random_bytes(32));

        $this->settings->update(static function (array $settings) use ($state, $codeVerifier): array {
            $settings[self::SETTINGS_KEY_STATE] = $state;
            $settings[self::SETTINGS_KEY_CODE_VERIFIER] = $codeVerifier;

            return $settings;
        });

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'scope' => implode(' ', $this->scopes()),
            'state' => $state,
            'code_challenge' => $this->codeChallenge($codeVerifier),
            'code_challenge_method' => $this->pkceMethod(),
        ]);

        return $this->authorizeEndpoint().'?'.$query;
    }

    /**
     * Complete the flow started by {@see self::buildAuthorizeUrl()}: verify
     * `$state` against the one persisted for this session, exchange `$code`
     * (with the matching PKCE verifier) for a token at
     * {@see self::tokenEndpoint()}, and persist the access/refresh tokens.
     *
     * The plugin's callback route calls this with the `state` and `code`
     * query parameters it received, and does nothing else security-relevant
     * itself — this method is where the state comparison that makes the
     * unauthenticated loopback callback safe actually happens.
     *
     * @throws OAuthStateMismatchException if $state does not match the one
     *                                     this class persisted, or no OAuth session is in progress
     * @throws OAuthTokenExchangeException if the token endpoint rejects the
     *                                     exchange or answers with something that is not a usable token response
     * @throws ClientExceptionInterface    if the underlying PSR-18 HTTP transport fails
     */
    public function handleCallback(string $state, string $code): void
    {
        $expectedState = null;
        $codeVerifier = null;

        // Single-use: the session's state/verifier are consumed here
        // regardless of outcome, so a stale or already-used callback can't
        // be replayed.
        $this->settings->update(function (array $settings) use (&$expectedState, &$codeVerifier): array {
            $expectedState = $settings[self::SETTINGS_KEY_STATE] ?? null;
            $codeVerifier = $settings[self::SETTINGS_KEY_CODE_VERIFIER] ?? null;

            unset($settings[self::SETTINGS_KEY_STATE], $settings[self::SETTINGS_KEY_CODE_VERIFIER]);

            return $settings;
        });

        if (
            !is_string($expectedState)
            || !is_string($codeVerifier)
            || $expectedState === ''
            || !hash_equals($expectedState, $state)
        ) {
            throw new OAuthStateMismatchException('OAuth callback state does not match the pending authorization session.');
        }

        $token = $this->requestToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
            'client_id' => $this->clientId(),
            'code_verifier' => $codeVerifier,
        ]);

        $this->settings->update(static function (array $settings) use ($token): array {
            $settings[self::SETTINGS_KEY_ACCESS_TOKEN] = $token['access_token'];
            if (isset($token['refresh_token']) && is_string($token['refresh_token'])) {
                $settings[self::SETTINGS_KEY_REFRESH_TOKEN] = $token['refresh_token'];
            }

            return $settings;
        });
    }

    /**
     * Exchange the stored refresh token for a new access token.
     *
     * If the vendor rotates refresh tokens (MyAnimeList does, on every use),
     * the new refresh token is written to {@see SettingsStoreInterface}
     * *before* the new access token is added and written in a second
     * `update()` call — so a crash between the two does not strand the
     * plugin with a consumed refresh token and nothing to replace it.
     *
     * @throws \LogicException             if no refresh token has been stored yet
     *                                     (the flow hasn't completed {@see self::handleCallback()})
     * @throws OAuthTokenExchangeException if the token endpoint rejects the refresh
     * @throws ClientExceptionInterface    if the underlying PSR-18 HTTP transport fails
     */
    public function refreshAccessToken(): void
    {
        $settings = $this->settings->read();
        $refreshToken = $settings[self::SETTINGS_KEY_REFRESH_TOKEN] ?? null;

        if (!is_string($refreshToken) || $refreshToken === '') {
            throw new \LogicException('No OAuth refresh token stored; complete handleCallback() before refreshing.');
        }

        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId(),
        ];

        $token = $this->requestToken($params);

        $newRefreshToken = $token['refresh_token'] ?? null;

        // Persist the (possibly rotated) refresh token on its own, before
        // the new access token is even added to the settings, let alone
        // persisted.
        $this->settings->update(static function (array $settings) use ($newRefreshToken): array {
            if (is_string($newRefreshToken) && $newRefreshToken !== '') {
                $settings[self::SETTINGS_KEY_REFRESH_TOKEN] = $newRefreshToken;
            }

            return $settings;
        });

        $accessToken = $token['access_token'];
        $this->settings->update(static function (array $settings) use ($accessToken): array {
            $settings[self::SETTINGS_KEY_ACCESS_TOKEN] = $accessToken;

            return $settings;
        });
    }

    /**
     * The access token from the most recent successful exchange or refresh,
     * or null if the plugin has not completed the OAuth flow yet.
     */
    public function accessToken(): ?string
    {
        $token = $this->settings->read()[self::SETTINGS_KEY_ACCESS_TOKEN] ?? null;

        return is_string($token) ? $token : null;
    }

    /**
     * Literal `127.0.0.1` loopback origin (RFC 8252 §8.3), read verbatim
     * from the host-provided environment — never resolved from a hostname,
     * never taken from anything a plugin controls.
     */
    private function redirectUri(): string
    {
        $origin = $_SERVER['OAUTH_CALLBACK_ORIGIN'] ?? null;

        if (!is_string($origin) || $origin === '') {
            throw new \RuntimeException('OAUTH_CALLBACK_ORIGIN is not set; the host application must provide it.');
        }

        return rtrim($origin, '/').$this->callbackPath();
    }

    /**
     * @throws \UnhandledMatchError if {@see self::pkceMethod()} returns anything
     *                              other than 'S256' or 'plain'
     */
    private function codeChallenge(string $codeVerifier): string
    {
        return match ($this->pkceMethod()) {
            'S256' => self::base64UrlEncode(hash('sha256', $codeVerifier, true)),
            'plain' => $codeVerifier,
        };
    }

    /**
     * POST $params as `application/x-www-form-urlencoded` to the token
     * endpoint (adding the client secret when this vendor has one) and
     * decode the JSON token response.
     *
     * @param array<string, string> $params
     *
     * @return array<string, mixed>
     */
    private function requestToken(array $params): array
    {
        $secret = $this->clientSecret();
        if ($secret !== null) {
            $params['client_secret'] = $secret;
        }

        $body = $this->streamFactory->createStream(http_build_query($params));

        $request = $this->requestFactory
            ->createRequest('POST', $this->tokenEndpoint())
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json')
            ->withBody($body);

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new OAuthTokenExchangeException(sprintf('OAuth token endpoint responded with HTTP %d.', $response->getStatusCode()));
        }

        try {
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new OAuthTokenExchangeException('OAuth token endpoint response is not valid JSON.', previous: $exception);
        }

        if (!is_array($data) || !isset($data['access_token']) || !is_string($data['access_token'])) {
            throw new OAuthTokenExchangeException('OAuth token endpoint response is missing "access_token".');
        }

        return $data;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
