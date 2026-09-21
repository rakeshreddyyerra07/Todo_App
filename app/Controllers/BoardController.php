<?php

namespace App\Controllers;

use App\Models\BoardModel;
use App\Models\TaskModel;

class BoardController extends BaseController
{
    protected $helpers = ['task'];

    /**
     * POST /boards/create
     * Fields: board_name, board_description
     */
    public function create()
    {
        $role = current_role();

        $error = '';
        $name  = trim((string) ($this->request->getPost('board_name') ?? ''));
        $desc  = trim((string) ($this->request->getPost('board_description') ?? ''));

        if (! ($role === 'admin' || $role === 'user')) {
            $error = 'You do not have permission to create a board.';
        } elseif ($name === '') {
            $error = 'Board name is required.';
        } else {
            $boards  = new BoardModel();
            $boardId = $boards->insert([
                'name'        => $name,
                'description' => $desc,
                'created_by'  => (int) session()->get('user_id'),
            ]);

            if ($boardId) {
                if (wants_json()) {
                    return $this->response->setJSON([
                        'success' => true,
                        'board'   => [
                            'id'          => (int) $boardId,
                            'name'        => $name,
                            'description' => $desc,
                        ],
                    ]);
                }

                return redirect()->to('/tasks?board_id=' . (int) $boardId);
            }

            $error = 'Database error: unable to create the board.';
        }

        if (wants_json()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $error !== '' ? $error : 'Unable to create board.',
            ]);
        }

        return redirect()->to('/tasks')->with('board_error', $error);
    }

    /**
     * POST /boards/delete
     * Field: board_id. A board can only be deleted when it has no tasks.
     */
    public function delete()
    {
        $role    = current_role();
        $boardId = (int) ($this->request->getPost('board_id') ?? 0);

        $result = ['success' => false];

        if (! ($role === 'admin' || $role === 'user')) {
            $result['message'] = 'You do not have permission to delete a board.';
        } elseif ($boardId <= 0) {
            $result['message'] = 'Invalid board.';
        } else {
            $taskCount = (new TaskModel())->countForBoard($boardId);

            if ($taskCount > 0) {
                $result['message'] = 'This board cannot be deleted because it contains '
                    . $taskCount
                    . ' task' . ($taskCount === 1 ? '' : 's')
                    . '. Move or delete the tasks first.';
            } else {
                $boards = new BoardModel();

                if ($boards->find($boardId) && $boards->delete($boardId)) {
                    $result['success']  = true;
                    $result['board_id'] = $boardId;
                } else {
                    $result['message'] = 'Board not found or could not be deleted.';
                }
            }
        }

        if (wants_json()) {
            return $this->response->setJSON($result);
        }

        if (! empty($result['success'])) {
            return redirect()->to('/tasks');
        }

        return redirect()->to('/tasks')->with('board_error', $result['message'] ?? 'Unable to delete board.');
    }
}
