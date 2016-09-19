<?php

namespace LibreEHR\Core\Emr\Eloquent;

use LibreEHR\Core\Emr\Eloquent\AbstractModel as Model;
use LibreEHR\Core\Contracts\DocumentInterface;
use LibreEHR\Core\Emr\Eloquent\Category;

class Document extends Model implements DocumentInterface
{
    public $base64Data = null;
    public $filename = null;

    //protected $categories = array();

    protected $table = 'documents';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function getId()
    {
        return $this->id;
    }

    public function setId( $id )
    {
        $this->id = $id;
        return $this;
    }

    public function categories()
    {
        return $this->belongsToMany( 'LibreEHR\Core\Emr\Eloquent\Category', 'categories_to_documents', 'document_id', 'category_id' );
    }

    public function addCategory( $categoryId )
    {
        $this->categories[]= $categoryId;
    }
    public function getCategories()
    {
        return $this->categories;
    }

    public function getType()
    {
        return $this->type;
    }
    public function setType( $type )
    {
        $this->type = $type;
        return $this;
    }

    public function getPublicUrl()
    {
        return "document/{$this->getId()}";
    }

    public function getUrl()
    {
        return $this->url;
    }
    public function setUrl( $url )
    {
        $this->url = $url;
        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }
    public function setDate( $date )
    {
        $this->date = $date;
        return $this;
    }

    public function getMimetype()
    {
        return $this->mimetype;
    }
    public function setMimetype( $mimetype )
    {
        $this->mimetype = $mimetype;
        return $this;
    }

    public function getForeignId()
    {
        return $this->foreign_id;
    }
    public function setForeignId( $id )
    {
        $this->foreign_id = $id;
        return $this;
    }

}
