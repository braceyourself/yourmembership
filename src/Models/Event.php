<?php namespace Braceyourself\Yourmembership\Models;

use Carbon\Carbon;

/**
 * Class Event
 * @package Braceyourself\Yourmembership\Models
 */
class Event extends Model
{
    protected $primaryKey = 'EventId';

    public function registrationCount()
    {
        return $this->registration_ids()->count();
    }

    public function openRegistrations()
    {
        return $this->registrations([
            'Status' => 1
        ]);
    }

    public function processedRegistrations()
    {
        return $this->registrations([
            'Status' => 2
        ]);
    }

    public function cancelledRegistrations()
    {
        return $this->registrations([
            'Status' => -1
        ]);
    }

    public function registrations(array $args = [])
    {
        return $this->api()->registrations($args);
    }

    public function registration_ids()
    {
        return $this->api()->registration_ids();
    }

    public function createRegistrationExport()
    {
        return $this->api()->createRegistrationsExport($this->EventId);
    }

    public function getExportUrl($export_id)
    {
        return $this->api()->getExportUrl($export_id);
    }

    public function isNotPast()
    {
        return Carbon::parse($this->EndDate)->isAfter(today());
    }

    public function startsAfter(\Illuminate\Support\Carbon $subMonth)
    {
        return $subMonth->isBefore(Carbon::parse($this->StartDate));
    }

    public function registration($registration_id): Registration
    {
        return $this->api()->registration($registration_id);
    }

    /*******************************************************
     * attributes
     ******************************************************/

}
