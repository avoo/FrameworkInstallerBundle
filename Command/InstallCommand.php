<?php

namespace Avoo\Bundle\InstallerBundle\Command;

use Avoo\Bundle\InstallerBundle\Composer\ScriptHandler;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
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
     */
    protected function install()
    {
        $this->setupCore();
        $this->setupBackend();
        $this->initializeDatabase();
        $this->runCommand('cache:clear', array('--no-warmup' => true));
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
     * Create database
     */
    protected function initializeDatabase()
    {
        $this->output->writeln('<info>Initialize database.</info>');
        if (!$this->isDatabaseExist()) {
            $this->runCommand('doctrine:database:create');
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
