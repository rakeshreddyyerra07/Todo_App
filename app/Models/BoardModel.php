<?php

namespace App\Models;

use CodeIgniter\Model;

class BoardModel extends Model
{
    protected $table = 'boards';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = false;

    protected $allowedFields = [
        'name',
        'description',
        'created_by',
        'created_at',
        'updated_at',
    ];
}
