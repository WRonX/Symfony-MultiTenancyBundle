<?php
/**
 * Copyright © 2017 WRonX <wronx[at]wronx.net> https://github.com/WRonX
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See http://www.wtfpl.net/ for more details.
 */
namespace WRonX\MultiTenancyBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class WRonXMultiTenancyExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);
        
        // If enabled, injecting wrapper class into Doctrine config tree
        if($config['enabled'] === true)
            $container->prependExtensionConfig('doctrine', ['dbal' => ['wrapper_class' => "WRonX\\MultiTenancyBundle\\Connection\\ConnectionWrapper"]]);
    }
    
    public function getAlias()
    {
        return "wronx_multitenancy";
    }
    
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('wronx_multitenancy.enabled', $config['enabled']);
        
        if($config['enabled'])
        {
            $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
            $loader->load('services.yml');
        }
    }
}
