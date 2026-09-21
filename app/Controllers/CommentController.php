<?php

namespace App\Controllers;

use App\Models\CommentModel;
use App\Models\TaskModel;

class CommentController extends BaseController
{
    protected $helpers = ['task'];

    /**
     * GET /tasks/comments/5  ->  JSON list of the comments of task 5
     */
    public function index(int $taskId)
    {
        if ($taskId <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid task.',
            ]);
        }

        $comments = [];

        foreach ((new CommentModel())->forTask($taskId) as $row) {
            $comments[] = [
                'id'         => (int) $row['id'],
                'task_id'    => (int) $row['task_id'],
                'user_id'    => (int) $row['user_id'],
                'user_name'  => $row['user_name'] ?: 'User',
                'comment'    => $row['comment'],
                'created_at' => date('d M Y, h:i A', strtotime((string) $row['created_at'])),
            ];
        }

        return $this->response->setJSON([
            'success'  => true,
            'comments' => $comments,
        ]);
    }

    /**
     * POST /tasks/comments/add   (task_id, comment)
     */
    public function add()
    {
        $taskId  = (int) ($this->request->getPost('task_id') ?? 0);
        $comment = trim((string) ($this->request->getPost('comment') ?? ''));
        $userId  = (int) session()->get('user_id');

        if ($taskId <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid task.']);
        }

        if ($comment === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Comment cannot be empty.']);
        }

        if (! (new TaskModel())->find($taskId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Task not found.']);
        }

        if (! (new CommentModel())->addComment($taskId, $userId, $comment)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to add comment.']);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Comment added successfully.',
        ]);
    }
}
