<?php

namespace Idephix\Task\SelfUpdate;

use Idephix\Task\Parameter;
use Idephix\Idephix;
use Idephix\TaskExecutor;
use Idephix\Extension\IdephixAwareInterface;
use Idephix\Task\Task;

class SelfUpdate implements IdephixAwareInterface, Task
{
    private $idx;

    public function setIdephix(TaskExecutor $idx)
    {
        $this->idx = $idx;
    }

    public function name()
    {
        return 'selfupdate';
    }

    public function description()
    {
        return 'Update Idephix to the latest version';
    }

    public function code()
    {
        return array($this, 'update');
    }

    public function parameters()
    {
        return Parameter\Collection::dry();
    }

    public function userDefinedParameters()
    {
        return new Parameter\UserDefinedCollection($this->parameters());
    }

    /**
     * Based by composer self-update
     */
    public function update()
    {
        $baseUrl = 'http://getidephix.com/';
        $latest = trim(file_get_contents($baseUrl.'version'));

        if (Idephix::VERSION !== $latest) {
            $this->idx->output->writeln(sprintf('Updating to version <info>%s</info>.', $latest));

            $remoteFilename = $baseUrl.'idephix.phar';
            $localFilename = $_SERVER['argv'][0];
            $tempFilename = basename($localFilename, '.phar').'-temp.phar';

            file_put_contents($tempFilename, file_get_contents($remoteFilename));

            try {
                chmod($tempFilename, 0777 & ~umask());
                // test the phar validity
                $phar = new \Phar($tempFilename);
                // free the variable to unlock the file
                unset($phar);
                rename($tempFilename, $localFilename);
            } catch (\Exception $e) {
                @unlink($tempFilename);
                if (!$e instanceof \UnexpectedValueException && !$e instanceof \PharException) {
                    throw $e;
                }
                $this->idx->output->writeln('<error>The download is corrupted ('.$e->getMessage().').</error>');
                $this->idx->output->writeln('<error>Please re-run the self-update command to try again.</error>');
            }
        } else {
            $this->idx->output->writeln('<info>You are using the latest idephix version.</info>');
        }
    }
}
