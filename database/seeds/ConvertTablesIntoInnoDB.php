<?php

use Illuminate\Database\Seeder;

class ConvertTablesIntoInnoDB extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tables = DB::select('SHOW TABLES');
        $db_tables = json_decode(json_encode($tables, true),true);
        foreach($db_tables as $table)
		{
			$tbl_name = $table['Tables_in_coachbook'];
			DB::statement('ALTER TABLE ' . $tbl_name . ' ENGINE = InnoDB');
		}
    }
}
