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
     * Config for other extensions has to be prepended: by the time loadExtension()
     * runs, the twig and sylius_twig_hooks extensions may already have read
     * theirs, and a prependExtensionConfig() from there is silently dropped.
     * That is how the hook used to depend on the shop importing
     * config/sylius_twig_hooks.yaml by hand, and the script never rendered
     * when it did not.
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/sylius_twig_hooks.yaml');

        // Exposed to the template so the script never has to guess a path. The
        // bundle's own config is not processed yet; read the raw value, last wins.
        $apiBase = '/api/agent';
        foreach ($builder->getExtensionConfig($this->extensionAlias) as $config) {
            if (isset($config['api_base']) && \is_string($config['api_base'])) {
                $apiBase = $config['api_base'];
            }
        }
        $builder->prependExtensionConfig('twig', [
            'globals' => ['koh_webmcp_api_base' => $apiBase],
        ]);
    }

    /**
     * @param array<mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('koh_webmcp.api_base', (string) ($config['api_base'] ?? '/api/agent'));

        $container->import('../config/services.php');
    }
}
