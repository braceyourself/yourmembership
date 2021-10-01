<?php namespace Braceyourself\Yourmembership\Models;

use App\Jobs\ApiRequest;
use App\Models\Wordpress\Srnt\User;
use Braceyourself\Yourmembership\YourmembershipApi;

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
 *
 * Class Registration
 * @package Braceyourself\Yourmembership\Models
 */
class Registration extends Model
{
    protected $primaryKey = 'RegistrationID';
    protected $keyType = 'string';
    protected $appends = [
        'type'
    ];


    protected static function boot()
    {
        parent::boot();

        static::saving(function (Registration $model) {
            $model->updateUserViaApi();
            // exit the saving of this 'Registration' object
            return false;
        });
    }

    public function updateUserViaApi()
    {
        $this->loadDetails();

        if (!isset($this->email)) {
            return;
        }

        /** @var User $user */
        $user = User::query()->firstOrCreate([
            'user_login' => $this->email,
        ], [
            'user_pass' => 'SRNT2021',
        ]);

        $user->update([
            'user_email'      => $this->email,
            'user_nicename'   => $this->nickname,
            'user_registered' => $this->registered_at,
            'display_name'    => $this->full_name,
        ]);


        $user->saveMeta([
            'first_name'                        => $this->first_name,
            'last_name'                         => $this->last_name,
            'title'                             => $this->title,
            'company'                           => $this->company,
            'registration_id'                   => $this->registration_id,
            'strEmail'                          => $this->email,
            'BadgeNumber'                       => $this->BadgeNumber,
            'AttendeeType'                      => $this->AttendeeType,
            'Custom_Nickname'                   => $this->dataSetValue('Custom_Nickname'),
            'strEmployerName'                   => $this->dataSetValue('strEmployerName'),
            'Custom_VirtualMeetingPresenter'    => $this->dataSetValue('Custom_VirtualMeetingPresenter'),
            'Custom_PreConferenceSession'       => $this->dataSetValue('Custom_PreConferenceSession'),
            'Custom_TobaccoIndustryAffiliation' => $this->dataSetValue('Custom_TobaccoIndustryAffiliation'),
            'Sessions'                          => json_encode($this->Sessions),
            'Type'                              => $this->type,
            'updated_at'                        => now(),
        ]);

//            'wp_capabilities'                   => serialize(['attendee' => true, $this->type => true]),
        $user->capabilities = 'attendee';
        $user->capabilities = $this->type;

        \Log::info('Saved User: ' . $user->user_login);
    }

    public function loadDetails()
    {
        $details = $this->api()->registration($this->RegistrationID);

        $this->attributes = $details->getAttributes();

        return $this;
    }

    /*******************************************************
     * attributes
     ******************************************************/
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
        return $this->dataSetValue('strEmail', $value);
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


        dd(__FUNCTION__, $data);
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
}
