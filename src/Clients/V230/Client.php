<?php namespace Braceyourself\Yourmembership\Clients\V230;

use Arr;
use Braceyourself\Yourmembership\Http\BaseRequest;
use Braceyourself\Yourmembership\Http\XmlRequest;
use Braceyourself\Yourmembership\Http\XmlResponse;
use Braceyourself\Yourmembership\Models\Event;
use Braceyourself\Yourmembership\Models\Person;
use Braceyourself\Yourmembership\Models\Registration;
use Braceyourself\Yourmembership\Yourmembership;
use Cache;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Collection;
use Log;
use Str;

/**
 *
 * @method Auth_Authenticate    180
 * @method Auth_CreateToken    180
 * @method Convert_ToEasternTime    240
 * @method Events_All_Search    60
 * @method Events_Event_Attendees_Get    60
 * @method Events_Event_Get    120
 * @method Feeds_Feed_Get    60
 * @method Feeds_Get    60
 * @method Member_Certifications_Get    60
 * @method Member_Certifications_Journal_Get    60
 * @method Member_Commerce_Store_GetOrderIDs    60
 * @method Member_Commerce_Store_Order_Get(array $args)    60
 * @method Member_Connection_Approve    20
 * @method Member_IsAuthenticated    60
 * @method Member_MediaGallery_Upload    20
 * @method Member_Messages_GetInbox    60
 * @method Member_Messages_GetSent    60
 * @method Member_Messages_Message_Read    60
 * @method Member_Messages_Message_Send    60
 * @method Member_Password_InitializeReset    30
 * @method Member_Password_Update    30
 * @method Member_Profile_Get    60
 * @method Member_Profile_GetMini    60
 * @method Member_Wall_Post    60
 * @method Members_Connections_Categories_Get    60
 * @method Members_Connections_Get    60
 * @method Members_MediaGallery_Albums_Get    60
 * @method Members_MediaGallery_Get    60
 * @method Members_MediaGallery_Item_Get    60
 * @method Members_Wall_Get    60
 * @method People_All_Search    60
 * @method People_Profile_Get    60
 * @method Sa_Auth_Authenticate    60
 * @method Sa_Certifications_All_Get    60
 * @method Sa_Certifications_CreditTypes_All_Get    60
 * @method Sa_Commerce_Product_Create    60
 * @method Sa_Commerce_Product_Get    60
 * @method Sa_Commerce_Product_Update    60
 * @method Sa_Commerce_Products_All_GetIDs    60
 * @method Sa_Commerce_Store_Order_Get(array $args)    60
 * @method Sa_Events_All_GetIDs(array $args)    60
 * @method Sa_Events_Event_Get(array $args)    60
 * @method Sa_Events_Event_Registration_Attendance_Update    120
 * @method Sa_Events_Event_Registration_Get(array $args)    600
 * @method Sa_Events_Event_Registrations_Find(array $args)    60
 * @method Sa_Events_Event_Registrations_GetIDs(array $args)    60
 * @method Sa_Export_All_InvoiceItems    6
 * @method Sa_Export_Career_Openings    6
 * @method Sa_Export_Donations_InvoiceItems    6
 * @method Sa_Export_Donations_Transactions    6
 * @method Sa_Export_Dues_InvoiceItems    6
 * @method Sa_Export_Dues_Transactions    6
 * @method Sa_Export_Event_Registrations(array $args)    60
 * @method Sa_Export_Finance_Batch    6
 * @method Sa_Export_Members    6
 * @method Sa_Export_Members_Groups    6
 * @method Sa_Export_Status(array $args)    120
 * @method Sa_Export_Store_InvoiceItems    6
 * @method Sa_Export_Store_Orders    6
 * @method Sa_Finance_Batch_Create    6
 * @method Sa_Finance_Batches_Get    60
 * @method Sa_Finance_Invoice_Payment_Create    120
 * @method Sa_Groups_Group_Create    60
 * @method Sa_Groups_Group_GetMembershipLog    30
 * @method Sa_Groups_Group_Update    60
 * @method Sa_Groups_GroupTypes_Get    60
 * @method Sa_Member_Certifications_Get    60
 * @method Sa_Member_Certifications_Journal_Get    60
 * @method Sa_Members_All_GetIDs    15
 * @method Sa_Members_All_MemberTypes_Get    60
 * @method Sa_Members_All_RecentActivity    20
 * @method Sa_Members_Certifications_JournalEntry_Create    120
 * @method Sa_Members_Commerce_Store_GetOrderIDs    60
 * @method Sa_Members_Events_Event_Registration_Get(array $args)    600
 * @method Sa_Members_Groups_Add    60
 * @method Sa_Members_Groups_Remove    60
 * @method Sa_Members_Profile_Create    90
 * @method Sa_Members_Referrals_Get    20
 * @method Sa_Members_SubAccounts_Get    180
 * @method Sa_NonMembers_All_GetIDs    15
 * @method Sa_NonMembers_Profile_Create    90
 * @method Sa_People_All_GetIDs(array $args)    15
 * @method Sa_People_Profile_Exists    600
 * @method Sa_People_Profile_FindID    600
 * @method Sa_People_Profile_Get(array $args)    600
 * @method Sa_People_Profile_Groups_Get    600
 * @method Sa_People_Profile_SetSyncDate    120
 * @method Sa_People_Profile_Update    120
 * @method Session_Abandon    90
 * @method Session_Create    999999999
 * @method Session_Ping
 */
