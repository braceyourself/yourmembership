<?php namespace Braceyourself\Yourmembership\Jobs;

use App\Facades\Srnt;
use Braceyourself\Yourmembership\YourmembershipApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LoadYmExportData implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $event_id;
    private YourmembershipApi $api;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(YourmembershipApi $api, $event_id)
    {
        $this->event_id = $event_id;
        $this->retry_after = 90;
        $this->queue = 'file-import';
        $this->tries = 3;
        $this->api = $api;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Cache::lock("loading-ym-export-data-$this->event_id")->get(function () {
            $export_id = \Cache::remember('registration-export', 60 * 10, function () {
                return $this->api->createRegistrationsExport($this->event_id);
            });

            $url = $this->api->getExportUrl($export_id);

            $this->api->importRegistrationExport($url);

        });
    }
}
