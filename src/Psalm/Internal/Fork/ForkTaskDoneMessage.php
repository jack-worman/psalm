<?php

namespace Psalm\Internal\Fork;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 *
 * @internal
 */
class ForkTaskDoneMessage implements ForkMessage
{
    use ImmutableNonCloneableTrait;

    
    public $data;

    
    public function __construct($data)
    {
        $this->data = $data;
    }
}
