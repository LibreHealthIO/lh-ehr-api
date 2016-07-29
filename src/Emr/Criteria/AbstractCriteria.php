<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 6/7/16
 * Time: 1:31 PM
 */
namespace LibreEHR\Core\Emr\Criteria;

use LibreEHR\Core\Contracts\BaseInterface;
use LibreEHR\Core\Contracts\ModelInterface;
use LibreEHR\Core\Contracts\RepositoryInterface;

abstract class AbstractCriteria
{
    public function __construct( $args )
    {
        if ( is_array( $args ) ) {
            foreach ( $args as $key => $value ) {
                $this->{$key} = $value;
            }
        }
    }

    public abstract function apply( ModelInterface $model );
}