<!--
SPDX-FileCopyrightText: 2026 Kai Ole Hartwig
SPDX-License-Identifier: MIT
-->

# sylius-webmcp

> WebMCP for the Sylius storefront (agentic tools in web pages)

WebMCP for the Sylius storefront — MCP tools embedded in storefront web pages (analogous to `moselwal/webmcp` for TYPO3). No public Sylius WebMCP package exists yet; this is ours.

## What it does

On every storefront page, the bundle renders a meta tag with the agent API base
and a small script. In a browser that offers WebMCP (`document.modelContext`,
or `navigator.modelContext` in Chrome's origin trial), the script registers one
tool for in-browser agents:

| Tool | Input | Behaviour |
|---|---|---|
| `search_products` | `query` (required), `limit` (1–50, default 10) | `GET {api_base}/feed?limit=…&q=…` without cookies; returns the JSON response |

The tool is read-only and marked `readOnlyHint` and `untrustedContentHint`:
catalogue text is data for the agent, not instructions. Nothing changes the cart.
Without WebMCP in the browser, the script does nothing.

The tool needs an agent API that serves `{api_base}/feed`. Install
[`koh/sylius-acp`](https://github.com/ohartwig/sylius-acp), which serves
`/api/agent/feed`. Its feed does not filter by `q` yet; the tool then returns
the newest products.

## Install

Requires PHP 8.3 or later and Sylius 2.2 or 2.3 (Twig Hooks).

```bash
composer require koh/sylius-webmcp koh/sylius-acp
```

Register the bundle (see the `koh/sylius-acp` README for its own entries):

```php
// config/bundles.php
return [
    // …
    Koh\SyliusWebmcp\KohSyliusWebmcpBundle::class => ['all' => true],
];
```

The bundle registers its Twig hook itself: the script renders at the end of the
shop's base template (`sylius_shop.base#javascripts`) as soon as the bundle is
enabled.

Install the script into `public/bundles/` and clear the cache:

```bash
bin/console assets:install
bin/console cache:clear
```

Check that a storefront page carries the tag and the script:

```bash
curl -s https://shop.example.com/en_US/ | grep -E 'koh-agent-api|webmcp.js'
```

```html
<meta name="koh-agent-api" content="/api/agent">
<script src="/bundles/kohsyliuswebmcp/webmcp.js" defer></script>
```

If a theme overrides the base template's JavaScript block, check the hook name
with `bin/console debug:twig-hook sylius_shop.base` and adjust the import.

## Configure

```yaml
# config/packages/koh_webmcp.yaml
koh_webmcp:
    api_base: '/api/agent'   # must match a route the shop serves
```

## Decommission

Remove the `composer require`, the `config/bundles.php` entry,
`config/packages/koh_webmcp.yaml` and `public/bundles/kohsyliuswebmcp/`.

## Security

Report vulnerabilities privately — see [SECURITY.md](SECURITY.md). Advisories
are published as CSAF 2.0 at <https://ole-hartwig.eu/.well-known/csaf/>.

## Licence

MIT — see [LICENSE](LICENSE).

---

Part of the **AI Ready Platform** (content commerce: Sylius × TYPO3). Status: basic skeleton.

<!-- release: enable initial release -->