class Client extends \Braceyourself\Yourmembership\Clients\Client
{

    protected $use_private_key;
    protected $call_data;
    protected $auth_path = "Session.Create";
    public static $cache_responses_for = 0;

    public function __call($name, $arguments)
    {
        $this->ensureSessionIdPresent();

        $method = Str::of($name)->replace('_', '.');

        $this->use_private_key = $method->lower()->startsWith('sa');
        $this->call_data = Arr::first($arguments);

        Log::channel('outgoing')->info("$method", Arr::wrap($this->call_data));

        $cache_key = $this->getApiConnectionName() . $method . serialize($this->call_data);

        return Cache::remember($cache_key, $this->getCacheTime(), function () use ($method) {

            return tap($this->request()->post($method), function (XmlResponse $response) use ($method) {

                if ($response->status() >= 300 || $response->xml('ErrCode') !== null) {
                    Log::channel('outgoing')->info("   Response [" . $response->status() . "]", $response->toArray());
                } else {
                    Log::channel('outgoing')->info("Done" . json_encode([
                            'method' => $method,
                        ], JSON_PRETTY_PRINT));
                }

            });


        });
    }

    public static function cacheResponses($seconds)
    {
        static::$cache_responses_for = $seconds;
    }

    /**
     * @return XmlRequest
     */
    protected function createRequest(callable $tap = null)
    {
        $r = (new XmlRequest())
            ->baseUrl('https://api.yourmembership.com')
            ->withOptions([
                'body' => array_merge($this->getDefaultData(), $this->getAuthData())
            ]);


        return $tap ? tap($r, $tap) : $r;

    }

    protected function getDefaultData()
    {
        return tap($this->getAuthData(), function ($data) {
            if (isset($this->session_id)) {
                $data['SessionID'] = $this->session_id;
            }
        });
    }


    protected function getAuthData()
    {
        $data = [
            'ApiKey'     => $this->use_private_key ? $this->config('private_key') : $this->config("api_key"),
            'SaPasscode' => $this->config('sa_passcode'),
            'Version'    => $this->config('api_version')
        ];


        if (isset($this->call_data)) {
            $data['Call'] = array_filter($this->call_data);
        }

        $data['CallID'] = Cache::increment($this->session_id);

        return $data;
    }

    public function authenticate()
    {
        return $this->Sa_Auth_Authenticate();
    }

    public function certifications()
    {
        return $this->Sa_Certifications_All_Get();
    }

    public function event($event_id): Event
    {
        return new Event(
            $this->Sa_Events_Event_Get(['EventID' => $event_id])->data->toArray(),
            app()->make("ym.$this->connection_name")
        );
    }

    public function member_registration()
    {
        return $this->Sa_Members_Events_Event_Registration_Get([
            'ID'      => $this->for->MemberID,
            'EventID' => $this->for->getEventId()
        ])->data->first();
    }

    public function findEventByName($event_name)
    {
        return $this->Sa_Events_All_GetIDs([
            'Name' => $event_name,
        ])->data;
    }


