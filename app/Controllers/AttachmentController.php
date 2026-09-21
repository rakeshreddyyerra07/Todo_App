<?php

namespace App\Controllers;

use App\Models\AttachmentModel;
use App\Models\TaskModel;

class AttachmentController extends BaseController
{
    protected $helpers = ['task'];

    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip',
    ];

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private const MAX_SIZE = 10 * 1024 * 1024; // 10 MB

    /**
     * Same location as the original app: <web root>/uploads/task_files
     * (in CI4 the web root is the "public" folder).
     */
    private function uploadDir(): string
    {
        return FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'task_files';
    }

    /* =====================================================================
       GET /tasks/attachments/5  ->  JSON list
    ===================================================================== */

    public function index(int $taskId)
    {
        if ($taskId <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid task.']);
        }

        $attachments = [];

        foreach ((new AttachmentModel())->forTask($taskId) as $row) {
            $extension = strtolower(pathinfo((string) $row['original_name'], PATHINFO_EXTENSION));

            $attachments[] = [
                'id'            => (int) $row['id'],
                'task_id'       => (int) $row['task_id'],
                'user_id'       => (int) $row['user_id'],
                'original_name' => $row['original_name'],
                'stored_name'   => $row['stored_name'],
                'file_path'     => $row['file_path'],
                'file_url'      => base_url(ltrim((string) $row['file_path'], '/')),
                'file_type'     => $row['file_type'],
                'file_size'     => (int) $row['file_size'],
                'is_image'      => in_array($extension, self::IMAGE_EXTENSIONS, true),
                'uploaded_at'   => date('d M Y, h:i A', strtotime((string) $row['uploaded_at'])),
            ];
        }

        return $this->response->setJSON([
            'success'     => true,
            'attachments' => $attachments,
        ]);
    }

    /* =====================================================================
       POST /tasks/attachments/upload   (task_id, attachment = the file)
    ===================================================================== */

    public function upload()
    {
        $userId = (int) session()->get('user_id');
        $taskId = (int) ($this->request->getPost('task_id') ?? 0);

        if ($taskId <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid task.']);
        }

        if (! (new TaskModel())->find($taskId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Task not found.']);
        }

        $file = $this->request->getFile('attachment');

        if ($file === null) {
            return $this->response->setJSON(['success' => false, 'message' => 'No file selected.']);
        }

        if (! $file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'File upload failed.']);
        }

        $size = (int) $file->getSize();

        if ($size <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'The selected file is empty.']);
        }

        if ($size > self::MAX_SIZE) {
            return $this->response->setJSON(['success' => false, 'message' => 'File size cannot exceed 10 MB.']);
        }

        $originalName = basename($file->getClientName());

        if ($originalName === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid file name.']);
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'This file type is not allowed.']);
        }

        $dir = $this->uploadDir();

        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unable to create upload directory.']);
        }

        if (! is_writable($dir)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Upload directory is not writable.']);
        }

        $storedName = 'task_' . $taskId . '_' . uniqid('', true) . '.' . $extension;
        $mimeType   = (string) $file->getMimeType();

        try {
            $file->move($dir, $storedName);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unable to save uploaded file.']);
        }

        $filePath = 'uploads/task_files/' . $storedName;

        $attachmentId = (new AttachmentModel())->addAttachment([
            'task_id'       => $taskId,
            'user_id'       => $userId,
            'original_name' => $originalName,
            'stored_name'   => $storedName,
            'file_path'     => $filePath,
            'file_type'     => $mimeType,
            'file_size'     => $size,
        ]);

        if ($attachmentId <= 0) {
            @unlink($dir . DIRECTORY_SEPARATOR . $storedName);

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to save file information.',
            ]);
        }

        return $this->response->setJSON([
            'success'       => true,
            'message'       => 'File uploaded successfully.',
            'attachment_id' => $attachmentId,
            'original_name' => $originalName,
            'stored_name'   => $storedName,
            'file_path'     => $filePath,
        ]);
    }

    /* =====================================================================
       POST /tasks/attachments/delete   (attachment_id)
       Admin: any file.  User: only the files they uploaded.
    ===================================================================== */

    public function delete()
    {
        $userId       = (int) session()->get('user_id');
        $isAdmin      = current_role() === 'admin';
        $attachmentId = (int) ($this->request->getPost('attachment_id') ?? 0);

        if ($attachmentId <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid attachment.']);
        }

        $model      = new AttachmentModel();
        $attachment = $model->find($attachmentId);

        if (! $attachment) {
            return $this->response->setJSON(['success' => false, 'message' => 'Attachment not found.']);
        }

        if (! $isAdmin && (int) $attachment['user_id'] !== $userId) {
            return $this->response->setJSON(['success' => false, 'message' => 'You cannot delete this file.']);
        }

        if (! $model->delete($attachmentId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unable to delete file.']);
        }

        // Remove the physical file too
        $stored = basename((string) $attachment['stored_name']);

        if ($stored !== '') {
            foreach ($this->candidatePaths($stored) as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'File deleted successfully.',
        ]);
    }

    /* =====================================================================
       GET /tasks/attachments/view/5  ->  the file itself
    ===================================================================== */

    public function show(int $id)
    {
        $row = $id > 0 ? (new AttachmentModel())->find($id) : null;

        if (! $row) {
            return $this->response->setStatusCode(404)->setBody('Attachment not found.');
        }

        $stored = basename((string) ($row['stored_name'] ?? ''));
        $path   = null;

        if ($stored !== '') {
            foreach ($this->candidatePaths($stored) as $candidate) {
                if (is_file($candidate)) {
                    $path = $candidate;
                    break;
                }
            }
        }

        if ($path === null) {
            return $this->response
                ->setStatusCode(404)
                ->setBody('The attachment file no longer exists on the server.');
        }

        $mime = '';

        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = (string) $finfo->file($path);
        }

        if ($mime === '') {
            $mime = (string) ($row['file_type'] ?: 'application/octet-stream');
        }

        $downloadName = basename((string) ($row['original_name'] ?: $stored));
        $downloadName = str_replace(['"', "\r", "\n"], '', $downloadName);

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . $downloadName . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, max-age=3600')
            ->setBody((string) file_get_contents($path));
    }

    /**
     * Where a stored file lives.
     *
     * @return string[]
     */
    private function candidatePaths(string $storedName): array
    {
        return [
            $this->uploadDir() . DIRECTORY_SEPARATOR . $storedName,
        ];
    }
}
