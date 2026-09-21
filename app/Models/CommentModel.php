<?php

namespace App\Models;

use CodeIgniter\Model;

class CommentModel extends Model
{
    protected $table      = 'task_comments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps = false;

    protected $allowedFields = [
        'task_id',
        'user_id',
        'comment',
        'created_at',
        'updated_at',
    ];

    /**
     * Comments of a task with the author's name, newest first.
     */
    public function forTask(int $taskId): array
    {
        return $this->select('task_comments.id, task_comments.task_id, task_comments.user_id, '
                . 'task_comments.comment, task_comments.created_at, users.name AS user_name')
            ->join('users', 'users.id = task_comments.user_id', 'left')
            ->where('task_comments.task_id', $taskId)
            ->orderBy('task_comments.id', 'DESC')
            ->findAll();
    }

    public function addComment(int $taskId, int $userId, string $comment): bool
    {
        $builder = $this->db->table($this->table);

        $builder->set([
                'task_id' => $taskId,
                'user_id' => $userId,
                'comment' => $comment,
            ])
            ->set('created_at', 'NOW()', false);

        return (bool) $builder->insert();
    }
}
