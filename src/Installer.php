<?php

namespace MyLar\Installer;

use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Installer extends Command
{
    
    protected $themes = ['flatlab', 'lte'];
    
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('new')
            ->setDescription('Create a new MyLar application.')
            ->addArgument('name', InputArgument::OPTIONAL, 'What your project name ?')
            ->addArgument('theme', null, InputArgument::OPTIONAL, 'flatlab');
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        if (! $this->checkCommand('yarn')) {
            throw new RuntimeException('You must install Yarn from https://yarnpkg.com');
        }

        $theme = $this->getTheme($input);

        if (! $this->checkThemeExists($theme)) {
            throw new RuntimeException('Your theme not exist. We only have flatlab, lte');
        }

        $this->verifyApplicationDoesntExist(
            $directory = ($input->getArgument('name')) ? getcwd().'/'.$input->getArgument('name') : getcwd()
        );

        $output->writeln('<info>Creating application...</info>');

        $version = $this->getVersion();

        $this->download($zipFile = $this->makeFilename(), $version)
            ->extract($zipFile, $directory)
            ->cleanUp($zipFile);

        $composer = $this->findComposer();

        $commands = [
            $composer.' install --no-scripts',
            $composer.' run-script post-install-cmd',
            $composer.' run-script post-create-project-cmd',
            'yarn install',
            'rm -rf resources/assets resources/views',
            'mv templates/'.$theme.'/assets resources/',
            'mv templates/'.$theme.'/views resources/',
            'mv templates/'.$theme.'/webpack.mix.js .',
            'rm -rf templates',
            'yarn run dev'
        ];

        $process = new Process(implode(' && ', $commands), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        $output->writeln('<comment>Your Lar ready!</comment>');
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string  $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Your Lar already exists!');
        }
    }

    /**
     * Generate a random temporary filename.
     *
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd().'/mylar_'.md5(time().uniqid()).'.zip';
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @param  string  $zipFile
     * @param  string  $version
     * @return $this
     */
    protected function download($zipFile, $version)
    {
        $response = (new Client)->get('https://github.com/vietdien2005/mylar/releases/download/'.$version.'/latest.zip');

        file_put_contents($zipFile, $response->getBody());

        return $this;
    }

    /**
     * Extract the Zip file into the given directory.
     *
     * @param  string  $zipFile
     * @param  string  $directory
     * @return $this
     */
    protected function extract($zipFile, $directory)
    {
        $archive = new ZipArchive;

        $archive->open($zipFile);

        $archive->extractTo($directory);

        $archive->close();

        return $this;
    }

    /**
     * Clean-up the Zip file.
     *
     * @param  string  $zipFile
     * @return $this
     */
    protected function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);

        @unlink($zipFile);

        return $this;
    }

    /**
     * Call API Github repo vietdien2005/mylar get the version latest that should be downloaded.
     *
     * @return string
     */
    protected function getVersion()
    {
        $response = (new Client)->get('https://api.github.com/repos/vietdien2005/mylar/releases');

        $data = json_decode($response->getBody(), true);

        return $data[0]['tag_name'];
    }

    /**
     * Get theme name.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return string
     */
    protected function getTheme(InputInterface $input)
    {
        $theme = $input->getArgument('theme');

        return $theme;
    }

    /**
     * check command exists
     *
     * @param  $cmd
     * @return string
     */
    protected function checkCommand($cmd)
    {
        return !empty(shell_exec("which $cmd"));
    }

    /**
     * check command exists
     *
     * @param  $cmd
     * @return string
     */
    protected function checkThemeExists($theme)
    {
        return in_array($theme, $this->themes);
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar')) {
            return '"'.PHP_BINARY.'" composer.phar';
        }

        return 'composer';
    }
}
