<?php namespace Braceyourself\Yourmembership\Clients;


use Braceyourself\Yourmembership\Http\BaseRequest;
use Braceyourself\Yourmembership\Http\BaseResponse;
use Braceyourself\Yourmembership\Http\HttpException;
use Braceyourself\Yourmembership\Http\Request;
use Braceyourself\Yourmembership\InvalidSessionId;
use Braceyourself\Yourmembership\Models\Event;
use Braceyourself\Yourmembership\Models\Model;
use Braceyourself\Yourmembership\Models\Person;
use Braceyourself\Yourmembership\Models\Registration;
use Braceyourself\Yourmembership\Yourmembership;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Nette\NotImplementedException;

/**
 * Class YourmembershipApi
 * @package Braceyourself\Yourmembership
 * @mixin BaseRequest
 */
class Client
{
    public static $cache_responses_for = 0;

    protected $session_id;
    /**
     * @var Factory
     */
    private $use_private_key;
    private $call_data;
    protected Model $for;

    private array $config;
    private $connection_name;
    protected $user_id;
    protected $auth_path = '/Ams/Authenticate';

    /**
     * YourmembershipApi constructor.
     */
    public function __construct(array $config, $connection_name)
    {
        $this->config = $config;
        $this->connection_name = $connection_name;
    }

    public function config($key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return data_get($this->config, $key, $default) ?? $default;
    }

    protected function getAuthPath()
    {
        return $this->auth_path;
    }

    protected function request()
    {
        return $this->createRequest();
    }

    /**
     * @return Request
     */
    protected function createRequest()
    {
        return (new Request())
            ->asJson()
            ->acceptJson()
            ->baseUrl('https://ws.yourmembership.com');
    }

    protected function getDefaultData()
    {
        if (isset($this->session_id)) {
            return [
                'SessionID' => $this->session_id
            ];
        }

        return $this->getAuthData();
    }

    protected function getAuthData()
    {
        return [
            'ClientID' => $this->config('client_id'),
            'UserType' => "Admin",
            'Username' => $this->config('api_key'),
            'Password' => $this->config('private_key'),
        ];
    }


    public function __call($name, $arguments)
    {
        // return the request if the called method is not a http verb
        if (!in_array($name, ['get', 'post', 'put', 'patch', 'delete', 'head'])) {
            return $this->request()->$name(...$arguments);
        }

        $this->ensureSessionIdPresent();

        /** @var BaseResponse $response */
        $response = $this->request()->withHeaders(['x-ss-id' => $this->session_id])
            // send the request
            ->$name(
            // path
                $this->preparePath(Arr::first($arguments), "/Ams/{$this->config('client_id')}/"),
                // data/query
                Arr::get($arguments, 1, [])
            );

        // throw errors
        if ($response->hasErrors()) {
            throw new HttpException($response->getErrorAsString());
        }

        return $response;
    }

    protected function ensureSessionIdPresent()
    {
        $ttl = 60 * 14;

        $this->session_id = Cache::remember("$this->connection_name-yourmembership-session-id", $ttl, function () {
            $response = $this->createSession();

            if (($user_id = $response->json('UserId')) !== null) {
                $this->user_id = $user_id;
            }

            return tap($response->getSessionId(), function ($session_id) use ($response) {
                if (is_null($session_id)) {
                    throw new InvalidSessionId($response);
                }

                Cache::increment($session_id);
            });

        });

    }

    /**
     * @return BaseResponse|\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
     */
    public function createSession()
    {
        return $this->request()->post($this->getAuthPath(), $this->getAuthData());
    }


    public function for($entity)
    {
        $this->for = $entity;

        return $this;
    }

    public static function cacheResponses($seconds)
    {
        static::$cache_responses_for = $seconds;
    }

    public function authenticate()
    {
        throw new NotImplementedException();
    }

    public function certifications()
    {

        throw new NotImplementedException();
    }

    public function futureEvents()
    {
        return $this->events()
            ->filter(fn(Event $e) => $e->Active
                && $e->isNotPast()
                && $e->startsAfter(today()->subMonth())
            );
    }

