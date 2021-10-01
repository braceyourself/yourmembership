<?php namespace Braceyourself\Yourmembership\Models;

/**
 * Class Event
 * @package Braceyourself\Yourmembership\Models
 */
class Event extends Model
{

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
}
