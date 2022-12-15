<?php

namespace Psalm\Type\Atomic;

/**
 * @deprecated Will be removed in Psalm v6, use TIntRange instead
 *
 * Represents a list key created from foreach ($list as $key => $value)
 * @psalm-immutable
 */
final class TDependentListKey extends TInt implements DependentType
{
    /**
     * Used to hold information as to what list variable this refers to
     */
    public string $var_id;

    /**
     * @param string $var_id the variable id
     */
    public function __construct(string $var_id)
    {
        $this->var_id = $var_id;
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'list-key<' . $this->var_id . '>';
    }

    public function getVarId(): string
    {
        return $this->var_id;
    }

    public function getAssertionString(): string
    {
        return 'int';
    }

    public function getReplacement(): TInt
    {
        return new TInt();
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
