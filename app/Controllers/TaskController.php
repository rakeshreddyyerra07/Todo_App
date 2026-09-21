<?php

namespace App\Controllers;

use App\Models\BoardModel;
use App\Models\TaskModel;

class TaskController extends BaseController
{
    protected $helpers = ['task'];

    private TaskModel $tasks;
    private BoardModel $boards;

    public function __construct()
    {
        $this->tasks  = new TaskModel();
        $this->boards = new BoardModel();
    }

    /* =====================================================================
       TASK BOARD  (GET /tasks)
    ===================================================================== */

    public function index()
    {
        $role = current_role();

        // ---- boards ------------------------------------------------------
        $boards = $this->boards
            ->select('id, name, description')
            ->orderBy('id', 'ASC')
            ->findAll();

        $selectedBoardId = (int) ($this->request->getGet('board_id') ?? 0);
        $selectedBoard   = null;

        foreach ($boards as $board) {
            if ((int) $board['id'] === $selectedBoardId) {
                $selectedBoard = $board;
                break;
            }
        }

        // No (valid) board in the URL -> open the first board
        if ($selectedBoard === null && ! empty($boards)) {
            $selectedBoard   = $boards[0];
            $selectedBoardId = (int) $boards[0]['id'];
        }

        if ($selectedBoard === null) {
            $selectedBoardId = 0;
        }

        // ---- search / filter --------------------------------------------
        $search         = (string) ($this->request->getGet('search') ?? '');
        $progressFilter = (string) ($this->request->getGet('progress') ?? 'all');

        $tasks = $this->tasks->forBoard($selectedBoardId, $search, $progressFilter);

        // ---- split the tasks into the board columns ---------------------
        $columns = [];

        foreach (progress_options() as $progress) {
            $columns[$progress] = [
                'progress' => $progress,
                'label'    => strtoupper($progress),
                'tasks'    => [],
            ];
        }

        foreach ($tasks as $task) {
            if (isset($columns[$task['progress']])) {
                $columns[$task['progress']]['tasks'][] = $task;
            }
        }

        return view('tasks/index', [
            'is_admin'          => ($role === 'admin'),
            'is_user'           => ($role === 'user'),
            'display_role'      => ucfirst($role),
            'search'            => $search,
            'progress_filter'   => $progressFilter,
            'boards'            => $boards,
            'selected_board'    => $selectedBoard,
            'selected_board_id' => $selectedBoardId,
            'board_error'       => (string) (session()->getFlashdata('board_error') ?? ''),
            'columns'           => $columns,
        ]);
    }

    /* =====================================================================
       ADD TASK  (standalone form + POST used by the "+" modal)
    ===================================================================== */

    /**
     * GET /tasks/add - plain HTML form (fallback when JavaScript is off).
     */
    public function add()
    {
        $boardId = (int) ($this->request->getGet('board_id') ?? 0);
        $board   = $boardId > 0 ? $this->boards->find($boardId) : null;

        return view('tasks/add', [
            'error'       => '',
            'board_id'    => $boardId,
            'board_name'  => (string) ($board['name'] ?? ''),
            'task'        => '',
            'description' => '',
            'priority'    => 'Medium',
            'progress'    => 'Todo',
            'status'      => 1,
        ]);
    }

    /**
     * POST /tasks/create - AJAX (JSON) or normal form post.
     */
    public function create()
    {
        // ---- role --------------------------------------------------------
        $role = current_role();

        if (! ($role === 'admin' || $role === 'user')) {
            return $this->fail('You do not have permission to add tasks.');
        }

        // ---- form data ---------------------------------------------------
        $task        = trim((string) ($this->request->getPost('task') ?? ''));
        $description = trim((string) ($this->request->getPost('description') ?? ''));
        $status      = (int) ($this->request->getPost('status') ?? 1);
        $priority    = (string) ($this->request->getPost('priority') ?? 'Medium');
        $progress    = (string) ($this->request->getPost('progress') ?? 'Todo');
        $boardId     = (int) ($this->request->getPost('board_id') ?? 0);

        // ---- board -------------------------------------------------------
        if ($boardId <= 0) {
            return $this->fail('Please select a board.');
        }

        $board = $this->boards->find($boardId);

        if (! $board) {
            return $this->fail('Selected board does not exist.');
        }

        // ---- validation --------------------------------------------------
        if ($task === '') {
            return $this->fail('Please enter a task.');
        }

        if (! in_array($status, [1, 2], true)) {
            return $this->fail('Invalid status.');
        }

        if (! in_array($priority, priority_options(), true)) {
            return $this->fail('Invalid priority.');
        }

        if (! in_array($progress, progress_options(), true)) {
            return $this->fail('Invalid progress.');
        }

        // ---- insert ------------------------------------------------------
        $newTaskId = $this->tasks->createTask([
            'board_id'    => $boardId,
            'task'        => $task,
            'description' => $description,
            'status'      => $status,
            'priority'    => $priority,
            'progress'    => $progress,
        ]);

        if ($newTaskId <= 0) {
            return $this->fail('Failed to add task.');
        }

        if (wants_json()) {
            return $this->response->setJSON([
                'success'    => true,
                'message'    => 'Task added successfully.',
                'task_id'    => $newTaskId,
                'board_id'   => $boardId,
                'board_name' => $board['name'],
                'progress'   => $progress,
            ]);
        }

        return redirect()
            ->to('/tasks?board_id=' . $boardId)
            ->with('success', 'Task added successfully.');
    }

