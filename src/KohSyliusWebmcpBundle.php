<?php

// SPDX-FileCopyrightText: 2026 Kai Ole Hartwig
// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace Koh\SyliusWebmcp;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Exposes shop actions to in-browser agents through the WebMCP API.
 */
final class KohSyliusWebmcpBundle extends AbstractBundle
{
    protected string $extensionAlias = 'koh_webmcp';

    /** Resources/ and config/ sit at the package root, not under src/. */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('api_base')
                    ->defaultValue('/api/agent')
                    ->info('Read-only agent API the tools call. Must match a route the shop serves — koh/sylius-acp provides one.')
                ->end()
            ->end();
    }

    /**
     * @param array<mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $apiBase = (string) ($config['api_base'] ?? '/api/agent');

        $builder->setParameter('koh_webmcp.api_base', $apiBase);

        // Exposed to the template so the script never has to guess a path.
        $builder->prependExtensionConfig('twig', [
            'globals' => ['koh_webmcp_api_base' => $apiBase],
        ]);

        $container->import('../config/services.php');
    }
}
