<?php

namespace Avoo\DemoBundle;

use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class AvooDemoBundle
 */
class AvooDemoBundle extends Bundle
{
    /**
     * Return array with currently supported drivers.
     *
     * @return array
     */
    public static function getSupportedDrivers()
    {
        return array(
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getBundlePrefix()
    {
        return 'avoo_demo';
    }
}
