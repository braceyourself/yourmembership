<?php namespace Braceyourself\Yourmembership\Models;

use App\Jobs\ApiRequest;
use App\Models\Wordpress\Srnt\User;
use Braceyourself\Yourmembership\Client;
use Illuminate\Support\Collection;

/**
 * @property string $RegistrationID
 *
 * attributes
 * @property string $type
 * @property string $registration_id
 * @property bool $has_tobacco_industry_affiliation
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string $registered_at
 * @property string $full_name
 * @property string $title
 * @property string $company
 * @property string $nickname
 * @property Collection $form_data
 *
 * Class Registration
 * @package Braceyourself\Yourmembership\Models
 */
class Registration extends Model
{
    protected $primaryKey = 'RegistrationID';
    protected $keyType = 'string';
    protected $appends = [
        'type',
        'form_data',
    ];


    public function loadDetails()
    {
        $details = $this->api()->cacheResponse(60 * 30)->registration($this->RegistrationID);

        $this->attributes = array_merge($this->attributes, $details->getAttributes());

        return $this;
    }

    /*******************************************************
     * attributes
     ******************************************************/
    /**
     * Accessor for $this->form_data
     **/
    public function getFormDataAttribute()
    {
        $data_set = array_merge(
            $this->attributes['CustomFormDataSet'] ?? [],
            $this->attributes['DataSet'] ?? [],
        );

        return collect($data_set)->mapWithKeys(function ($v, $k) {

            $key = \Str::of($v['Name'] ?? $k);
            $value = $v['Values'] ?? $v['Value'];

            if (is_array($value)) {
                if (count($value) === 1) {
                    $value = \Arr::first($value);
                } else if (empty($value)) {
                    $value = null;
                }
            }

            $key = $key->replaceFirst('Custom_', '')
                ->replaceFirst('str', '')
                ->snake();

            return ["$key" => $value];
        })->filter();
    }

    /**
     * Mutator for $this->email_address
     * @param $value
     */
    public function setEmailAddressAttribute($value)
    {
        $this->attributes['email'] = $value;
    }

    /**
     * Accessor for $this->nickname
     **/
    public function getNicknameAttribute($value)
    {
        return $this->dataSetValue('Custom_Nickname', $value);
    }

    /**
     * Accessor for $this->registration_id
     **/
    public
    function getRegistrationIdAttribute($value)
    {
        return \Arr::get($this->attributes, 'RegistrationID', $value);
    }

    /**
     * Accessor for $this->email
     **/
    public
    function getEmailAttribute($value)
    {
        return $this->dataSetValue('strEmail', $value ?? $this->form_data->get('email'));
    }

//* @property string $first_name

    /**
     * Accessor for $this->first_name
     **/
    public
    function getFirstNameAttribute($value)
    {
        return data_get($this->attributes, 'FirstName', $value);
    }

//* @property string $last_name

    /**
     * Accessor for $this->last_name
     **/
    public
    function getLastNameAttribute($value)
    {
        return data_get($this->attributes, 'LastName', $value);
    }

//* @property string $registered_at

    /**
     * Accessor for $this->registered_at
     **/
    public function getRegisteredAtAttribute($value)
    {
        return data_get($this->attributes, 'DateRegistered', $value);
    }

    /**
     * Mutator for $this->registration_date
     * @param $value
     */
    public function setRegistrationDateAttribute($value)
    {
        $this->attributes['registered_at'] = $value;
    }

    /**
     * Mutator for $this->DateRegistered
     * @param $value
     */
    public function setDateRegisteredAttribute($value)
    {
        $this->attributes['registered_at'] = $value;
    }



//* @property string $full_name

    /**
     * Accessor for $this->full_name
     **/
    public function getFullNameAttribute()
    {
        return "$this->first_name $this->last_name";
    }

//* @property string $title

    /**
     * Accessor for $this->title
     **/
    public function getTitleAttribute()
    {
        return $this->dataSetValue('Custom_Prefix', $this->prefix);
    }

//* @property string $company

    /**
     * Accessor for $this->company
     **/
    public function getCompanyAttribute()
    {
        return $this->dataSetValue('strEmployerName', $this->organization);
    }


    /**
     * Accessor for $this->type
     **/
    public function getTypeAttribute()
    {
        if ($this->has_tobacco_industry_affiliation) {
            return "FOR PROFIT - INDUSTRY";
        }

        switch ($this->AttendeeType) {
            case '2021 Member (For Profit)':
            case '2021 Non-Member (For Profit)':
            case '2021 Recent Graduate Member (For Profit)':
            case '2021 Recent Graduate Non-Member (For Profit)':
                return 'FOR PROFIT';
            case '2021 Member (Not For Profit)':
            case '2021 Non-Member (Not For Profit)':
            case '2021 Recent Graduate Member (Not For Profit)':
            case '2021 Recent Graduate Non-Member (Not For Profit)':
                return 'NOT FOR PROFIT';
            case '2021 Student/Trainee Member';
            case '2021 Student/Trainee Non-Member':
                return 'STUDENT';
            case '2021 LMIC (Low/Middle Income Country)':
            case '2021 Non-Member (Guest)':
            default:
                return 'OTHER';
        }
    }

    /**
     * Accessor for $this->has_tobacco_industry_affiliation
     **/
    public function getHasTobaccoIndustryAffiliationAttribute()
    {
        $value = $this->dataSetValue('Custom_TobaccoIndustryAffiliation');

        switch (strtolower($value)) {
            case 'no':
                return false;
            case 'yes':
                return true;
        }

        return false;
    }

    /*******************************************************
     * methods
     ******************************************************/
    public function loadEventMemberDetails()
    {
        $data = $this->api()->member_registration();
    }

    public function getEventId()
    {
        if (isset($this->EventID)) {
            return $this->EventID;
        }

        $results = $this->api()->findEventByName($this->EventName);

        if ($results->count() === 1) {
            $this->EventID = $results->first();
        }

        return $this->EventID;
    }

    public function dispatchSyncJob()
    {
        return ApiRequest::dispatch(
            fn() => $this->save(),
            $this->uniqueRequestId()
        )->onQueue('registration-sync');
    }

    public function uniqueRequestId()
    {
        return "ym-registration-request-" . $this->registration_id;
    }

    public function dataSetValue($key, $default = null)
    {
        return data_get($this->attributes, "DataSet.$key.@attributes.ExportValue", $default);
    }

    public function isCancelled()
    {
        return $this->Status === 'Cancelled';
    }

    /**
     * @return Collection
     */
    public function order()
    {
        return \Cache::remember("$this->registration_id-registration-order-details", 60 * 60, function () {
            return $this->api()->Sa_Commerce_Store_Order_Get([
                'InvoiceID' => $this->InvoiceID
            ]);
        })->data;
    }

    public function products()
    {
        $data = collect(data_get($this->order(), 'Order.Products'))->mapInto(Collection::class);

        if ($data->first()->has('ProductID') === false) {
            return $data->collapse();
        }

        return $data;
    }

    public function member()
    {
        return $this->api()->registrations();
    }
}
