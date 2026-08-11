<?php

namespace Phunk\Cms;

use Twig\Environment;
use Phunk\{Result, Base};
use Psr\Log\LoggerInterface;

class Html extends Base
{
    public function __construct(
        protected LoggerInterface $logger,
        protected Environment $twig,
    ) {
    }

    public function render(string $template, array $context = []): Result
    {
        $this->debug('render', ['template' => $template, 'context' => $context]);
        return Result::ok($this->twig->render($template, $context));
    }

    public function addGlobalData(array $data): Result
    {
        foreach ($data as $name => $value) {
            $this->twig->addGlobal($name, $value);
        }

        return Result::ok($data);
    }
}
