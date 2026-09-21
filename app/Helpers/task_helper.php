<?php

/*
|--------------------------------------------------------------------------
| Task helper
|--------------------------------------------------------------------------
| Small view / controller helpers ported from the original Todo App
| (task/index.php). Load with:  helper('task');
*/

if (! function_exists('progress_options')) {
    /**
     * Board columns, in the order they are shown.
     */
    function progress_options(): array
    {
        return ['Todo', 'Pending', 'In Progress', 'Review', 'Done'];
    }
}

if (! function_exists('priority_options')) {
    function priority_options(): array
    {
        return ['Low', 'Medium', 'High'];
    }
}

if (! function_exists('completion_options')) {
    function completion_options(): array
    {
        return ['Incomplete', 'Pending', 'Completed'];
    }
}

if (! function_exists('current_role')) {
    /**
     * Role of the logged in user: "admin" or "user".
     */
    function current_role(): string
    {
        return strtolower(trim((string) (session()->get('user_role') ?? 'user')));
    }
}

if (! function_exists('wants_json')) {
    /**
     * True for AJAX / fetch() requests that expect a JSON answer.
     */
    function wants_json(): bool
    {
        $request = service('request');

        return $request->isAJAX()
            || str_contains($request->getHeaderLine('Accept'), 'application/json');
    }
}

if (! function_exists('getPriorityClass')) {
    function getPriorityClass($priority): string
    {
        switch ($priority) {
            case 'High':
                return 'priority-high';
            case 'Medium':
                return 'priority-medium';
            case 'Low':
            default:
                return 'priority-low';
        }
    }
}

if (! function_exists('getProgressClass')) {
    function getProgressClass($progress): string
    {
        switch ($progress) {
            case 'In Progress':
                return 'progress-progress';
            case 'Pending':
                return 'progress-pending';
            case 'Review':
                return 'progress-review';
            case 'Done':
                return 'progress-done';
            case 'Todo':
            default:
                return 'progress-todo';
        }
    }
}

if (! function_exists('normalizeCompletionValue')) {
    /**
     * Converts legacy 1/0 values (or anything unexpected) into one of
     * "Completed", "Pending" or "Incomplete".
     */
    function normalizeCompletionValue($value): string
    {
        $value = trim((string) $value);

        if (in_array($value, ['Completed', 'Pending', 'Incomplete'], true)) {
            return $value;
        }

        if ($value === '1') {
            return 'Completed';
        }

        return 'Incomplete';
    }
}

if (! function_exists('getCompletionClass')) {
    function getCompletionClass($completion): string
    {
        switch ($completion) {
            case 'Completed':
                return 'completed';
            case 'Pending':
                return 'pending';
            case 'Incomplete':
            default:
                return 'incomplete';
        }
    }
}

if (! function_exists('formatTaskDate')) {
    function formatTaskDate($date): string
    {
        if (empty($date)) {
            return '';
        }

        $timestamp = strtotime((string) $date);

        if (! $timestamp) {
            return '';
        }

        return date('d M Y, h:i:s A', $timestamp);
    }
}
