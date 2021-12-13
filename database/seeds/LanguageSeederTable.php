<?php

use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeederTable extends Seeder
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
			$lang_arr = array();
			$lang_arr = [
				[
					'title' => 'Bulgarian', 'lang_code' => 'BG', 'short_code' => 'br', 'status' => 1,
				],
                [
                    'title' => 'Croatian', 'lang_code' => 'HR', 'short_code' => 'hr', 'status' => 1,
                ],
				[
					'title' => 'Czech', 'lang_code' => 'CS', 'short_code' => 'cs', 'status' => 1,
				],
                [
                    'title' => 'Danish', 'lang_code' => 'DA', 'short_code' => 'da', 'status' => 1,
                ],
				[
					'title' => 'Dutch', 'lang_code' => 'NL', 'short_code' => 'nl', 'status' => 1,
				],
                [
                    'title' => 'English', 'lang_code' => 'EN', 'short_code' => 'en', 'status' => 1,
                ],
				[
					'title' => 'Estonian', 'lang_code' => 'ET', 'short_code' => 'et', 'status' => 1,
				],
                [
                    'title' => 'Finnish', 'lang_code' => 'FI', 'short_code' => 'fi', 'status' => 1,
                ],
				[
					'title' => 'French', 'lang_code' => 'FR', 'short_code' => 'fr', 'status' => 1,
				],
                [
                    'title' => 'German', 'lang_code' => 'DE', 'short_code' => 'de', 'status' => 1,
                ],
				[
					'title' => 'Greek', 'lang_code' => 'EL', 'short_code' => 'el', 'status' => 1,
				],
                [
                    'title' => 'Hungarian', 'lang_code' => 'HU', 'short_code' => 'hu', 'status' => 1,
                ],
				[
					'title' => 'Irish', 'lang_code' => 'GA', 'short_code' => 'ga', 'status' => 1,
				],
                [
                    'title' => 'Italian', 'lang_code' => 'IT', 'short_code' => 'it', 'status' => 1,
                ],
				[
					'title' => 'Latvian', 'lang_code' => 'LV', 'short_code' => 'lv', 'status' => 1,
				],
                [
                    'title' => 'Lithuanian', 'lang_code' => 'LT', 'short_code' => 'lt', 'status' => 1,
                ],
				[
					'title' => 'Maltese', 'lang_code' => 'MT', 'short_code' => 'mt', 'status' => 1,
				],
                [
                    'title' => 'Polish', 'lang_code' => 'PL', 'short_code' => 'pl', 'status' => 1,
                ],
				[
					'title' => 'Portuguese', 'lang_code' => 'PT', 'short_code' => 'pt', 'status' => 1,
				],
                [
                    'title' => 'Romanian', 'lang_code' => 'RO', 'short_code' => 'it', 'status' => 1,
                ],
				[
					'title' => 'Slovak', 'lang_code' => 'SK', 'short_code' => 'sk', 'status' => 1,
				],
                [
                    'title' => 'Slovenian', 'lang_code' => 'SL', 'short_code' => 'sl', 'status' => 1,
                ],
				[
					'title' => 'Spanish', 'lang_code' => 'ES', 'short_code' => 'es', 'status' => 1,
				],
                [
                    'title' => 'Swedish', 'lang_code' => 'SV', 'short_code' => 'sv', 'status' => 1,
                ],
			];
			
			foreach ($lang_arr as $key => $value) {
				$language = new Language;
				$language->fill($value)->save();
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
