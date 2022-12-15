<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;

final class EnumCaseStorage
{
    /**
     * @var int|string|null
     */
    public $value;

    public CodeLocation $stmt_location;

    public bool $deprecated = false;

    /**
     * @param int|string|null  $value
     */
    public function __construct(
        $value,
        CodeLocation $location
    ) {
        $this->value = $value;
        $this->stmt_location = $location;
    }
}
