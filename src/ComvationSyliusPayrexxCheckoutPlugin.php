<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin;

// Symfony 6.1+ style, see
//  https://symfony.com/blog/new-in-symfony-6-1-simpler-bundle-extension-and-configuration

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class ComvationSyliusPayrexxCheckoutPlugin extends AbstractBundle
{
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        $container->import('../config/services.yaml');
    }
}
