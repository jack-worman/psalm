<?php

declare(strict_types=1);

namespace Psalm\Issue;

final class MixedMethodCall extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 15;

    use MixedIssueTrait;
}
