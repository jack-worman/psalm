<?php

namespace Psalm\Internal\Scope;

use PhpParser;
use Psalm\Internal\Clause;
use Psalm\Type\Union;

/**
 * @internal
 */
class SwitchScope
{
    /**
     * @var array<string, Union>|null
     */
    public ?array $new_vars_in_scope;

    /**
     * @var array<string, bool>
     */
    public array $new_vars_possibly_in_scope = [];

    /**
     * @var array<string, Union>|null
     */
    public ?array $redefined_vars;

    /**
     * @var array<string, Union>|null
     */
    public ?array $possibly_redefined_vars;

    /**
     * @var array<PhpParser\Node\Stmt>
     */
    public array $leftover_statements = [];

    /**
     * @var PhpParser\Node\Expr|null
     */
    public ?PhpParser\Node\Expr $leftover_case_equality_expr;

    /**
     * @var list<Clause>
     */
    public array $negated_clauses = [];

    /**
     * @var array<string, bool>|null
     */
    public ?array $new_assigned_var_ids;
}
