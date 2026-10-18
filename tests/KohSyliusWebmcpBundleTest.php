<?php

// SPDX-FileCopyrightText: 2026 Kai Ole Hartwig
// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace Koh\SyliusWebmcp\Tests;

use Koh\SyliusWebmcp\KohSyliusWebmcpBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

/**
 * Registering the bundle is the whole installation: the Twig hook and the
 * template global arrive without the shop importing anything.
 */
final class KohSyliusWebmcpBundleTest extends TestCase
{
    public function testTheShopHookIsPrepended(): void
    {
        $hooks = $this->prepended([])->getExtensionConfig('sylius_twig_hooks');

        self::assertSame(
            '@KohSyliusWebmcp/_webmcp_script.html.twig',
            $hooks[0]['hooks']['sylius_shop.base#javascripts']['webmcp']['template'] ?? null,
        );
    }

    public function testTheTemplateGetsTheDefaultApiBase(): void
    {
        $twig = $this->prepended([])->getExtensionConfig('twig');

        self::assertSame('/api/agent', $twig[0]['globals']['koh_webmcp_api_base'] ?? null);
    }

    public function testTheTemplateGetsTheConfiguredApiBase(): void
    {
        $twig = $this->prepended([['api_base' => '/agents/v2']])->getExtensionConfig('twig');

        self::assertSame('/agents/v2', $twig[0]['globals']['koh_webmcp_api_base'] ?? null);
    }

    public function testTheApiBaseParameterDefaults(): void
    {
        self::assertSame('/api/agent', $this->loaded([])->getParameter('koh_webmcp.api_base'));
    }

    public function testTheApiBaseParameterFollowsTheConfig(): void
    {
        self::assertSame('/agents/v2', $this->loaded([['api_base' => '/agents/v2']])->getParameter('koh_webmcp.api_base'));
    }

    public function testTheBundleLivesAtThePackageRoot(): void
    {
        self::assertFileExists((new KohSyliusWebmcpBundle())->getPath().'/config/sylius_twig_hooks.yaml');
    }

    /** @param list<array<string, mixed>> $configs */
    private function loaded(array $configs): ContainerBuilder
    {
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.environment', 'test');
        $builder->setParameter('kernel.build_dir', sys_get_temp_dir());
        $extension = (new KohSyliusWebmcpBundle())->getContainerExtension();
        self::assertNotNull($extension);
        $extension->load($configs, $builder);

        return $builder;
    }

    /** @param list<array<string, mixed>> $configs */
    private function prepended(array $configs): ContainerBuilder
    {
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.environment', 'test');
        $builder->setParameter('kernel.build_dir', sys_get_temp_dir());
        $extension = (new KohSyliusWebmcpBundle())->getContainerExtension();
        self::assertInstanceOf(PrependExtensionInterface::class, $extension);
        $builder->registerExtension($extension);
        foreach ($configs as $config) {
            $builder->loadFromExtension('koh_webmcp', $config);
        }
        $extension->prepend($builder);

        return $builder;
    }
}
