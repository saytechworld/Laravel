<?php

namespace App\Http\Controllers\Backend;

use App\Models\MailConfiguration;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception, DB;
use  App\Http\Requests\Backend\MailConfigurationRequest;


class MailConfigurationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $mailsettings = (new MailConfiguration)->newQuery();
        $mailsettings = $mailsettings->orderBy('name','ASC')->paginate(25);
        return view('Backend.mailsetting.index',compact('mailsettings'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('Backend.mailsetting.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(MailConfigurationRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = isset($input['status']) ? 1 : 0;
            $mailconfiguration = new MailConfiguration;
            if($mailconfiguration->fill($input)->save()){
                DB::commit();
                return redirect()->route('admin.system.mailconfiguration.index',['page' => $input['page']])->withSuccess('Mail Setting created successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MailConfiguration  $mailConfiguration
     * @return \Illuminate\Http\Response
     */
    public function show(MailConfiguration $mailconfiguration)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MailConfiguration  $mailConfiguration
     * @return \Illuminate\Http\Response
     */
    public function edit(MailConfiguration $mailconfiguration)
    {
        return view('Backend.mailsetting.edit',compact('mailconfiguration'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MailConfiguration  $mailConfiguration
     * @return \Illuminate\Http\Response
     */
    public function update(MailConfigurationRequest $request, MailConfiguration $mailconfiguration)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = isset($input['status']) ? 1 : 0;
            if($mailconfiguration->fill($input)->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Mail Setting updated successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }  
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MailConfiguration  $mailConfiguration
     * @return \Illuminate\Http\Response
     */
    public function destroy(MailConfiguration $mailConfiguration)
    {
        //
    }

    public function mark(Request $request, MailConfiguration $mailconfiguration, $status)
    {
        DB::beginTransaction();
        try {
            $mailconfiguration->status = $status == 1 ? 1 : 0;
            if($mailconfiguration->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Status updated successfully.');
            } 
            throw new Exception("Error Processing Request", 1);
        }catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withError($e->getMessage());
        }
    }
}
