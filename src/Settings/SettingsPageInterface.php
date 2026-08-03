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

namespace AnimeDb\PluginContracts\Settings;

/**
 * A plugin's own settings page, embedded into the host's settings area
 * (the Chrome `options_ui` model).
 *
 * Unlike widget interfaces, this does not extend
 * {@see \AnimeDb\PluginContracts\ExternalIdResolutionInterface}: a settings
 * page is not per-record, so resolving an external id has no meaning here.
 *
 * The contract covers only rendering. All interactivity — saving the form,
 * an "Authorize" button, OAuth redirect/callback — goes through the
 * plugin's own routes (declared in its `plugin-routing.yaml`); `render()`
 * returns an HTMX form targeting those routes. The core does not implement
 * OAuth on the plugin's behalf; a plugin that needs the standard Authorization
 * Code + PKCE flow builds it on
 * {@see \AnimeDb\PluginContracts\OAuth\AbstractOAuthClient} instead of
 * reimplementing it.
 *
 * The "Authorize" button itself must be a plain top-level navigation (a link
 * or a form submit that reloads the page), never an HTMX in-page swap: the
 * desktop host's Electron shell intercepts the browser-bound OAuth redirect
 * only on a real top-level navigation, not on an HTMX-driven fetch, so an
 * HTMX-swapped button would silently fail to open the authorize page.
 *
 * A plugin obtains a {@see SettingsStoreInterface} and any other host
 * services through the same constructor injection as other role
 * interfaces, and renders through the host's Twig environment (locale and
 * `csrf_token()` are ambient there). The host wraps `render()` in a
 * try/catch so that one broken settings page does not take down the whole
 * settings area. Exactly one settings page per plugin.
 */
interface SettingsPageInterface
{
    /**
     * Render the plugin's settings page.
     *
     * @return string rendered page markup as a raw HTML string
     */
    public function render(): string;
}
