// koh/sylius-webmcp — registers Sylius storefront actions as WebMCP tools so an
// in-browser AI agent can act on the shop. WebMCP is a W3C draft (pre-1.0); the
// API is exposed as `navigator.modelContext` (Chrome origin trial) with a
// `document.modelContext` variant in the spec text. Requires a secure context
// (HTTPS) and the "tools" permissions-policy (default ['self']).
(function () {
  'use strict';

  var ctx = (typeof navigator !== 'undefined' && navigator.modelContext)
    || (typeof document !== 'undefined' && document.modelContext);
  if (!ctx || typeof ctx.registerTool !== 'function') {
    return; // WebMCP not available in this browser.
  }

  ctx.registerTool({
    name: 'add_to_cart',
    description: 'Add a product variant to the current shopping cart.',
    inputSchema: {
      type: 'object',
      properties: {
        productVariant: { type: 'string', description: 'Sylius product variant IRI or code.' },
        quantity: { type: 'integer', minimum: 1, default: 1 }
      },
      required: ['productVariant']
    },
    annotations: { readOnlyHint: false },
    execute: async function (input) {
      // Sylius Shop API: carts are orders in "cart" state, keyed by tokenValue.
      // The storefront exposes the active cart token via a meta tag it renders.
      var token = (document.querySelector('meta[name="sylius-cart-token"]') || {}).content;
      var base = (document.querySelector('meta[name="sylius-shop-api"]') || {}).content || '/api/v2/shop';
      if (!token) {
        var created = await fetch(base + '/orders', {
          method: 'POST',
          headers: { 'Content-Type': 'application/ld+json', 'Accept': 'application/ld+json' },
          body: JSON.stringify({ localeCode: document.documentElement.lang || 'en_US' })
        }).then(function (r) { return r.json(); });
        token = created.tokenValue;
      }
      var res = await fetch(base + '/orders/' + encodeURIComponent(token) + '/items', {
        method: 'POST',
        headers: { 'Content-Type': 'application/ld+json', 'Accept': 'application/ld+json' },
        body: JSON.stringify({ productVariant: input.productVariant, quantity: input.quantity || 1 })
      }).then(function (r) { return r.json(); });
      return { ok: true, cart: token, result: res };
    }
  });
})();
