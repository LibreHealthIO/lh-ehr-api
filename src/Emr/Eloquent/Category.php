<?php

namespace LibreEHR\Core\Emr\Eloquent;

use LibreEHR\Core\Emr\Eloquent\AbstractModel as Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function documents()
    {
        return $this->belongsToMany( 'LibreEHR\Core\Emr\Eloquent\Document', 'categories_to_documents', 'category_id', 'document_id' );
    }
}