    public function createRegistrationsExport($event_id)
    {
        return $this->Sa_Export_Event_Registrations([
            'EventID' => $event_id
        ])->data->get('ExportID');
    }

    public function getExportUrl($export_id): string
    {
        do {
            $this->log('Attempting to retrieve ExportURI');

            $response = static::Sa_Export_Status([
                'ExportID' => $export_id
            ]);

            $status = static::getExportStatusMessage($response->data->get('Status'));

            $this->log("status: $status");

            $url = $response->data->get('ExportURI');

            if ($url === []) {
                $url = null;
            }

            $status = $response->data->get('Status');

            if ($url === null) {
                Log::info("Status: $status. Retrying in 3 seconds...");
                sleep(3);
            }

        } while ($url === null);

        $this->log("Export file retrieved: $url");

        return $url;
    }

    public function streamExport($url, callable $closure)
    {
        if ($stream = fopen($url, 'r')) {

            $header = null;

            while (($buffer = fgets($stream)) !== false) {
                $line = str_getcsv($buffer, ',', '');

                if ($header === null) {
                    $header = $line;
                    continue;
                }

                if (count($header) !== count($line)) {
                    continue;
                }

                $row = array_combine($header, $line);

                $data = collect($row)->mapWithKeys(function ($v, $k) {
                    $k = Str::of($k)
                        ->replace(' ', '_')
                        ->lower();

                    if ($v === "") {
                        $v = null;
                    }

                    return [(string)$k => $v];
                });

                $closure($data);

            }
        }
    }

    public function registrations(array $args = []): Collection
    {
        if (!$this->for instanceof Event) {
            throw new Exception("Unable to pull registrations for " . get_class($this->for));
        }

        $args['EventID'] = $this->for->EventID;
//        'Status'    => $status,
//            'FirstName' => $first_name,
//            'LastName'  => $last_name

        return collect($this->Sa_Events_Event_Registrations_Find($args)->data->first())
            ->map(function ($r) {
                return new Registration($r, app()->make("ym.$this->connection_name"));
            });
    }

    public function registration_ids($status = null): Collection
    {
        if (!$this->for instanceof Event) {
            throw new Exception("Unable to pull registrations for " . get_class($this->for));
        }

        return collect($this->Sa_Events_Event_Registrations_GetIDs([
            'EventID' => $this->for->EventID,
            'Status'  => $status,
        ])->data->first())->map(function ($item) {
            return new Registration(array_merge([
                'RegistrationID' => Arr::get($item, '@content'),
            ], Arr::get($item, '@attributes')));
        });
    }

    public function registration($id, $event_id = null): Registration
    {
        return new Registration(
            $this->Sa_Events_Event_Registration_Get([
                'RegistrationID' => $id
            ])->data->toArray(),
            app()->make("ym.$this->connection_name")
        );
    }

    public function people_ids(array $query = [])
    {
        return $this->Sa_People_All_GetIDs($query)->data
            ->map(fn($data) => collect($data)->map(fn($data) => collect($data)))
            ->get('People')
            ->get('ID');
    }

    public function person($id)
    {
        return new Person($this->Sa_People_Profile_Get([
            'ID' => $id
        ])->data->toArray());
    }

    public function event_ids(array $query = []): Collection
    {
        return collect($this->Sa_Events_All_GetIDs(array_filter($query))->data->first());
    }

    public function events()
    {
        return $this->Sa_Events_All_GetIDs()->data->flatten()->map(function ($event_id) {
            return $this->event($event_id);
        });
    }

    public function products()
    {
        return $this->cacheResponse(60 * 60)->Sa_Commerce_Products_All_GetIDs()->data->flatten()->map(function ($id) {
            $data = $this->cacheResponse(60 * 5)->Sa_Commerce_Product_Get(['ProductID' => $id])->data->toArray();

            return Yourmembership::mapInto('product', [$data, $this]);
        });
    }

    public function invoices()
    {
        $res = $this->Sa_Export_All_InvoiceItems();

        dd($res->xml());
    }

    public static function getExportStatusMessage($status_code)
    {
        switch ($status_code) {
            case 0:
                return 'Unknown';
            case 1:
                return "Working";
            case 2:
                return "Complete";
            case -1:
                return "Failure";
            default:
                throw new \Exception("Unknown export status code: $status_code");
        }

    }

}
