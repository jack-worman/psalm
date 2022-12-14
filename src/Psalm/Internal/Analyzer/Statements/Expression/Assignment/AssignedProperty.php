<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use Psalm\Type\Union;

/**
 * @internal
 */
class AssignedProperty
{
    /**
     * @var Union
     */
    public Union $property_type;

    /**
     * @var string
     */
    public string $id;

    /**
     * @var Union
     */
    public Union $assignment_type;

    public function __construct(
        Union $property_type,
        string $id,
        Union $assignment_type
    ) {
        $this->property_type = $property_type;
        $this->id = $id;
        $this->assignment_type = $assignment_type;
    }
}
