<?php

class PatientRepositoryTest extends TestCase
{
//        public function testCreatePatient()
//        {
//            $repo = new \LibreEHR\Core\Emr\PatientRepository();
//            $id = $repo->create( array(
//                'DOB' => '1980-11-11',
//                'firstName' => 'Plastic',
//                'lastName' => 'Redcup',
//            ));
//        }
//
//        public function testUpdatePatient()
//        {
//            $repo = new \LibreEHR\Core\Emr\PatientRepository();
//            $id = $repo->create( array(
//                'DOB' => '1980-11-11',
//                'firstName' => 'Plastic',
//                'lastName' => 'Redcup'
//            ));
//
//            $repo->update( $id, array(
//                'lastName' => 'Bluecup'
//            ));
//        }
//
//        public function testFindPatientByUsername()
//        {
//            $repo = new \LibreEHR\Core\Emr\PatientRepository();
//            $patient = $repo->find->byLastName( 'Plastic' );
//        }

        public function testBasicExample()
        {
            $this->post('/fhir/patient', ['dateOfBirth' => '1980-10-10'] )
                ->seeJson([
                    'created' => true,
                ]);
        }
}