    public function events()
    {
        $res = $this->get("Events", [
            'PageSize'   => 100,
            'PageNumber' => 1
        ]);

        $class = Yourmembership::getMappedClass('event');

        return collect($res->json('EventsList'))
            ->map(fn($v) => new $class($v, $this));
    }


    public function event($event_id): Event
    {
        $page_size = 10;
        $page_number = 0;

        do {
            $res = $this->get("Events", [
                'PageSize'   => $page_size,
                'PageNumber' => ++$page_number
            ]);

            $data = collect($res->json('EventsList'));

            $has_next_page = $data->count() === $page_size;
            $event_data = $data->where('EventId', $event_id)->first();

        } while ($event_data === null && $has_next_page);

        $class = Yourmembership::getMappedClass('event');

        return new $class($event_data, $this);
    }

    public function member_registration()
    {
        throw new NotImplementedException();
    }


    public function findEventByName($event_name)
    {
        throw new NotImplementedException();
    }

    public function createRegistrationsExport($event_id)
    {
        throw new NotImplementedException();
    }

    public function getExportUrl($export_id)
    {
        throw new NotImplementedException();
    }

    public function registrations(array $query = []): Collection
    {
        $res = $this->get("Event/{EventID}/EventRegistrants", array_merge([
            'BypassCache' => 'true'
        ], $query))->json('EventRegistrantsList');

        $class = Yourmembership::getMappedClass('registration');

        return collect($res)->map(fn($v) => new $class($v, $this));
    }

    public function registration_ids($status = null): Collection
    {
        $res = $this->get("Event/{EventID}/EventRegistrationIDs");

        return collect($res->json('EventRegistrationsID'))->pluck('RegistrantID');
    }

    public function registration($id, $event_id = '{EventID}'): Registration
    {
        $data = $this->get("Event/$event_id/EventRegistrations", [
            'RegistrationID' => $id
        ])->json('EventRegistrationRegistrant');

        $class = Yourmembership::getMappedClass('registration');

        return new $class($data, $this);
    }

    public function people_ids(array $query = [])
    {
        $query = array_merge([
            'UserType' => 'All',
            'PageSize' => 100,
        ], $query);

        return collect(
            $this->get("PeopleIDs", $query)->json('IDList')
        );
    }

    public function person($id)
    {
        $res = $this->get("/Ams/{$this->config('client_id')}/People", [
            'ProfileID' => $id
        ]);

        $class = Yourmembership::getMappedClass('person');

        return new $class($res->json(), $this);
    }

    public function event_ids(array $query = []): Collection
    {
        $res = $this->get("Ams/{$this->config('client_id')}/EventIDs", array_merge($query, [
            'PageSize'   => 100,
            'PageNumber' => 1,
        ]));

        return collect($res->json("EventIDList"))
            ->map(fn($v) => data_get($v, 'ID'));
    }

    public function streamRegistrationExport($url, callable $closure)
    {
        throw new NotImplementedException();
    }

    public function importRegistrationExport($url)
    {
        $this->streamRegistrationExport($url, function ($row) {
            $user_class = $this->config('user_class');
            $user_class::storeCsvExport($row);
        });
    }

    private function preparePath($path, string $base_path)
    {
        $path = trim($path, "/");
        $base_path = trim("$base_path", "/");

        if (Str::of($path)->startsWith($base_path) === false) {
            $path = "/$base_path/$path";
        }

        if ($this->pathHasSubstitutions($path)) {
            $path = $this->replacePathSubstitutions($path);
        }

        return $path;
    }

    private function replacePathSubstitutions(string $path)
    {
        preg_match($pattern = "/{(\w*)}/mi", $path, $matches);

        unset($matches[0]);

        $matches = collect($matches)->map(function ($match) {
            if ($entity = $this->for) {
                $value = $entity->$match ?? $entity->getKey() ?? $entity->id;
            }

            return $value;
        })->toArray();

        return preg_replace_array($pattern, $matches, $path);
    }

    private function pathHasSubstitutions(string $path)
    {
        preg_match("/{(\w*)}/mi", $path, $matches);

        return count($matches) > 1;
    }

}
