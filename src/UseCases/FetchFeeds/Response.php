<?php

declare(strict_types=1);

namespace UseCases\FetchFeeds;

use Domain\Model;
use UseCases\CanHaveErrors;

class Response
{
    use CanHaveErrors;

    /** @var Model\Feed[] */
    private $feeds;

    private function __construct(array $feeds, array $errors)
    {
        $this->feeds = $feeds;
        $this->errors = $errors;
    }

    public function succeeded(array $feeds): self
    {
        return new self($feeds, []);
    }

    public function failed(array $errors): self
    {
        return new self([], $errors);
    }

    public function getFeeds()
    {
        return $this->feeds;
    }
}
