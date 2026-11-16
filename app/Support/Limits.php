<?php

namespace App\Support;

/**
 * Single source of truth for validation limits (exposed at GET /api/meta).
 */
final class Limits
{
    public const TITLE_MAX = 255;
    public const NAME_MAX = 255;
    public const TASK_DESCRIPTION_MAX = 5000;
    public const PROJECT_DESCRIPTION_MAX = 2000;
    public const COMMENT_MAX = 2000;
    public const LABEL_MAX = 30;
    public const SUBTASK_MAX = 255;
    public const PASSWORD_MIN = 8;
    public const AVATAR_MAX_KB = 2048;
    public const PER_PAGE_DEFAULT = 20;
    public const PER_PAGE_MAX = 100;

    /** @return array<string, int> */
    public static function all(): array
    {
        return [
            'title_max' => self::TITLE_MAX,
            'name_max' => self::NAME_MAX,
            'task_description_max' => self::TASK_DESCRIPTION_MAX,
            'project_description_max' => self::PROJECT_DESCRIPTION_MAX,
            'comment_max' => self::COMMENT_MAX,
            'label_max' => self::LABEL_MAX,
            'subtask_max' => self::SUBTASK_MAX,
            'password_min' => self::PASSWORD_MIN,
            'avatar_max_kb' => self::AVATAR_MAX_KB,
        ];
    }
}