    /* =====================================================================
       VIEW TASK  (GET /tasks/view/5)
    ===================================================================== */

    public function show(int $id)
    {
        $task = $this->tasks->findTask($id);

        if (! $task) {
            return redirect()->to('/tasks');
        }

        return view('tasks/view', [
            'task'         => $task,
            'user_role'    => current_role(),
            'message'      => (string) ($this->request->getGet('message') ?? ''),
        ]);
    }

    /* =====================================================================
       EDIT TASK  (GET shows the form, POST saves it)
    ===================================================================== */

    public function edit(int $id)
    {
        $taskRow = $this->tasks->findTask($id);

        if (! $taskRow) {
            return redirect()->to('/tasks');
        }

        $role = current_role();

        $task         = (string) ($taskRow['task'] ?? '');
        $description  = (string) ($taskRow['description'] ?? '');
        $status       = (int) ($taskRow['status'] ?? 1);
        $priority     = (string) ($taskRow['priority'] ?? 'Medium');
        $progress     = (string) ($taskRow['progress'] ?? 'Todo');
        $isCompleted  = normalizeCompletionValue($taskRow['is_completed'] ?? '');
        $error        = '';

        if ($this->request->is('post')) {
            $task        = trim((string) ($this->request->getPost('task') ?? ''));
            $description = trim((string) ($this->request->getPost('description') ?? ''));
            $status      = (int) ($this->request->getPost('status') ?? 1);
            $priority    = (string) ($this->request->getPost('priority') ?? 'Medium');
            $progress    = (string) ($this->request->getPost('progress') ?? 'Todo');
            $isCompleted = (string) ($this->request->getPost('is_completed') ?? 'Incomplete');

            if ($task === '') {
                $error = 'Task title is required.';
            }

            if ($status !== 0 && $status !== 1) {
                $status = 1;
            }

            if (! in_array($priority, priority_options(), true)) {
                $priority = 'Medium';
            }

            if (! in_array($progress, progress_options(), true)) {
                $progress = 'Todo';
            }

            if (! in_array($isCompleted, completion_options(), true)) {
                $isCompleted = 'Incomplete';
            }

            // Done automatically means completed
            if ($progress === 'Done') {
                $isCompleted = 'Completed';
            }

            if ($error === '') {
                $saved = $this->tasks->updateTask($id, [
                    'task'         => $task,
                    'description'  => $description,
                    'status'       => $status,
                    'priority'     => $priority,
                    'progress'     => $progress,
                    'is_completed' => $isCompleted,
                ]);

                if ($saved) {
                    if (wants_json()) {
                        $fresh = $this->tasks->findTask($id);

                        return $this->response->setJSON([
                            'success'      => true,
                            'message'      => 'Task updated successfully.',
                            'task'         => $task,
                            'description'  => $description,
                            'priority'     => $priority,
                            'progress'     => $progress,
                            'is_completed' => $isCompleted,
                            'status'       => $status,
                            'edited'       => formatTaskDate($fresh['editedDate'] ?? ''),
                        ]);
                    }

                    return redirect()->to('/tasks?updated=1');
                }

                $error = 'Unable to update task. Please try again.';
            }

            if (wants_json()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $error !== '' ? $error : 'Unable to update task.',
                ]);
            }
        }

        return view('tasks/edit', [
            'task_id'      => $id,
            'task'         => $task,
            'description'  => $description,
            'status'       => $status,
            'priority'     => $priority,
            'progress'     => $progress,
            'is_completed' => $isCompleted,
            'user_role'    => $role,
            'board_id'     => (int) ($taskRow['board_id'] ?? 0),
            'error'        => $error,
        ]);
    }

    /* =====================================================================
       DELETE TASK  (admin only)
    ===================================================================== */

    public function delete(int $id)
    {
        if (current_role() !== 'admin') {
            if (wants_json()) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Only an administrator can delete tasks.',
                ]);
            }

            return redirect()->to('/tasks');
        }

        $deleted = $id > 0 && $this->tasks->delete($id);

        if (wants_json()) {
            return $this->response->setJSON([
                'success' => (bool) $deleted,
                'message' => $deleted ? 'Task deleted successfully.' : 'Failed to delete task.',
            ]);
        }

        return redirect()
            ->to('/tasks')
            ->with($deleted ? 'success' : 'error', $deleted ? 'Task deleted successfully.' : 'Failed to delete task.');
    }

    /* =====================================================================
       TOGGLE COMPLETE  (GET /tasks/toggle/5)
    ===================================================================== */

    public function toggle(int $id)
    {
        $task = $this->tasks->findTask($id);

        if (! $task) {
            return redirect()->to('/tasks?error=1');
        }

        $currentStatus = (int) $task['is_completed'];
        $newStatus     = ($currentStatus === 1) ? 0 : 1;

        $this->tasks->updateTask($id, ['is_completed' => $newStatus]);

        // Basic redirect security (same checks as the original)
        $return = (string) ($this->request->getGet('return') ?? ('tasks/view/' . $id));

        if (
            str_contains($return, '//')
            || str_contains($return, "\n")
            || str_contains($return, "\r")
        ) {
            $return = 'tasks/view/' . $id;
        }

        $return .= (str_contains($return, '?') ? '&' : '?')
            . 'message=' . urlencode('Task completion updated.');

        return redirect()->to(site_url($return));
    }

    /* =====================================================================
       AJAX: UPDATE ONE FIELD  (priority / progress / completion / description)
       POST /tasks/update-field
    ===================================================================== */

    public function updateField()
    {
        $role = current_role();

        if (! ($role === 'admin' || $role === 'user')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'You do not have permission to edit this task.',
            ]);
        }

        $taskId = (int) ($this->request->getPost('task_id') ?? 0);
        $field  = (string) ($this->request->getPost('field') ?? '');
        $value  = (string) ($this->request->getPost('value') ?? '');

        $allowedFields = ['priority', 'progress', 'is_completed', 'description'];

        if ($taskId <= 0 || ! in_array($field, $allowedFields, true)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request.',
            ]);
        }

        if ($field === 'priority' && ! in_array($value, priority_options(), true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid priority value.']);
        }

        if ($field === 'progress' && ! in_array($value, progress_options(), true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid progress value.']);
        }

        if ($field === 'is_completed' && ! in_array($value, completion_options(), true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid completion value.']);
        }

        if ($field === 'description') {
            $value = trim($value);
        }

        if (! $this->tasks->updateTask($taskId, [$field => $value])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Update failed.',
            ]);
        }

        $fresh = $this->tasks->findTask($taskId);

        return $this->response->setJSON([
            'success' => true,
            'field'   => $field,
            'value'   => $value,
            'edited'  => formatTaskDate($fresh['editedDate'] ?? ''),
        ]);
    }

    /* =====================================================================
       AJAX: DRAG AND DROP  (POST /tasks/update-progress)
    ===================================================================== */

    public function updateProgress()
    {
        $taskId      = (int) ($this->request->getPost('task_id') ?? 0);
        $newProgress = (string) ($this->request->getPost('progress') ?? '');

        $response = ['success' => false];

        if ($taskId > 0 && in_array($newProgress, progress_options(), true)) {
            if ($this->tasks->updateTask($taskId, ['progress' => $newProgress])) {
                $response = [
                    'success'  => true,
                    'task_id'  => $taskId,
                    'progress' => $newProgress,
                ];
            } else {
                $response['message'] = 'Unable to update task progress.';
            }
        } else {
            $response['message'] = 'Invalid task or progress value.';
        }

        if (wants_json()) {
            return $this->response->setJSON($response);
        }

        $boardId = (int) ($this->request->getPost('board_id') ?? 0);

        return redirect()->to($boardId > 0 ? '/tasks?board_id=' . $boardId : '/tasks');
    }

    /* =====================================================================
       Helper: error answer for both AJAX and normal requests
    ===================================================================== */

    private function fail(string $message)
    {
        if (wants_json()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $message,
            ]);
        }

        $boardId = (int) ($this->request->getPost('board_id') ?? 0);
        $board   = $boardId > 0 ? $this->boards->find($boardId) : null;

        return view('tasks/add', [
            'error'       => $message,
            'board_id'    => $boardId,
            'board_name'  => (string) ($board['name'] ?? ''),
            'task'        => trim((string) ($this->request->getPost('task') ?? '')),
            'description' => trim((string) ($this->request->getPost('description') ?? '')),
            'priority'    => (string) ($this->request->getPost('priority') ?? 'Medium'),
            'progress'    => (string) ($this->request->getPost('progress') ?? 'Todo'),
            'status'      => (int) ($this->request->getPost('status') ?? 1),
        ]);
    }
}
