<?php

declare(strict_types=1);

namespace Psalm\Issue;

final class MismatchingDocblockReturnType extends CodeIssue
{
    public const ERROR_LEVEL = 4;
    public const SHORTCODE = 142;
}
