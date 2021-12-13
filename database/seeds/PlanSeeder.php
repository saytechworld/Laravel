<?php

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
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
			$plan_arr = array();
			$plan_arr = [
				[
					'title' => 'Silver', 'description' => 'Silver', 'price' => 500, 'status' => 1, 'validity' => 1,
				],
				[
					'title' => 'Premium', 'description' => 'Premium', 'price' => 500, 'status' => 1, 'validity' => 1,
				],
				[
					'title' => 'Normal', 'description' => 'Normal', 'price' => 500, 'status' => 1, 'validity' => 1,
				],
			];
			
			foreach ($plan_arr as $key => $value) {
				$plan = new Plan;
				$plan->fill($value)->save();
			}
			DB::commit();
		    // all good
		} catch (\Exception $e) {
			DB::rollback();		    // something went wrong
			echo "<pre>"; print_r($e->getMessage());
			exit;
		}

    }
}
