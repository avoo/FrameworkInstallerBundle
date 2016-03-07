<?php

namespace Avoo\Bundle\InstallerBundle\Command;

use Avoo\Bundle\InstallerBundle\Composer\ScriptHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallCommand
 */
class InstallCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface $input
     */
    protected $input;

    /**
     * @var OutputInterface $output
     */
    protected $output;

    /**
     * @var QuestionHelper $helper
     */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('avoo:install')
            ->setDescription('Avoo installer.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->helper = $this->getHelperSet()->get('question');

        $output->writeln('<info>Installing Avoo.</info>');
        $output->writeln('');

        $this->install();

        $output->writeln('<info>Install complete, now you can run the command avoo:configure</info>');
    }

    /**
     * Install Avoo framework
     *
     * @return $this
     */
    protected function install()
    {
        $this->setupCore();
        $this->setupBackend();
        $this->runCommand('cache:clear', array('--no-warmup' => true));

        return $this;
    }

    /**
     * Setup Core
     */
    protected function setupCore()
    {
        ScriptHandler::installCoreBundle($this->input, $this->output, $this->helper);
    }

    /**
     * Setup backend
     */
    protected function setupBackend()
    {
        ScriptHandler::installBackendBundle($this->input, $this->output, $this->helper);
    }

    /**
     * Run command
     *
     * @param string $command
     * @param array  $parameters
     * @return $this
     * @throws \Exception
     */
    protected function runCommand($command, $parameters = array())
    {
        $parameters = array_merge(
            array('command' => $command),
            $parameters,
            $this->getDefaultParameters()
        );

        $this->getApplication()->setAutoExit(false);
        $exitCode = $this->getApplication()->run(new ArrayInput($parameters), $this->output ?: new NullOutput());

        if (0 !== $exitCode) {
            $this->getApplication()->setAutoExit(true);

            $errorMessage = sprintf('The command terminated with an error code: %u.', $exitCode);
            $this->output->writeln("<error>$errorMessage</error>");
            $exception = new \Exception($errorMessage, $exitCode);

            throw $exception;
        }

        return $this;
    }

    /**
     * Get default parameters.
     *
     * @return array
     */
    protected function getDefaultParameters()
    {
        $defaultParameters = array('--no-debug' => true);

        if ($this->input->hasOption('env')) {
            $env = $this->input->hasOption('env');
            $defaultParameters['--env'] = $env ? $this->input->getOption('env') : 'dev';
        }

        if ($this->input->hasOption('verbose') && true === $this->input->getOption('verbose')) {
            $defaultParameters['--verbose'] = true;
        }

        return $defaultParameters;
    }
}
