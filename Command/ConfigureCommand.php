<?php

namespace Avoo\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class ConfigureCommand
 */
class ConfigureCommand extends ContainerAwareCommand
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
            ->setName('avoo:configure')
            ->setDescription('Avoo framework configuration.')
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

        $output->writeln('<info>Configuring Avoo.</info>');
        $output->writeln('');

        $this->setupDatabase();
        $this->setupAdministrator();
        $this->runCommand('assets:install', array('web' => true));

        $output->writeln('<info>Avoo has been successfully installed and configured.</info>');
    }

    /**
     * Setup database
     */
    protected function setupDatabase()
    {
        $this->output->writeln('<info>Setting up database.</info>');

        $commands = array();
        $commands['doctrine:schema:drop'] = array('--force' => true);
        $commands[] = 'doctrine:migrations:diff';
        $commands['doctrine:migrations:migrate'] = array('--no-interaction' => true);
        $commands[] = 'sylius:rbac:initialize';

        foreach ($commands as $key => $value) {
            if (is_string($key)) {
                $command = $key;
                $parameters = $value;
            } else {
                $command = $value;
                $parameters = array();
            }

            $this->runCommand($command, $parameters);
        }
    }

    /**
     * Setup administrator
     */
    protected function setupAdministrator()
    {
        $question = new ConfirmationQuestion('Would you like to create default administrator user (Recommended)? [y/n] ', true);
        if (!$this->helper->ask($this->input, $this->output, $question)) {
            return;
        }

        $this->runCommand('avoo:user:create');
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
