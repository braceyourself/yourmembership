<?php namespace Braceyourself\Yourmembership\Models;

class Person extends Model
{
    /**
     * Accessor for $this->id
     **/
    public function getIdAttribute()
    {
        return $this->attributes['ProfileID'] ?? null;
    }

}
