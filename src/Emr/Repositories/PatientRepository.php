<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 2/5/16
 * Time: 9:44 AM
 */

namespace LibreEHR\Core\Emr\Repositories;

use LibreEHR\Core\Contracts\DocumentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use LibreEHR\Core\Contracts\PatientInterface;
use LibreEHR\Core\Contracts\PatientRepositoryInterface;
use Illuminate\Support\Facades\App;
use LibreEHR\Core\Emr\Criteria\DocumentByPid;
use LibreEHR\Core\Emr\Eloquent\PatientData as Patient;
use LibreEHR\Core\Emr\Finders\Finder;

class PatientRepository extends AbstractRepository implements PatientRepositoryInterface
{
    public function model()
    {
        return '\LibreEHR\Core\Contracts\PatientInterface';
    }

    public function find()
    {
        return parent::find();
    }

    public function create( PatientInterface $patientInterface )
    {
        if ( is_a( $patientInterface, $this->model() ) ) {
            $photo = $patientInterface->getPhoto();

            if ( !$patientInterface->getId() ) {
                // If a pid is not provided, we have to increment the pid in SQL
                // because though it's not the primary key, it must be unique.
                // This subquery increments the pid from the max in the table
                $subquery = DB::connection($this->connection)->table((new Patient)->getTable() . ' as PD')
                    ->selectRaw('pid + 1 as new_pid')
                    ->orderBy('pid', 'desc')
                    ->take(1)->toSql();

                $pid = DB::connection($this->connection)->raw("($subquery)");
                $patientInterface->setAttribute( 'pid', $pid  );
                $patientInterface->setAttribute( 'pubpid', $pid  );
            }

            $patientInterface->save();
            $patientInterface = $this->get( $patientInterface->id );

            if ( $photo ) {
                // TODO use document Repository to get the path from some config
                $docpath = "/Users/kchapple/Dev/www/openemr_github/sites/default/documents";
                mkdir($docpath . "/" . $patientInterface->getPid());
                $filepath = $docpath . "/" . $patientInterface->getPid() . "/" . $photo->filename;
                $ifp = fopen($filepath, "wb");
                fwrite($ifp, base64_decode($photo->base64Data));
                fclose($ifp);

                $documentRepo = App::make('LibreEHR\Core\Contracts\DocumentRepositoryInterface');
                $photo->setType('file_url');
                $photo->setUrl("file://$filepath");
                $photo->setDate(date('Y-m-d'));
                $photo->setForeignId($patientInterface->getPid());
                $photo->addCategory(10); // 10 === 'Patient Photograph'

                $documentRepo->create($photo);
            }
        }

        return $patientInterface;
    }

    public function onAfterFind( $entity )
    {
        $documentRepository = new DocumentRepository( new Finder(), $this->connection );
        $documents = $documentRepository->finder()->pushCriteria( new DocumentByPid( $entity->getPid(), '10'  ) );
        $photo = null;
        foreach ( $documents as $d ) {
            foreach ( $d->categories as $category ) {
                if ( $category->id == '10' ) {
                    $photo = $d;
                    break;
                }
            }
        }
        $entity->setPhoto( $photo );
        return $entity;
    }

    public function update( $id, array $data )
    {

    }

    public function delete( $id )
    {

    }

}