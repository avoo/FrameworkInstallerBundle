<?php

namespace Avoo\Bundle\InstallerBundle\Composer;

use Composer\Script\CommandEvent;
use Avoo\Bundle\InstallerBundle\Filesystem\Filesystem;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as BaseScriptHandler;

/**
 * Class ScriptHandler
 *
 * @author Jérémy Jégou <jejeavo@gmail.com>
 */
class ScriptHandler extends BaseScriptHandler
{
    /**
     * Avoo demo bundle install
     *
     * @param CommandEvent $event
     */
    public static function installAvooDemoBundle(CommandEvent $event)
    {
        $rootDir = getcwd();
        $options = self::getOptions($event);
        if (file_exists($rootDir.'/src/Avoo/DemoBundle')) {
            return;
        }
        if (!getenv('AVOO_DEMO')) {
            if (!$event->getIO()->askConfirmation('Would you like to install Avoo Demo bundle? [y/n] ', true)) {
                return;
            }
        }

        $event->getIO()->write('Installing the Avoo Demo bundle.');

        $appDir = $options['symfony-app-dir'];

        $kernelFile = $appDir . '/AppKernel.php';
        $configFile = $appDir . '/config/config_dev.yml';

        $fileSystem = new Filesystem();
        $fileSystem->mirror(__DIR__.'/../Resources/skeleton/avoo-demo-bundle', $rootDir.'/src', null, array('override' => true));

        $ref = '$bundles[] = new \Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();';
        $bundleDeclaration = "\$bundles[] = new \\Avoo\\DemoBundle\\AvooDemoBundle();";
        $content = file_get_contents($kernelFile);

        if (false === strpos($content, $bundleDeclaration)) {
            $updatedContent = str_replace($ref, $bundleDeclaration."\n            ".$ref, $content);
            if ($content === $updatedContent) {
                throw new \RuntimeException('Unable to patch %s.', $kernelFile);
            }

            $fileSystem->dumpFile($kernelFile, $updatedContent);
        }

        $ref = '- { resource: config.yml }';
        $configDeclaration = '- { resource: @AvooDemoBundle/Resources/config/config.yml }';
        $content = file_get_contents($configFile);

        if (false === strpos($content, $bundleDeclaration)) {
            $updatedContent = str_replace($ref, $ref . "\n    " . $configDeclaration, $content);
            if ($content === $updatedContent) {
                throw new \RuntimeException('Unable to patch %s.', $kernelFile);
            }

            $fileSystem->dumpFile($configFile, $updatedContent);
        }

        self::patchAvooDemoBundleConfiguration($appDir, $fileSystem);
    }

