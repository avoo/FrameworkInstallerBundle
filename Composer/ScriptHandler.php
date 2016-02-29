<?php

namespace Avoo\Bundle\InstallerBundle\Composer;

use Composer\Script\CommandEvent;
use Avoo\Bundle\InstallerBundle\Filesystem\Filesystem;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as BaseScriptHandler;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param QuestionHelper  $helper
     */
    public static function installAvooDemoBundle(InputInterface $input, OutputInterface $output, QuestionHelper $helper)
    {
        $rootDir = getcwd();

        if (file_exists($rootDir.'/src/Avoo/DemoBundle')) {
            return;
        }
        if (!getenv('AVOO_DEMO')) {
            $question = new ConfirmationQuestion('Would you like to install Avoo Demo bundle? [y/n] ', true);
            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }

        $output->writeln('<info>Installing the Avoo Demo bundle.</info>');

        $kernelFile = $rootDir . '/app/AppKernel.php';
        $configFile = $rootDir . '/app/config/config_dev.yml';

        $fileSystem = new Filesystem();
        $fileSystem->mirror(__DIR__.'/../Resources/skeleton/avoo-demo-bundle', $rootDir . '/src', null, array('override' => true));

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

        self::patchAvooDemoBundleConfiguration($rootDir, $fileSystem);
    }

    /**
     * Avoo core bundle install
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param QuestionHelper  $helper
     */
    public static function installCoreBundle(InputInterface $input, OutputInterface $output, QuestionHelper $helper)
    {
        $rootDir = getcwd();
        $question = new ConfirmationQuestion('Would you like to install default core bundle (Recommended)? [y/n] ', true);
        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $applicationName = self::getApplicationName($input, $output, $helper);

        if (is_dir($rootDir.'/src/' . $applicationName . '/CoreBundle')) {
            return;
        }

        $output->writeln('<info>Installing Core bundle.</info>');

        $bundleDir = $rootDir . '/src/' . $applicationName;

        $fileSystem = new Filesystem();
        $fileSystem->setTwigEnvironment(self::getTwigEnvironment());

        self::buildComponentFiles($bundleDir . '/Component/', $applicationName, $fileSystem);
        self::buildCoreFiles($bundleDir . '/Bundle/CoreBundle/', $applicationName, $fileSystem);
        self::buildAppFiles(getcwd(), $applicationName, $fileSystem);

        $configFile = $rootDir . '/app/config/config.yml';
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

        $kernelFile = $rootDir . '/app/AppKernel.php';

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param QuestionHelper  $helper
     */
    public static function installBackendBundle(InputInterface $input, OutputInterface $output, QuestionHelper $helper)
    {
        $rootDir = getcwd();

        $question = new ConfirmationQuestion('Would you like to install default backend bundle (Recommended)? [y/n] ', true);
        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $applicationName = self::getApplicationName($input, $output, $helper);

        if (is_dir($rootDir.'/src/' . $applicationName . '/BackendBundle')) {
            return;
        }

        $output->writeln('<info>Installing Backend bundle.</info>');
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

        $configFile = $rootDir . '/app/config/config.yml';
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

        $kernelFile = $rootDir . '/app/AppKernel.php';

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
     * Get application name
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param QuestionHelper  $helper
     *
     * @return string
     */
    private static function getApplicationName(InputInterface $input, OutputInterface $output, QuestionHelper $helper)
    {
        $question = new Question('Choose your application name: ');
        $question->setValidator(function($answer) {
            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $answer)) {
                throw new \InvalidArgumentException('The application name contains invalid characters.');
            }

            return ucfirst(strtolower($answer));
        });

        return $helper->ask($input, $output, $question);
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
     * @param string     $rootDir
     * @param Filesystem $fileSystem
     */
    private static function patchAvooDemoBundleConfiguration($rootDir, Filesystem $fileSystem)
    {
        $routingFile = $rootDir . '/app/config/routing_dev.yml';
        $routingData = file_get_contents($routingFile).<<<EOF

# AvooDemoBundle routes (to be removed)
_avoo_demo:
    prefix: /demo
    resource: "@AvooDemoBundle/Resources/config/routing.yml"
EOF;
        $fileSystem->dumpFile($routingFile, $routingData);
    }
}
