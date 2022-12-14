<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class TemplateAsTree extends ParseTree
{
    /**
     * @var string
     */
    public string $param_name;

    /**
     * @var string
     */
    public string $as;

    public function __construct(string $param_name, string $as, ?ParseTree $parent = null)
    {
        $this->param_name = $param_name;
        $this->as = $as;
        $this->parent = $parent;
    }
}