    /**
     * Avoo core bundle install
     *
     * @param CommandEvent $event
     */
    public static function installCoreBundle(CommandEvent $event)
    {
        $rootDir = getcwd();
        $options = self::getOptions($event);

        if (!$event->getIO()->askConfirmation('Would you like to install default core bundle (Recommended)? [y/n] ', true)) {
            return;
        }

        $applicationName = $event->getIO()->askAndValidate('Choose your application name: ', function($answer) {
            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $answer)) {
                throw new \InvalidArgumentException('The application name contains invalid characters.');
            }

            return ucfirst(strtolower($answer));
        });

        if (is_dir($rootDir.'/src/' . $applicationName . '/CoreBundle')) {
            return;
        }

        $event->getIO()->write('Installing Core bundle.');
        $appDir = $options['symfony-app-dir'];
        $bundleDir = $rootDir . '/src/' . $applicationName;

        $consoleDir = self::getConsoleDir($event, 'Install database');
        static::executeCommand($event, $consoleDir, 'doctrine:database:create');

        $fileSystem = new Filesystem();
        $fileSystem->setTwigEnvironment(self::getTwigEnvironment());

        self::buildComponentFiles($bundleDir . '/Component/', $applicationName, $fileSystem);
        self::buildCoreFiles($bundleDir . '/Bundle/CoreBundle/', $applicationName, $fileSystem);
        self::buildAppFiles(getcwd(), $applicationName, $fileSystem);

        $configFile = $appDir . '/config/config.yml';
        $ref = '- { resource: security.yml }';
        $configDeclaration = '- { resource: @' . $applicationName . 'CoreBundle/Resources/config/app/' . strtolower($applicationName) . '.yml }';
        $content = file_get_contents($configFile);

        if (false === strpos($content, $configDeclaration)) {
            $updatedContent = str_replace($ref, $ref . "\n    " . $configDeclaration, $content);
            if ($content === $updatedContent) {
                throw new \RuntimeException('Unable to patch %s.', $configFile);
            }

            $fileSystem->dumpFile($configFile, $updatedContent);
        }

        $kernelFile = $appDir . '/AppKernel.php';

        $fileSystem = new Filesystem();
        $ref = '$bundles = array(';
        $bundleDeclaration = 'new ' . $applicationName . '\\Bundle\\CoreBundle\\' . $applicationName . 'CoreBundle(),';
        $content = file_get_contents($kernelFile);

        if (false === strpos($content, $bundleDeclaration)) {
            $updatedContent = str_replace($ref, $ref . "\n            " . $bundleDeclaration."\n        ", $content);
            if ($content === $updatedContent) {
                throw new \RuntimeException('Unable to patch %s.', $kernelFile);
            }

            $fileSystem->dumpFile($kernelFile, $updatedContent);
        }
    }

    /**
     * Avoo core bundle install
     *
     * @param CommandEvent $event
     */
    public static function installBackendBundle(CommandEvent $event)
    {
        $rootDir = getcwd();
        $options = self::getOptions($event);

        if (!$event->getIO()->askConfirmation('Would you like to install default backend bundle (Recommended)? [y/n] ', true)) {
            return;
        }

        $applicationName = $event->getIO()->askAndValidate('Choose your application name: ', function($answer) {
            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $answer)) {
                throw new \InvalidArgumentException('The application name contains invalid characters.');
            }

            return ucfirst(strtolower($answer));
        });

        if (is_dir($rootDir.'/src/' . $applicationName . '/BackendBundle')) {
            return;
        }

        $event->getIO()->write('Installing Backend bundle.');
        $appDir = $options['symfony-app-dir'];
        $bundleDir = $rootDir . '/src/' . $applicationName;

        $fileSystem = new Filesystem();
        $fileSystem->setTwigEnvironment(self::getTwigEnvironment());

        self::buildBackendFiles($bundleDir . '/Bundle/BackendBundle/', $applicationName, $fileSystem);

        $routingPath = $rootDir . '/app/config/routing.yml';
        $namePrefix = strtolower($applicationName) . '_backend';
        $bundleName = $applicationName . 'BackendBundle';
        $content = file_get_contents($routingPath);
        $nodeDeclaration = <<<EOF
$namePrefix:
    resource: @$bundleName/Resources/config/routing/main.yml
    prefix: /administration

