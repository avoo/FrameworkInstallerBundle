<?php

namespace Avoo\DemoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('avoo_demo');

        $this->addClassesSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Add classes section
     *
     * @param ArrayNodeDefinition $node
     */
    public function addClassesSection(ArrayNodeDefinition $node)
    {
        $node
            ->addDefaultsIfNotSet()
            ->children()
                // Driver used by the resource bundle
                ->scalarNode('driver')->isRequired()->cannotBeEmpty()->end()
                // Object manager used by the resource bundle, if not specified "default" will used
                ->scalarNode('manager')->defaultValue('default')->end()
                // Validation groups used by the form component
                ->arrayNode('validation_groups')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('news')->defaultValue('AvooDemoNews')->end()
                    ->end()
                ->end()

                // Configure the template namespace used by each resource
                ->arrayNode('templates')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('news')->defaultValue('AvooDemoBundle:News')->end()
                    ->end()
                ->end()

                // The resources
                ->arrayNode('classes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('news')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->end()
                                ->scalarNode('controller')->defaultValue('Avoo\DemoBundle\Controller\NewsController')->end()
                                ->scalarNode('repository')->defaultValue('Avoo\DemoBundle\Doctrine\ORM\NewsRepository')->end()
                                ->scalarNode('form')->defaultValue('Avoo\DemoBundle\Form\Type\NewsFormType')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
