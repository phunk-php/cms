<?php

namespace Phunk\Cms\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Phunk\{Phunk, Base, Result};
use Phunk\Cms\ContentParser;
use Phunk\Cms\File;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'phunk:build', description: 'Rebuild the content cache and clear the nginx cache.')]
class PhunkBuild extends Base
{
    public function __construct(
        protected LoggerInterface $logger,
        protected ContentParser $content,
        protected File $file,
        protected array $clearCacheCommand,
    ) {
        parent::__construct($logger);
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->debug('__invoke', ['command' => $this->clearCacheCommand]);

        $contentResult = $this->content->processContent();

        if ($contentResult->isErr()) {
            $output->writeln('<error>Failed to rebuild content cache.</error>');
            return Command::FAILURE;
        }

        $items = $contentResult
        ->andThen(Phunk::map(fn (Result $item) => $item->unwrap()))
        ->unwrap()
        ;

        $output->writeln(sprintf('Rebuilt content cache (%d item(s)).', count($items)));

        $process = new Process($this->clearCacheCommand);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output->write($process->getOutput());
        $output->writeln('Cleared nginx cache.');

        return Command::SUCCESS;
    }
}
