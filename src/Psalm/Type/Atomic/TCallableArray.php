<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an array that is _also_ `callable`.
 *
 * @psalm-immutable
 */
final class TCallableArray extends TNonEmptyArray
{
    public string $value = 'callable-array';
}
