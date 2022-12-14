<?php

namespace Psalm\Internal\Fork;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 *
 * @internal
 */
class ForkProcessDoneMessage implements ForkMessage
{
    use ImmutableNonCloneableTrait;
    
    public $data;

    
    public function __construct($data)
    {
        $this->data = $data;
    }
}
