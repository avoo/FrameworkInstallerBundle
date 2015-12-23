<?php

namespace Avoo\DemoBundle\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class AvooDemoExtension
 */
class AvooDemoExtension extends AbstractResourceExtension
{
    protected $applicationName = 'avoo_demo';

    protected $configDirectory = '/../Resources/config/container';

    protected $configFiles = array(
        'services',
        'forms',
    );

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $this->configure(
            $config,
            new Configuration(),
            $container,
            self::CONFIGURE_LOADER | self::CONFIGURE_DATABASE | self::CONFIGURE_PARAMETERS | self::CONFIGURE_VALIDATORS
        );
    }
}