EOF;

        if (false === strpos($content, $namePrefix)) {
            $updatedContent = $nodeDeclaration . "\n" . $content;

            $fileSystem->dumpFile($routingPath, $updatedContent);
        }

        $configFile = $appDir . '/config/config.yml';
        $ref = '- { resource: security.yml }';
        $configDeclaration = '- { resource: @' . $applicationName . 'BackendBundle/Resources/config/app/' . strtolower($applicationName) . '.yml }';
        $content = file_get_contents($configFile);

        if (false === strpos($content, $configDeclaration)) {
            $updatedContent = str_replace($ref, $ref . "\n    " . $configDeclaration, $content);
            if ($content === $updatedContent) {
                throw new \RuntimeException('Unable to patch %s.', $configFile);
            }

            $fileSystem->dumpFile($configFile, $updatedContent);
        }

        $kernelFile = $appDir . '/AppKernel.php';

        $fileSystem = new Filesystem();
        $ref = '$bundles = array(';
        $bundleDeclaration = 'new ' . $applicationName . '\\Bundle\\BackendBundle\\' . $applicationName . 'BackendBundle(),';
        $content = file_get_contents($kernelFile);

        if (false === strpos($content, $bundleDeclaration)) {
            $updatedContent = str_replace($ref, $ref . "\n            " . $bundleDeclaration, $content);
            if ($content === $updatedContent) {
                throw new \RuntimeException('Unable to patch %s.', $kernelFile);
            }

            $fileSystem->dumpFile($kernelFile, $updatedContent);
        }
    }

    /**
     * Run installer command
     *
     * @param CommandEvent $event
     */
    public static function configureApp(CommandEvent $event)
    {
        $consoleDir = self::getConsoleDir($event, 'Configure application');

        static::executeCommand($event, $consoleDir, 'avoo:install');
    }

    /**
     * Create administrator user
     *
     * @param CommandEvent $event
     */
    public static function setupAdmin(CommandEvent $event)
    {
        $consoleDir = self::getConsoleDir($event, 'Administrator setup');

        static::executeCommand($event, $consoleDir, 'avoo:user:create');
    }

    /**
     * Build core files
     *
     * @param string     $bundleDir
     * @param string     $applicationName
     * @param Filesystem $filesystem
     */
    private static function buildCoreFiles($bundleDir, $applicationName, Filesystem $filesystem)
    {
        $filesystem->setParameters(array(
            'namespace' => $applicationName . '\\Bundle\\CoreBundle',
            'applicationName' => $applicationName,
            'rename' => array(
                'Bundle.php.twig' => $applicationName . 'CoreBundle.php',
                'Extension.php.twig' => $applicationName . 'CoreExtension.php',
                'app.yml.twig' => strtolower($applicationName) . '.yml',
            )
        ));

        $filesystem->mirror(__DIR__.'/../Resources/skeleton/avoo-core-bundle', $bundleDir, null, array('override' => true));
    }

    /**
     * Build app files
     *
     * @param string     $bundleDir
     * @param string     $applicationName
     * @param Filesystem $filesystem
     */
    private static function buildAppFiles($bundleDir, $applicationName, Filesystem $filesystem)
    {
        $filesystem->setParameters(array(
            'namespace' => $applicationName . '\\Bundle\\CoreBundle',
            'applicationName' => $applicationName,
            'rename' => array(
                'Extension.php.twig' => $applicationName . 'CoreExtension.php',
                'app.yml.twig' => strtolower($applicationName) . '.yml',
            )
        ));

        $filesystem->mirror(__DIR__.'/../Resources/skeleton/avoo-app', $bundleDir . '/app', null, array('override' => true));
    }

    /**
     * Build backend files
     *
     * @param string     $bundleDir
     * @param string     $applicationName
     * @param Filesystem $filesystem
     */
    private static function buildBackendFiles($bundleDir, $applicationName, Filesystem $filesystem)
    {
        $filesystem->setParameters(array(
            'namespace' => $applicationName . '\\Bundle\\BackendBundle',
            'applicationName' => $applicationName,
            'rename' => array(
                'Bundle.php.twig' => $applicationName . 'BackendBundle.php',
                'Extension.php.twig' => $applicationName . 'BackendExtension.php',
                'app.yml.twig' => strtolower($applicationName) . '.yml'
            )
        ));

        $filesystem->mirror(__DIR__.'/../Resources/skeleton/avoo-backend-bundle', $bundleDir, null, array('override' => true));
    }

    /**
     * Build component files
     *
     * @param string     $bundleDir
     * @param string     $applicationName
     * @param Filesystem $filesystem
     */
    private static function buildComponentFiles($bundleDir, $applicationName, Filesystem $filesystem)
    {
        $filesystem->setParameters(array(
            'namespace' => $applicationName,
            'applicationName' => $applicationName,
        ));

        $filesystem->mirror(__DIR__.'/../Resources/skeleton/avoo-component', $bundleDir, null, array('override' => true));
    }

    /**
     * Remove installer files
     *
     * @param CommandEvent $event
     */
    public static function removeFilesInstaller(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        if (!is_dir($appDir)) {
            return;
        }

        if (!is_dir($appDir.'/AvooStandard')) {
            return;
        }

        $fileSystem = new Filesystem();
        $fileSystem->remove($appDir.'/AvooStandard');
    }

    /**
     * Init project
     *
     * @param CommandEvent $event
     */
    public static function initProject(CommandEvent $event)
    {
        $consoleDir = static::getConsoleDir($event, 'install avoo');

        if (null === $consoleDir) {
            return;
        }

        self::executeCommand($event, $consoleDir, 'avoo:install');
    }

    /**
     * Get the twig environment that will render skeletons
     *
     * @return \Twig_Environment
     */
    private static function getTwigEnvironment()
    {
        $skeletonsDirs = array(
            __DIR__.'/../Resources'
        );

        $twigEnvironment = new \Twig_Environment(new \Twig_Loader_Filesystem($skeletonsDirs), array(
            'debug'            => true,
            'cache'            => false,
            'strict_variables' => true,
            'autoescape'       => false,
        ));

        return $twigEnvironment;
    }

    /**
     * @param string     $appDir
     * @param Filesystem $fileSystem
     */
    private static function patchAvooDemoBundleConfiguration($appDir, Filesystem $fileSystem)
    {
        $routingFile = $appDir.'/config/routing_dev.yml';
        $routingData = file_get_contents($routingFile).<<<EOF

# AvooDemoBundle routes (to be removed)
_avoo_demo:
    prefix: /demo
    resource: "@AvooDemoBundle/Resources/config/routing.yml"
EOF;
        $fileSystem->dumpFile($routingFile, $routingData);
    }
}
