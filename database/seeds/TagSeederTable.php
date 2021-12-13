<?php

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeederTable extends Seeder
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
			$tag_arr = array();
			$tag_arr = [
				[
					'title' => 'General', 'status' => 1, 
				],
				[
					'title' => 'Training', 'status' => 1, 
				],
				[
					'title' => 'Competition', 'status' => 1, 
				],
				[
					'title' => 'Athlete', 'status' => 1, 
				],
				[
					'title' => 'Coach', 'status' => 1, 
				],
				[
					'title' => 'Coaching', 'status' => 1, 
				],
				[
					'title' => 'Equestrian', 'status' => 1, 
				],
				[
					'title' => 'Jumping', 'status' => 1, 
				],
				[
					'title' => 'Dressage', 'status' => 1, 
				],
				[
					'title' => 'Eventing', 'status' => 1, 
				],
				[
					'title' => 'Driving / Para Driving', 'status' => 1, 
				],
				[
					'title' => 'Endurance', 'status' => 1, 
				],
				[
					'title' => 'Vaulting', 'status' => 1, 
				],
				[
					'title' => 'Reining', 'status' => 1, 
				],
				[
					'title' => 'Polo', 'status' => 1, 
				],
				[
					'title' => 'Running', 'status' => 1, 
				],
				[
					'title' => 'Cycling', 'status' => 1, 
				],
				[
					'title' => 'Other', 'status' => 1, 
				],
			];
			
			foreach ($tag_arr as $key => $value) {
				$tag = new Tag;
				$tag->fill($value)->save();
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





