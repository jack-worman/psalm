<?php

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Type\Union;

/**
 * @internal
 */
class CaseScope
{
    /**
     * @var Context
     */
    public Context $parent_context;

    /**
     * @var array<string, Union>|null
     */
    public ?array $break_vars;

    public function __construct(Context $parent_context)
    {
        $this->parent_context = $parent_context;
    }

    public function __destruct()
    {
        unset($this->parent_context);
    }
}
