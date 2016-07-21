<?php

namespace LibreEHR\Core\Contracts;

interface DocumentInterface extends BaseInterface
{
    public function addCategory( $categoryId );
    public function getCategories();

    public function getType();
    public function setType( $type );

    public function getUrl();
    public function setUrl( $url );

    public function getDate();
    public function setDate( $date );

    public function getMimetype();
    public function setMimetype( $mimetype );

    public function getForeignId();
    public function setForeignId( $id );
}
