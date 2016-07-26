<?php

class PatientRepositoryTest extends TestCase
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

    public function testCreatePatient()
    {
        $repo = new \LibreEHR\Core\Emr\PatientRepository();
        $id = $repo->create( array(
            'DOB' => '1980-11-11',
            'firstName' => 'Plastic',
            'lastName' => 'Redcup',
        ));
    }

    public function testUpdatePatient()
    {
        $repo = new \LibreEHR\Core\Emr\PatientRepository();
        $id = $repo->create( array(
            'DOB' => '1980-11-11',
            'firstName' => 'Plastic',
            'lastName' => 'Redcup'
        ));

        $repo->update( $id, array(
            'lastName' => 'Bluecup'
        ));
    }

    public function testFindPatientByUsername()
    {
        $repo = new \LibreEHR\Core\Emr\PatientRepository();
        $patient = $repo->find->byLastName( 'Plastic' );
    }
}