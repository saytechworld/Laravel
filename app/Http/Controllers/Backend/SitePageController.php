<?php

namespace App\Http\Controllers\Backend;

use App\Models\SitePage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB, Exception;
use  App\Http\Requests\Backend\SitePageRequest;

class SitePageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sitepages = (new SitePage)->newQuery();
        $sitepages = $sitepages->orderBy('name','ASC')->paginate(25);
        return view('Backend.sitepages.index',compact('sitepages'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('Backend.sitepages.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SitePageRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = isset($input['status']) ? 1 : 0;
            $input['optional'] = isset($input['optional']) ? 1 : 0;
            $sitepage = new SitePage;
            if($sitepage->fill($input)->save()){
                DB::commit();
                return redirect()->route('admin.system.sitepage.index',['page' => $input['page']])->withSuccess('Page created successfully.');
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
     * @param  \App\Models\SitePage  $sitePage
     * @return \Illuminate\Http\Response
     */
    public function show(SitePage $sitePage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SitePage  $sitePage
     * @return \Illuminate\Http\Response
     */
    public function edit(SitePage $sitepage)
    {
        return view('Backend.sitepages.edit',compact('sitepage'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SitePage  $sitePage
     * @return \Illuminate\Http\Response
     */
    public function update(SitePageRequest $request, SitePage $sitepage)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = isset($input['status']) ? 1 : 0;
            $input['optional'] = isset($input['optional']) ? 1 : 0;
            if($sitepage->fill($input)->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Page updated successfully.');
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
     * @param  \App\Models\SitePage  $sitePage
     * @return \Illuminate\Http\Response
     */
    public function destroy(SitePage $sitePage)
    {
        //
    }

    public function mark(Request $request, SitePage $sitepage, $status)
    {
        DB::beginTransaction();
        try {
            $sitepage->status = $status == 1 ? 1 : 0;
            if($sitepage->save()){
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
