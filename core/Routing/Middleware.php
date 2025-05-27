<?php

namespace Core\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Middleware
{
    public string $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }
}