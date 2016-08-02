<?php

namespace LibreEHR\Core\Contracts;
use Illuminate\Http\Request;

/**
 * Interface BaseAdapterInterface
 *
 * @package LibreEHR\Core\Contracts
 *
 * Take a collection and output something
 *
 */
interface BaseAdapterInterface
{
    public function retrieve( $id );
    public function store( Request $request );
    public function collectionToOutput();

    // TODO add search method ??

}
