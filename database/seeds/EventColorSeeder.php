<?php

use Illuminate\Database\Seeder;
use App\Models\EventColor;

class EventColorSeeder extends Seeder
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
			$color_arr = array();
            $color_arr = [
				[
				    'name' => 'Red', 'color_code_id' => '4f8b7d62-0988-41b1-b095-dd9722d85br', 'color_code' => '#D50000'
				],
				[
				    'name' => 'Blue', 'color_code_id' => '4f8b7d62-0988-41b1-b095-dd9722d8545', 'color_code' => '#039BE5'
				],
				[
				    'name' => 'Green', 'color_code_id' => '4f8b7d62-09885-b1-b095-dd9722d85br', 'color_code' => '#33B679'
				],
				[
				    'name' => 'Yellow', 'color_code_id' => '454f8b7d62-0988-41bb095-dd9722d85br', 'color_code' => '#F6BF26'
				],
				[
				    'name' => 'Red1', 'color_code_id' => '4f8b7d62-07988-41bb095-dd9722d85br', 'color_code' => '#E67C73'
				],
				[
				    'name' => 'Red2', 'color_code_id' => '4f8b7d62-0878-41b1-b095-dd9722d85br', 'color_code' => '#F4511E'
				],
				[
				    'name' => 'Green1', 'color_code_id' => '4f8b7546d62-098-41b1-b095-dd9722d8545', 'color_code' => '#0B8043'
				],
				[
				    'name' => 'Blue1', 'color_code_id' => '42f8b7d62-0988-b1-b5095-dd9722d85br', 'color_code' => '#3F51B5'
				],
				[
				    'name' => 'Purple', 'color_code_id' => '4f87b7d62-0988-4145bb095-dd9722d85br', 'color_code' => '#7986CB'
				],
				[
				    'name' => 'Pink', 'color_code_id' => '4f8b754d62-0988-41bb04595-dd9722d85br', 'color_code' => '#8E24AA'
				],
				[
				    'name' => 'Brown', 'color_code_id' => '4f8b74d62-0988-41bb04595-dd9722d85br', 'color_code' => '#616161'
				],
			];
			
			foreach ($color_arr as $key => $value) {
				$color = new EventColor();
                $color->fill($value)->save();
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
