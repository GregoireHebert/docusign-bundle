<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection;

use DocusignBundle\Adapter\AdapterDefinitionFactory;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class DocusignExtension extends Extension
{

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);
        $this->registerConfiguration($container, $config);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    private function registerConfiguration(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('docusign.accessToken', $config['accessToken']);
        $container->setParameter('docusign.accountId', $config['accountId']);
        $container->setParameter('docusign.defaultSignerName', $config['defaultSignerName']);
        $container->setParameter('docusign.defaultSignerEmail', $config['defaultSignerEmail']);
        $container->setParameter('docusign.apiURI', $config['apiURI']);
        $container->setParameter('docusign.callBackRouteName', $config['callbackRouteName']);
        $container->setParameter('docusign.webHookRouteName', $config['webHookRouteName']);
    }

}
