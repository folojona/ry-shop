<?php

namespace Ry\Shop\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\Job;
use Ry\Shop\Models\Shop;
use Ry\Shop\Models\Customer;
use Ry\Shop\Models\Subscription;
use Mail;
use Carbon\Carbon;

class ExpiredReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ryshop:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder to expiring subscriptions';

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
        $customers = Customer::whereHas("subscriptions", function($query){
            $date = Carbon::now()->format('Y-m-d');
            $query->whereRaw("(DATEDIFF(DATE(ry_shop_subscriptions.expiry), ?) = 60 OR DATEDIFF(DATE(ry_shop_subscriptions.expiry), ?) = 30 OR DATEDIFF(DATE(ry_shop_subscriptions.expiry), ?) = 15 OR DATEDIFF(DATE(ry_shop_subscriptions.expiry), ?) = 7 OR DATEDIFF(DATE(ry_shop_subscriptions.expiry), ?) = 3) AND DATEDIFF(DATE(ry_shop_subscriptions.expiry), ?) > 0", [$date, $date, $date, $date, $date, $date]);
        })->get();
        foreach($customers as $customer) {
            Mail::send("ryappeldoffres::emails.expiry", [
                "subscriptions" => $customer->subscriptions()->whereRaw("(DATEDIFF(DATE(expiry), ?) = 60 OR DATEDIFF(DATE(expiry), ?) = 30 OR DATEDIFF(DATE(expiry), ?) = 15 OR DATEDIFF(DATE(expiry), ?) = 7 OR DATEDIFF(DATE(expiry), ?) = 3) AND DATEDIFF(DATE(expiry), ?) > 0", [$date, $date, $date, $date, $date, $date])->get(),
                "customer" => $customer,
                "shop" => Shop::find(1),
            ], function($message) use ($customer){
                $message->subject(env("SHOP", "TOPMORA SHOP")." - Renouvellement de vos services");
                $message->to($customer->owner->email, $customer->owner->name);
                $message->from(env("contact", "manager@topmora.com"), env("COMPANY", "TOPMORA SHOP"));
            });
        }
    }
}
