<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 1/5/17
 * Time: 10:53 AM
 */
namespace LibreEHR\Core\Emr\Eloquent;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    protected $table = 'facility';
    protected $primaryKey = 'id';
    public $timestamps = false;

}
