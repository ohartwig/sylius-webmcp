<?php

// SPDX-FileCopyrightText: 2026 Kai Ole Hartwig
// SPDX-License-Identifier: MIT

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

/**
 * composer-unused only sees symbols that PHP source references directly. A
 * Sylius bundle wires itself in through configuration, so the exclusion below
 * names the file that does it.
 */
return static function (Configuration $config): Configuration {
    return $config
        // The peer this bundle extends. config/sylius_twig_hooks.yaml registers
        // the storefront script under the `sylius_twig_hooks` extension against
        // the `sylius_shop.base#javascripts` hook - a Sylius 2.x mechanism, and
        // the bundle's only delivery path into the storefront. No PHP here
        // imports Sylius, and this require is what pins the bundle to 2.x.
        ->addNamedFilter(NamedFilter::fromString('sylius/sylius'));
};
