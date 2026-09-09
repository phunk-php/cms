<?php

namespace Phunk\Cms\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Phunk\{Phunk, Base, Result};
use Phunk\Cms\ContentParser;
use Phunk\Cms\Entity\BaseContent as Content;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

//use Phunk\Model\Content as ContentModel;

#[AsCommand(name: 'phunk:content:cache', description: 'Process all content and build the SQLite cache.')]
class ContentCache extends Base
{
    private const DATE_FORMAT = 'Y-m-d H:i';

    public function __construct(
        protected LoggerInterface $logger,
        protected ContentParser $content,
    ) {
        return parent::__construct($logger);
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->debug('__invoke');
        $result = $this->content->processContent();

        if ($result->isErr()) {
            return Command::FAILURE;
        }

        $data = $result
        ->andThen(Phunk::map(fn(Result $item) => $item->unwrap()))
        ->andThen(Phunk::map(fn(Content $item) => [
            'id' => $item->getId(),
            'slug' => $item->getSlug(),
            'title' => $item->getTitle(),
            'locale' => $item->getLocale(),
            'type'   => $item::class,
            'created' => $item->getCreatedAt()->format(self::DATE_FORMAT),
            'updated' => $item->getUpdatedAt()->format(self::DATE_FORMAT),
        ]))
        ->unwrap()
        ;

        $table = new Table($output);
        $table
        ->setHeaders(['ID', 'Slug', 'Title', 'Locale', 'Type', 'Created', 'Updated'])
        ->setRows($data)
        ;

        $table->render();
        return Command::SUCCESS;
        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
}