<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ThemeColor;
use DB, Exception,Validator;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    
    public function fetchThemeColor(Request $request)
    {
    	$theme_colors = ThemeColor::with(['parent_theme_colors','child_theme_colors'])->paginate(25);
        return view('Backend.theme.color.index',compact('theme_colors'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    public function createThemeColor()
    {
    	$theme_colors = ThemeColor::whereNull('parent_color_id')->get();
    	return view('Backend.theme.color.create',compact('theme_colors'));
    }

    public function storeThemeColor(Request $request)
    {
    	DB::beginTransaction();
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
	          'name' => 'required|max:150',
	          'color_code' => 'required|array|min:3|max:3',
	           "color_code.R"  => "required",
	           "color_code.G"  => "required",
	           "color_code.B"  => "required",

	        ]);
	        if ($validator->fails()) {
	          throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
        	}
        	$input['color_code'] = json_encode($input['color_code']);
      		$input['theme_color_code_id'] = Str::uuid()->toString();
      		$theme_color = new ThemeColor;
      		if($theme_color->fill($input)->save()){
                //DB::commit();
                return redirect()->route('admin.system.themecolor.index',['page' => $input['page']])->withSuccess('Color created successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
        	DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }


    public function editThemeColor(ThemeColor $themecolor)
    {
    	$theme_colors = ThemeColor::whereNull('parent_color_id')->get();
    	return view('Backend.theme.color.edit',compact('theme_colors','themecolor'));
    }

    public function updateThemeColor(Request $request, ThemeColor $themecolor)
    {
    	DB::beginTransaction();
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
	          'name' => 'required|max:150',
	          'color_code' => 'required|array|min:3|max:3',
	           "color_code.R"  => "required",
	           "color_code.G"  => "required",
	           "color_code.B"  => "required",

	        ]);
	        if ($validator->fails()) {
	          throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
        	}
        	$input['color_code'] = json_encode($input['color_code']);
      		$input['theme_color_code_id'] = Str::uuid()->toString();
      		if($themecolor->fill($input)->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Color updated successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
        	DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }
}
