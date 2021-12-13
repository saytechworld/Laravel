<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\Event;
use Carbon\Carbon;

class AutoRejectExpiredEventNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rejectexpiredeventnotification:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reject Expired Event Notification';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try{
            $yesterday_date = Carbon::now()->subDays(1)->format('Y-m-d');
            $yesterday_start_time = $yesterday_date." 00:00:00";
            $yesterday_end_time = $yesterday_date." 23:59:59";
        
            $user_event =   Event::whereHas('event_attendants',function($query){
                                $query->where(['status' => 0, 'attendant_type' => 'A']);
                            })->with(['event_attendants' => function($queryone){
                                $queryone->where(['status' => 0, 'attendant_type' => 'A']);
                            }])->whereRaw("( (end_datetime IS NULL AND event_datetime between '".$yesterday_start_time."' AND '".$yesterday_end_time."' ) OR ( end_datetime  IS NOT NULL AND   end_datetime between '".$yesterday_start_time."' AND '".$yesterday_end_time."' ))")->get();
            if($user_event->count() > 0)
            {
                foreach ($user_event as $user_event_key => $user_event_value) {
                    $event_attendants = $user_event_value->event_attendants->pluck('user_id')->toArray();
                    $user_event_value->notifications()->whereIn('to_user_id',$event_attendants)->delete();
                    $user_event_value->event_attendants()->whereIn('user_id',$event_attendants)->update(['status' => 2]);
                }
            }
            $this->info('Cron Command Run successfully!');
        }catch (\Exception $e) {
            
        }
        
    }
}
