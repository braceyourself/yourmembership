<?php namespace Braceyourself\Yourmembership\Models;

use Braceyourself\Yourmembership\YourmembershipApi;
use Illuminate\Database\Eloquent\Model as BaseModel;

/**
 *
 * Class Model
 * @package Braceyourself\Yourmembership\Models
 */
abstract class Model extends BaseModel
{
    protected $guarded = [];

    public function isFillable($key)
    {
        return !$this->isGuarded($key);
    }

    public function api(): YourmembershipApi
    {
        return app()->make('yourmembership')->for($this);
    }
}
