<?php

declare(strict_types=1);

namespace Psalm\Issue;

final class ImpureStaticVariable extends CodeIssue
{
    public const ERROR_LEVEL = -1;
    public const SHORTCODE = 210;
}
