<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 1/5/17
 * Time: 10:53 AM
 */
namespace LibreEHR\Core\Emr\Eloquent;

use Illuminate\Database\Eloquent\Model;
use LibreEHR\Core\Contracts\ProviderInterface;
use LibreEHR\Core\Emr\Repositories\FacilityRepository;

class User extends Model implements ProviderInterface
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $connectionKey = null;

    public function facility()
    {
        $facilityRepo = new FacilityRepository();
        $facilityRepo->setConnection( $this->getConnectionName() );
        $facility = $facilityRepo->get( $this->facility_id );
        return $facility;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getFirstName()
    {
        return $this->fname;
    }

    public function setFirstName($firstName)
    {
        $this->fname = $firstName;
        return $this;
    }

    public function getLastName()
    {
        return $this->lname;
    }

    public function setLastName($lastName)
    {
        $this->lname = $lastName;
        return $this;
    }

    public function getEmailAddress()
    {
        return $this->email;
    }

    public function setEmailAddress($emailAddress)
    {
        $this->email = $emailAddress;
        return $this;
    }

    public function getAddress()
    {
        return $this->facility()->street;
    }

    public function setAddress($address)
    {
        // Setting address is not implemented
        return $this;
    }

    public function getTown()
    {
        return $this->facility()->city;
    }

    public function setTown($town)
    {
        // Not implemented
        return $this;
    }

    public function getState()
    {
        return $this->facility()->state;
    }

    public function setState($state)
    {
        // Not implemented
        return $this;
    }

    public function getCountry()
    {
        // No country on libreehr
        return '';
    }

    public function setCountry($country)
    {
        return $this;
    }

    public function getName()
    {
        return $this->facility()->name;
    }

    public function setName($practiceName)
    {
        // setting facility name not supported
        return $this;
    }

    public function getEmrId()
    {
        return $this->id;
    }

    public function setEmrId($emrId)
    {
        $this->id = $emrId;
        return $this;
    }

    public function getConnectionKey()
    {
        return $this->connectionKey;
    }

    public function setConnectionKey( $key )
    {
        $this->connectionKey = $key;
    }
}
