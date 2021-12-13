<?php

namespace App\Http\Controllers\Backend;

use App\Models\StaticPage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception, DB;
use  App\Http\Requests\Backend\StaticPageRequest;


class StaticPageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $staticpages = (new StaticPage)->newQuery();
        $staticpages = $staticpages->orderBy('title','ASC')->paginate(25);
        return view('Backend.staticpage.index',compact('staticpages'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('Backend.staticpage.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StaticPageRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = 1;
            if(!empty($input['image'])){
                $image_name = AwsBucketImageCompressUpload($input['image'], 'staticpages');
                $input['featured_image'] = !empty($image_name) ? $image_name : null;
                unset($input['image']);
            }
            $page = !empty($input['page']) ? $input['page'] : 1;
            $staticpage = new StaticPage;
            if($staticpage->fill($input)->save()){
                DB::commit();
                return redirect()->route('admin.system.staticpage.index',['page' => $page])->withSuccess('Page created successfully.');
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
     * @param  \App\Models\StaticPage  $staticPage
     * @return \Illuminate\Http\Response
     */
    public function show(StaticPage $staticPage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\StaticPage  $staticPage
     * @return \Illuminate\Http\Response
     */
    public function edit(StaticPage $staticpage)
    {
        return view('Backend.staticpage.edit',compact('staticpage'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StaticPage  $staticPage
     * @return \Illuminate\Http\Response
     */
    public function update(StaticPageRequest $request, StaticPage $staticpage)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = 1;
            if(!empty($input['image'])){
                $image_name = AwsBucketImageCompressUpload($input['image'], 'staticpages');
                if(empty($image_name)){
                    throw new Exception("Error occured while uploading image.", 1); 
                }
                $input['featured_image'] = $image_name;
                unset($input['image']);
            }
            $page = !empty($input['page']) ? $input['page'] : 1;
            if($staticpage->fill($input)->save()){
                DB::commit();
                return redirect()->route('admin.system.staticpage.index',['page' => $page])->withSuccess('Page updated successfully.');
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
     * @param  \App\Models\StaticPage  $staticPage
     * @return \Illuminate\Http\Response
     */
    public function destroy(StaticPage $staticPage)
    {
        //
    }

    public function mark(Request $request, StaticPage $staticpage, $status)
    {
        DB::beginTransaction();
        try {
            $staticpage->status = $status == 1 ? 1 : 0;
            if($staticpage->save()){
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
