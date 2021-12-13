<?php

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\Skill;
use Carbon\Carbon;


class GameSkillSeeder extends Seeder
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
			$game_arr = array();

			$game_arr = [
				[
					'title' => 'Equestrian', 'status' => 1,
					'skills' => [
						array('title' => 'Jumping','status' => 1),
                        array('title' => 'Dressage','status' => 1),
                        array('title' => 'Eventing','status' => 1),
                        array('title' => 'Driving / Para Driving','status' => 1),
                        array('title' => 'Endurance', 'status' => 1),
                        array('title' => 'Vaulting', 'status' => 1),
                        array('title' => 'Reining', 'status' => 1),
                        array('title' => 'Polo', 'status' => 1),
                        array('title' => 'Veterinary', 'status' => 1),
                        array('title' => 'Other', 'status' => 1),
					]
				],
				[
					'title' => 'Running', 'status' => 1,
					'skills' => [
						array('title' => 'Sprint','status' => 1),
                        array('title' => 'Marathon','status' => 1),
                        array('title' => 'Other', 'status' => 1),
					]
				],
				[
					'title' => 'Cycling', 'status' => 1,
					'skills' => [
						array('title' => 'Road','status' => 1),
                        array('title' => 'Mtb','status' => 1),
                        array('title' => 'Cyclocross','status' => 1),
                        array('title' => 'Other', 'status' => 1),
					]
				],
                [
                    'title' => 'Bodybuilding', 'status' => 1,
                    'skills' => [
                        array('title' => 'Musculation','status' => 1),
                        array('title' => 'Nutrition','status' => 1),
                        array('title' => 'Fitness','status' => 1),
                        array('title' => 'Other', 'status' => 1),
                    ]
                ],
			];
			//echo "<pre>"; print_r($game_arr); exit;
			foreach ($game_arr as $key => $value) {
				$g_arr = array();
				$g_arr['title'] =$value['title'];
				$g_arr['status'] =$value['status'];
				$game = new Game;
				if($game->fill($g_arr)->save()){
					foreach ($value['skills'] as $skillskey => $skillsvalue) {
						$s_arr = array();
						$s_arr['title'] =$skillsvalue['title'];
						$s_arr['status'] =$skillsvalue['status'];
						$s_arr['game_id'] =	$game->id;
						$skill = new Skill;
						$skill->fill($s_arr)->save();
					}
				}
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
