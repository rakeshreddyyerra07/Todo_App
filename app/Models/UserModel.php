<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = false;

    protected $allowedFields = [
        'id',
        'name',
        'email',
        'password',
        'role',
        'reset_token',
        'reset_token_expiry',
    ];
}
