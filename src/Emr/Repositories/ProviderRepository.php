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
use LibreEHR\Core\Contracts\ProviderInterface;
use LibreEHR\Core\Contracts\ProviderRepositoryInterface;
use Illuminate\Support\Facades\App;
use LibreEHR\Core\Emr\Criteria\DocumentByPid;
use LibreEHR\Core\Emr\Eloquent\ProviderData as Provider;
use LibreEHR\Core\Emr\Finders\Finder;

class ProviderRepository extends AbstractRepository implements ProviderRepositoryInterface
{
    public function model()
    {
        return '\LibreEHR\Core\Contracts\ProviderInterface';
    }

    public function find()
    {
        return parent::find();
    }

    public function create(ProviderInterface $providerInterface)
    {
        if (is_a($providerInterface, $this->model())) {
            $providerInterface->save();
            $providerInterface = $this->get($providerInterface->id);
        }
        return $providerInterface;
    }

    public function update($id, array $data)
    {
    }

    public function delete($id)
    {
    }
}
