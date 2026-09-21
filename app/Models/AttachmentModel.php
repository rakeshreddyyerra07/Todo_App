<?php

namespace App\Models;

use CodeIgniter\Model;

class AttachmentModel extends Model
{
    protected $table      = 'task_attachments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps = false;

    protected $allowedFields = [
        'task_id',
        'user_id',
        'original_name',
        'stored_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_at',
    ];

    public function forTask(int $taskId): array
    {
        return $this->where('task_id', $taskId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /**
     * @return int New attachment id, or 0 on failure.
     */
    public function addAttachment(array $data): int
    {
        $builder = $this->db->table($this->table);

        $builder->set($data)->set('uploaded_at', 'NOW()', false);

        if (! $builder->insert()) {
            return 0;
        }

        return (int) $this->db->insertID();
    }
}
