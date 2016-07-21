<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 4/15/16
 * Time: 11:48 AM
 */
namespace LibreEHR\Core\Emr\Finders;

use LibreEHR\Core\Contracts\FinderInterface;

/**
 * Class AbstractFinder
 *
 * See https://www.hl7.org/fhir/search.html
 */
class AbstractFinder implements FinderInterface
{
    public function byId( $id )
    {

    }

    public function byLastUpdated( $date )
    {

    }

    public function byTag( $tag )
    {

    }

    public function byProfile( $profile )
    {

    }

    public function bySecurity( $security )
    {

    }

    public function byText( $text )
    {

    }

    public function byContent( $content )
    {

    }

    public function byList( $list )
    {

    }

    public function byQuery( $query )
    {

    }
}