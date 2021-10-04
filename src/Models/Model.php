<?php namespace Braceyourself\Yourmembership\Models;

use Braceyourself\Yourmembership\Clients\Client;
use Illuminate\Database\Eloquent\Model as BaseModel;

/**
 *
 * Class Model
 * @package Braceyourself\Yourmembership\Models
 */
abstract class Model extends BaseModel
{
    protected $guarded = [];
    private ?Client $api_client;

    public function __construct(array $attributes = [], Client $api = null)
    {
        parent::__construct($attributes);

        if (!empty($attributes) && is_null($api)) {
            throw new \Exception("Api Client required when creating " . static::class . " instance.");
        }

        $this->api_client = $api;
    }

    public function isFillable($key)
    {
        return !$this->isGuarded($key);
    }

    public function api(): Client
    {
        return optional($this->api_client)->for($this);
    }
}
