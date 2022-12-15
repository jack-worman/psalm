<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Union;

/**
 * @psalm-immutable
 */
final class AttributeArg
{
    use ImmutableNonCloneableTrait;
    /**
     * @psalm-suppress PossiblyUnusedProperty It's part of the public API for now
     */
    public ?string $name = null;

    /**
     * @var Union|UnresolvedConstantComponent
     */
    public $type;

    /**
     * @psalm-suppress PossiblyUnusedProperty It's part of the public API for now
     */
    public CodeLocation $location;

    /**
     * @param Union|UnresolvedConstantComponent $type
     */
    public function __construct(
        ?string $name,
        $type,
        CodeLocation $location
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->location = $location;
    }
}
