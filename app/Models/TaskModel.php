<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskModel extends Model
{
    protected $table      = 'tasks';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps = false;

    protected $allowedFields = [
        'board_id',
        'task',
        'description',
        'status',
        'is_completed',
        'priority',
        'progress',
        'addedDate',
        'editedDate',
    ];

    /**
     * Columns returned to the app. "task" and "description" are aliased so
     * the array keys are always lower case, whichever way the column names
     * were spelled in the database (older dumps use Task / Description).
     */
    private const COLUMNS = 'tasks.id, tasks.board_id, tasks.task AS task, '
        . 'tasks.description AS description, tasks.status, tasks.is_completed, '
        . 'tasks.priority, tasks.progress, tasks.addedDate, tasks.editedDate';

    /**
     * One task, or null.
     */
    public function findTask(int $id): ?array
    {
        return $this->select(self::COLUMNS)
            ->where('tasks.id', $id)
            ->first();
    }

    /**
     * Tasks for the board screen (newest first).
     * $boardId = 0 means "do not filter by board".
     */
    public function forBoard(int $boardId, string $search = '', string $progress = 'all'): array
    {
        $this->select(self::COLUMNS);

        if ($search !== '') {
            $this->where('tasks.task LIKE', '%' . $search . '%');
        }

        if ($boardId > 0) {
            $this->where('tasks.board_id', $boardId);
        }

        if ($progress !== '' && $progress !== 'all') {
            $this->where('tasks.progress', $progress);
        }

        return $this->orderBy('tasks.id', 'DESC')->findAll();
    }

    public function countForBoard(int $boardId): int
    {
        return (int) $this->where('board_id', $boardId)->countAllResults();
    }

    /**
     * Inserts a task. addedDate / editedDate use the database clock
     * (NOW()) exactly like the original app did.
     *
     * @return int New task id, or 0 on failure.
     */
    public function createTask(array $data): int
    {
        $builder = $this->db->table($this->table);

        $builder->set($data)
            ->set('addedDate', 'NOW()', false)
            ->set('editedDate', 'NOW()', false);

        if (! $builder->insert()) {
            return 0;
        }

        return (int) $this->db->insertID();
    }

    /**
     * Updates the given columns and refreshes editedDate.
     */
    public function updateTask(int $id, array $data): bool
    {
        $builder = $this->db->table($this->table);

        $builder->set($data)
            ->set('editedDate', 'NOW()', false)
            ->where('id', $id);

        return (bool) $builder->update();
    }
}
