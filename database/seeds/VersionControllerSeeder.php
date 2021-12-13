<?php

use Illuminate\Database\Seeder;
use App\Models\AndroidVersionController;
use App\Models\IosVersionController;

class VersionControllerSeeder extends Seeder
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

            $android_version = new AndroidVersionController();
            $android_version->version = '1.0    ';
            $android_version->status = 1;
            $android_version->save();

            $ios_version = new IosVersionController();
            $ios_version->version = '1.0';
            $ios_version->status = 1;
            $ios_version->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();		    // something went wrong
            echo "<pre>"; print_r($e->getMessage());
            exit;
        }
    }
}
