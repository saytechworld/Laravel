<?php

namespace App\Http\Controllers\Backend;

use App\Models\AndroidVersionController;
use App\Models\IosVersionController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB, Exception,Validator;

class VersionController extends Controller
{
    public function android(Request $request) {
        $android_versions = (new AndroidVersionController())->newQuery();
        $android_versions = $android_versions->paginate(10);
        return view('Backend.version.android.index',compact('android_versions'))
            ->with('i', ($request->input('page', 1) - 1) * 2);
    }

    public function ios(Request $request) {
        $ios_versions = (new IosVersionController())->newQuery();
        $ios_versions = $ios_versions->paginate(10);
        return view('Backend.version.ios.index',compact('ios_versions'))
            ->with('i', ($request->input('page', 1) - 1) * 2);
    }

    public function createAndroid(Request $request) {
        return view('Backend.version.android.create');
    }

    public function createIos(Request $request) {
        return view('Backend.version.ios.create');
    }

    public function storeAndroid(Request $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();

            $validator = Validator::make($input,[
                'version'   =>  'required|numeric',
                'status'   =>  'required|in:0,1',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $version = new AndroidVersionController();
            if($version->fill($input)->save()){
                DB::commit();
                return redirect()->route('admin.system.version.android.index',['page' => $input['page']])->withSuccess('Android Version created successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }

    public function storeIos(Request $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();

            $validator = Validator::make($input,[
                'version'   =>  'required|numeric',
                'status'   =>  'required|in:0,1',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $version = new IosVersionController();
            if($version->fill($input)->save()){
                DB::commit();
                return redirect()->route('admin.system.version.ios.index',['page' => $input['page']])->withSuccess('IOS Version created successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }
}
