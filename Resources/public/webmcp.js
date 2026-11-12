// SPDX-FileCopyrightText: 2026 Kai Ole Hartwig
// SPDX-License-Identifier: MIT
//
// Registers this shop's read-only actions as WebMCP tools, so an in-browser
// agent can search the catalogue on the page the shopper is already looking at.
//
// Read-only on purpose. An earlier version registered add_to_cart against the
// Sylius Shop API; that API is commonly disabled outside development, so the
// tool was advertised and never worked. Beyond that, a page-embedded agent
// mutating a cart is a decision for the shop to make deliberately — not
// something a discovery bundle should switch on by installing it.
//
// The endpoint comes from a meta tag rendered server-side, so this file never
// guesses a path.
(function () {
  'use strict';

  // The 2026 draft moved the API to document.modelContext; Chrome's origin
  // trial still exposes navigator.modelContext.
  var ctx =
    (typeof document !== 'undefined' && document.modelContext) ||
    (typeof navigator !== 'undefined' && navigator.modelContext);

  if (!ctx || typeof ctx.registerTool !== 'function') {
    return; // WebMCP unavailable — nothing to do, and nothing to log.
  }

  var meta = document.querySelector('meta[name="koh-agent-api"]');
  var base = meta && meta.content ? meta.content.replace(/\/$/, '') : null;
  if (!base) {
    return; // No agent API on this shop.
  }

  var controller = new AbortController();

  var registration = ctx.registerTool(
    {
      name: 'search_products',
      title: 'Search products',
      description:
        'Search this shop catalogue. Returns SKU-level items with price, currency and availability.',
      inputSchema: {
        type: 'object',
        properties: {
          query: { type: 'string', description: 'Search phrase.' },
          limit: { type: 'integer', minimum: 1, maximum: 50, default: 10 }
        },
        required: ['query']
      },
      // Nothing is required: a failed search answers with `error` alone.
      outputSchema: {
        type: 'object',
        properties: {
          items: {
            type: 'array',
            items: {
              type: 'object',
              properties: {
                id: { type: 'string', description: 'SKU.' },
                item_group_id: { type: 'string', description: 'Product the SKU belongs to.' },
                title: { type: 'string' },
                description: { type: 'string' },
                link: { type: 'string', format: 'uri' },
                availability: { type: 'string' },
                condition: { type: 'string' },
                price: { type: 'string', description: 'Gross price in minor units (cents).' },
                price_currency: { type: 'string' },
                age_restriction: { type: 'integer' }
              }
            }
          },
          page: { type: 'integer' },
          limit: { type: 'integer' },
          count: { type: 'integer' },
          error: { type: 'string' }
        }
      },
      annotations: {
        readOnlyHint: true,
        // The catalogue carries editor-authored text. An agent should treat it
        // as data, not as instructions.
        untrustedContentHint: true
      },
      // The draft hands execute a second argument carrying an AbortSignal that
      // fires when the agent cancels the call; browsers from before that
      // revision pass nothing.
      execute: async function (input, options) {
        var url =
          base +
          '/feed?limit=' +
          encodeURIComponent(String(input.limit || 10)) +
          '&q=' +
          encodeURIComponent(input.query);

        var response = await fetch(url, {
          headers: { Accept: 'application/json' },
          credentials: 'omit',
          signal: options && options.signal ? options.signal : undefined
        });

        if (!response.ok) {
          return { error: 'Search failed with status ' + response.status };
        }

        return await response.json();
      }
    },
    { signal: controller.signal }
  );

  // registerTool returns a promise in the current draft (and undefined in
  // older browsers). A refused registration — permissions policy, a name
  // already taken — is the browser's answer, not a page error: swallow it
  // rather than leave an unhandled rejection in the shop's console.
  if (registration && typeof registration.catch === 'function') {
    registration.catch(function () {});
  }

  // Withdraw the tools when the page goes away, so an agent is not left holding
  // a handle to a document that no longer exists.
  window.addEventListener('pagehide', function () {
    controller.abort();
  });
})();
