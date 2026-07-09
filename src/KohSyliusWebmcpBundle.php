<?php

declare(strict_types=1);

namespace Koh\SyliusWebmcp;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Yaml\Yaml;

final class KohSyliusWebmcpBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(\dirname(__DIR__) . '/config/services.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Register the WebMCP storefront hook without the app having to import it.
        $hooks = Yaml::parseFile(\dirname(__DIR__) . '/config/sylius_twig_hooks.yaml');
        if (isset($hooks['sylius_twig_hooks'])) {
            $builder->prependExtensionConfig('sylius_twig_hooks', $hooks['sylius_twig_hooks']);
        }
    }
}
