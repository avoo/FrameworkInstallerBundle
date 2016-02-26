<?php

namespace Avoo\Bundle\InstallerBundle\Command;

use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var DialogHelper $dialog
     */
    protected $dialog;

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
        $this->dialog = $this->getHelperSet()->get('dialog');

        $output->writeln('<info>Installing Avoo.</info>');
        $output->writeln('');

        $this->install();

        $output->writeln('<info>Avoo has been successfully installed.</info>');
    }

    /**
     * Install Avoo framework
     *
     * @return $this
     */
    protected function install()
    {
        $this->output->writeln('<info>Setting up database.</info>');

        $this->setupDatabase();

        return $this;
    }

    /**
     * Setup database
     *
     * @throws \Exception
     */
    protected function setupDatabase()
    {
        $commands = array();
        if (!$this->isDatabaseExist()) {
            $commands[] = 'doctrine:database:create';
        }

        $commands['doctrine:schema:drop'] = array('--force' => true);
        $commands[] = 'doctrine:migrations:diff';
        $commands[] = 'doctrine:migrations:migrate';
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
     * Is database exist
     *
     * @return bool
     * @throws \Exception
     */
    private function isDatabaseExist()
    {
        $databaseName = $this->getContainer()->getParameter('database_name');

        try {
            /** @var MySqlSchemaManager $schemaManager */
            $schemaManager = $this->getContainer()->get('doctrine')->getManager()->getConnection()->getSchemaManager();
        } catch (\Exception $exception) {
            $message = "SQLSTATE[42000] [1049] Unknown database '%s'";
            if (false !== strpos($exception->getMessage(), sprintf($message, $databaseName))) {
                return false;
            }

            throw $exception;
        }

        try {
            return in_array($databaseName, $schemaManager->listDatabases());
        } catch(\PDOException $e) {
            return false;
        }
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
