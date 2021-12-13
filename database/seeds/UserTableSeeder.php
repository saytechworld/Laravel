<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	DB::beginTransaction();
		try {
			$roles_arr = array(
				array('name' => 'admin', 'description' => 'Manage Backend Service', 'all' => 1, 'sort' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')),
				array('name' => 'subadmin', 'description' => 'Manage backend service that is assigned by super admin', 'all' => 0, 'sort' => 2, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')),
				array('name' => 'coach', 'description' => 'Manage Coach Panel', 'all' => 0, 'sort' => 3, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')),
				array('name' => 'athlete', 'description' => 'Manage Athelete Panel', 'all' => 0, 'sort' => 4, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')),
			);

		    if(Role::insert($roles_arr)){
		    	$user = User::create(['name' => 'Yatendra Saini', 'email' => 'yatendra@thinkstart.in', 'email_verified_at' => date('Y-m-d H:i:s'), 'password' => bcrypt('Yatendr@532'), 'confirmation_code' => str_random(40), 'status' => 1, 'confirmed' => 1, 'user_uuid' => Str::uuid()->toString()]);
		    	$user->user_details()->firstOrCreate(['user_id' => $user->id ]);
		    	$user->roles()->attach(1);

		    	$user_two = User::create(['name' => 'Anil', 'email' => 'anil@thinkstart.in', 'email_verified_at' => date('Y-m-d H:i:s'), 'password' => bcrypt('anil@532'), 'confirmation_code' => str_random(40), 'status' => 1, 'confirmed' => 1, 'user_uuid' => Str::uuid()->toString()]);
		    	$user_two->user_details()->firstOrCreate(['user_id' => $user_two->id ]);
		    	$user_two->roles()->attach(1);
		    	DB::commit();
		    	// all good
		    }
		    throw new Exception("Error Processing Request", 1);
		} catch (\Exception $e) {
		    DB::rollback();		    // something went wrong
		}
    }
}
