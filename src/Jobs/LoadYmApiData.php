<?php namespace Braceyourself\Yourmembership\Jobs;

use App\Models\Wordpress\Srnt\User;
use Braceyourself\Yourmembership\InvalidSessionId;
use Braceyourself\Yourmembership\Models\Event;
use Braceyourself\Yourmembership\Models\Registration;
use Braceyourself\Yourmembership\YourmembershipApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LoadYmApiData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $event_id;
    private YourmembershipApi $api;
    private $usermeta_class;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(YourmembershipApi $api, $event_id)
    {
        $this->api = $api;
        $this->event_id = $event_id;
    }

    public function tags()
    {
        return [
            class_basename(static::class)
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Cache::lock('loading-ym-data' . $this->event_id)->get(function () {
            try {
                $event = $this->api->event($this->event_id);
            } catch (InvalidSessionId $e) {
                $this->log("Exceeding request limit. Deferring.");
                return;
            }

            if (class_exists($this->api->getUserMetaClass())) {
                $this->createNewRegistrations($event);
            }

            if (class_exists($this->api->getUserClass()))
                $this->updateExistingUsers();
        });
    }

    private function log(string $message, array $context = null)
    {
        $message = '[' . static::class . "] $message";
        $args = [$message];
        if ($context !== null) {
            $args[] = $context;
        }

        \Log::info(...$args);

    }

    private function updateExistingUsers()
    {
        $existing = User::with('meta')
            ->whereHas('meta', function (Builder $query) {
                $query->where([
                    ['meta_key', 'registration_id'],
                    ['meta_value', '!=', null]
                ]);
            })
            ->whereHas('meta', function (Builder $query) {
                $query->where([
                    ['meta_key', 'updated_at'],
                    ['meta_value', '<', now()->subMinutes(2)]
                ]);
            })->orderBy(function (\Illuminate\Database\Query\Builder $query) {
                $query->from('usermeta')->where([
                    ['user_id', \DB::raw('wp_users.id')],
                    ['meta_key', 'updated_at']
                ])->select('meta_value');
            })
            ->get();

        \Log::info("Found " . $existing->count() . " users that need to be updated");

        $existing->each(function (User $user) {
            $user->syncWithApi();
        });
    }

    private function createNewRegistrations(Event $event)
    {
        $user_meta = $this->api->config('usermeta_class');

        $new_registrations = $event->registration_ids()->filter(function (Registration $r) use ($user_meta){
            return !$user_meta::where([
                ['meta_key', 'registration_id'],
                ['meta_value', $r->RegistrationID]
            ])->exists();
        });

        \Log::info("Found " . $new_registrations->count() . " new registrations.");

        $new_registrations->each(function (Registration $registration) use ($event) {
            $registration->dispatchSyncJob()->onQueue('new-registrations');
        });
    }

}
