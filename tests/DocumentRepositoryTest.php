<?php

use LibreEHR\Core\Emr\Repositories\DocumentRepository;

class DocumentRepositoryTest extends TestCase
{
    // The setUp() and tearDown() template methods are run once for each test
    // // method (and on fresh instances) of the test case class.
    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {

    }

    public function testGet()
    {

        $documentRepository = new DocumentRepository();
        $document = $documentRepository->get( 9 );
        $categories = $document->categories;
        foreach ( $categories as $cat ) {
            echo $cat;
        }
    }

}