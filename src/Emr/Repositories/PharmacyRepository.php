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
use LibreEHR\Core\Contracts\PharmacyInterface;
use LibreEHR\Core\Contracts\PharmacyRepositoryInterface;
use Illuminate\Support\Facades\App;
use LibreEHR\Core\Emr\Criteria\DocumentByPid;
use LibreEHR\Core\Emr\Eloquent\PharmacyData as Pharmacy;
use LibreEHR\Core\Emr\Finders\Finder;

class PharmacyRepository extends AbstractRepository implements PharmacyRepositoryInterface
{
    public function model()
    {
        return '\LibreEHR\Core\Contracts\PharmacyInterface';
    }

    public function find()
    {
        return parent::find();
    }

    public function create(PharmacyInterface $pharmacyInterface)
    {
        if (is_a($pharmacyInterface, $this->model())) {
            $pharmacyInterface->save();
            $pharmacyInterface = $this->get($pharmacyInterface->id);
        }
        return $pharmacyInterface;
    }

    public function update($id, array $data)
    {
    }

    public function delete($id)
    {
    }

    public function fetchAll()
    {
        return Pharmacy::all();
    }

    public function get($id)
    {
        return Pharmacy::find($id);
    }
}
