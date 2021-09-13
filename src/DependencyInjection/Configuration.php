<?php

declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('comvation_sylius_payrexx_checkout__plugin');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
