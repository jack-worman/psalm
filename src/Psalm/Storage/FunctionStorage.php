<?php

namespace Psalm\Storage;

final class FunctionStorage extends FunctionLikeStorage
{
    /** @var array<string, bool> */
    public array $byref_uses = [];
}
