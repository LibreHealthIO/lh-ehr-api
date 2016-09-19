<?php

namespace LibreEHR\Core\Emr\Eloquent;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractModel extends Model
{
    protected $connection = 'mysql';
}