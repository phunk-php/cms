<?php

namespace Phunk\Cms\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Phunk\Base;
use Phunk\Cms\File;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'phunk:nginx:cache-clear', description: 'Clear nginx cache.')]
class NginxCacheClear extends Base
{
    public function __construct(
        protected LoggerInterface $logger,
        protected File $file,
        protected array $clearCacheCommand,
    ) {
        parent::__construct($logger);
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->debug('__invoke', ['command' => $this->clearCacheCommand]);

        $process = new Process($this->clearCacheCommand);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output->write($process->getOutput());

        return Command::SUCCESS;
    }
}