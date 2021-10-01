<?php namespace Braceyourself\Yourmembership;


use App\Models\Wordpress\Srnt\User;
use Braceyourself\Yourmembership\Models\Event;
use Braceyourself\Yourmembership\Models\Model;
use Braceyourself\Yourmembership\Models\Person;
use Braceyourself\Yourmembership\Models\Registration;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Psy\Util\Str;
use Spatie\ArrayToXml\ArrayToXml;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;

/**
 * Class YourmembershipApi
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
 * @method Member_Commerce_Store_Order_Get    60
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
 * @method Sa_Commerce_Store_Order_Get    60
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
 *
 * @package Braceyourself\Yourmembership
 * @mixin Request
 */
class YourmembershipApi
{
    const BASE_URL = 'https://api.yourmembership.com';
    public static $cache_responses_for = 0;

    private $session_id;
    /**
     * @var Factory
     */
    private $use_private_key;
    private $call_data;
    protected Model $for;

    private Request $request;
    private array $config;

    /**
     * YourmembershipApi constructor.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function config($key, $default = null)
    {
        return data_get($this->config, $key, $default) ?? $default;
    }

    public function __call($name, $arguments)
    {
        $this->ensureSessionIdPresent();

        $method = \Str::of($name)->replace('_', '.');

        $this->use_private_key = $method->lower()->startsWith('sa');
        $this->call_data = \Arr::first($arguments);

        \Log::channel('outgoing')->info("$method", \Arr::wrap($this->call_data));
        $start = now();

        try {
            /** @var Response $response */
            $response = \Cache::remember(
                $method . serialize($this->call_data),
                static::$cache_responses_for,
                function () use ($method) {
                    return $this->request()->post($method);
                }
            );
        } catch (\Exception $e) {
            \Log::error("Exception - {$e->getMessage()}", optional($response)->json());
        }

        $end = now();


        if ($response->status() >= 300) {
            \Log::channel('outgoing')->info("   Response [" . $response->status() . "]", $response->data->toArray());
        } else {
            \Log::channel('outgoing')->info("Done" . json_encode([
                    'method'       => $method,
                    'request_time' => $end->since($start)
                ], JSON_PRETTY_PRINT));
        }


        return $response;
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
            $this->Sa_Events_Event_Get(['EventID' => $event_id])->data->toArray()
        );
    }

    public function for($entity)
    {
        $this->for = $entity;

        return $this;
    }

    public function registrations(array $args = [])
    {
        if (!$this->for instanceof Event) {
            throw new \Exception("Unable to pull registrations for " . get_class($this->for));
        }

        $args['EventID'] = $this->for->EventID;
//        'Status'    => $status,
//            'FirstName' => $first_name,
//            'LastName'  => $last_name

        return collect(
            $this->Sa_Events_Event_Registrations_Find($args)->data->first()
        )
            ->mapInto(Registration::class);
    }

    public function registration_ids($status = null)
    {
        if (!$this->for instanceof Event) {
            throw new \Exception("Unable to pull registrations for " . get_class($this->for));
        }

        return collect($this->Sa_Events_Event_Registrations_GetIDs([
            'EventID' => $this->for->EventID,
            'Status'  => $status,
        ])->data->first())->map(function ($item) {
            return new Registration(array_merge([
                'RegistrationID' => \Arr::get($item, '@content'),
            ], \Arr::get($item, '@attributes')));
        });

    }

    public function registration($id)
    {
        /** @var Response $response */
        $response = $this->Sa_Events_Event_Registration_Get([
            'RegistrationID' => $id
        ]);

        return new Registration($response->data->toArray());
    }

    public function people_ids(CarbonInterface $timestamp = null, $website_id = null)
    {
        return $this->Sa_People_All_GetIDs([
            'Timestamp' => $timestamp,
            'WebsiteID' => $website_id
        ])->data
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


    /**
     * @param CarbonInterface|null $start_date
     * @param CarbonInterface|null $end_date
     * @param string|null $name
     * @param string|null $status
     * @param CarbonInterface|null $last_modified
     * @return \Illuminate\Support\Collection
     */
    public function event_ids(CarbonInterface $start_date = null, CarbonInterface $end_date = null, string $name = null, string $status = null, CarbonInterface $last_modified = null)
    {
        return collect($this->Sa_Events_All_GetIDs(array_filter([
            'StartDate'        => $start_date,
            'EndDate'          => $end_date,
            'Name'             => $name,
            'Status'           => $status,
            'LastModifiedDate' => $last_modified,
        ]))->data->first());
    }


    private function request()
    {
        return (new Request())
            ->baseUrl(static::BASE_URL)
            ->withOptions([
                'body' => $this->getDefaultData()
            ]);
    }

    private function getDefaultData()
    {
        $data = [
            'ApiKey'     => $this->use_private_key ? $this->config('private_key') : $this->config("api_key"),
            'SaPasscode' => $this->config('sa_passcode'),
            'Version'    => $this->config('api_version', '2.30')
        ];

        if (isset($this->session_id)) {
            $data['SessionID'] = $this->session_id;
        }

        if (isset($this->call_data)) {
            $data['Call'] = array_filter($this->call_data);
        }

        $data['CallID'] = \Cache::increment($this->session_id);

        return $data;
    }

    private function ensureSessionIdPresent()
    {
        $this->session_id = \Cache::remember('yourmembership-session-id', 60, function () {
            \Log::info('Requesting new Yourmembership session_id');
            $response = $this->createSession();
            $session_id = $response->xml(['Session.Create', 'SessionID']);

            if (is_null($session_id)) {
                throw new InvalidSessionId("Session Id is null: [ErrCode: " . $response->xml('ErrCode') . "] ErrDesc: " . $response->xml('ErrDesc'));
            }

            \Cache::increment($session_id);

            return $session_id;
        });

    }

    public function createSession()
    {
        return $this->request()->post('Session.Create');
    }


    public static function cacheResponses($seconds)
    {
        static::$cache_responses_for = $seconds;
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

    public function getExportUrl($export_id)
    {
        do {
            \Log::info('Attempting to retrieve ExportURI');

            $response = static::Sa_Export_Status([
                'ExportID' => $export_id
            ]);

            $url = $response->data->get('ExportURI');
            if ($url === []) {
                $url = null;
            }
            $status = $response->data->get('Status');

            if ($url === null) {
                \Log::info("Status: $status. Retrying in 3 seconds...");
                sleep(3);
            }

        } while ($url === null);

        try {
            \Log::info("Export file retrieved: $url");
        } finally {
            dump($url);
        }

        return $url;
    }

    public function importRegistrationExport($url)
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
                    $k = \Str::of($k)
                        ->replace(' ', '_')
                        ->lower();

                    if ($v === "") {
                        $v = null;
                    }

                    return [(string)$k => $v];
                });

                /** @var \Corcel\Model\User $user_class */
                $user_class = $this->config('user_class');
                $user_class::storeCsvExport($data);

            }
        }
    }

    public function getUserMetaClass()
    {
        return $this->config('usermeta_class');
    }

    public function getUserClass()
    {
        return $this->config('user_class');
    }
}
