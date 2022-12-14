<?php

namespace Psalm\Internal\DataFlow;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 *
 * @internal
 */
class Path
{
    use ImmutableNonCloneableTrait;

    public string $type;

    public ?array $unescaped_taints;

    public ?array $escaped_taints;

    public int $length;

    /**
     * @param ?array<string> $unescaped_taints
     * @param ?array<string> $escaped_taints
     */
    public function __construct(
        string $type,
        int $length,
        ?array $unescaped_taints = null,
        ?array $escaped_taints = null
    ) {
        $this->type = $type;
        $this->length = $length;
        $this->unescaped_taints = $unescaped_taints;
        $this->escaped_taints = $escaped_taints;
    }
}
