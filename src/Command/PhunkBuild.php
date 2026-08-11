<?php

namespace Phunk\Cms\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Phunk\{Phunk, Base, Result};
use Phunk\Cms\ContentParser;
use Phunk\Cms\File;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'phunk:build', description: 'Rebuild the content cache and clear the nginx cache.')]
class PhunkBuild extends Base
{
    public function __construct(
        protected LoggerInterface $logger,
        protected ContentParser $content,
        protected File $file,
        protected string $path,
    ) {
        parent::__construct($logger);
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->debug('__invoke', ['path' => $this->path]);

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

        return $this->file->clearPath($this->path)
        ->match(
            onOk: function (array $summary) use ($output): int {
                $output->writeln(sprintf('Deleted %d file(s) from %s (%d failed).', $summary['deleted'], $this->path, $summary['failed']));

                return $summary['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
            },
            onErr: function (mixed $error) use ($output): int {
                $output->writeln(sprintf('<error>Failed to clear cache at %s: %s</error>', $this->path, $error));

                return Command::FAILURE;
            },
        );
    }
}
