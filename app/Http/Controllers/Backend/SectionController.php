<?php

namespace App\Http\Controllers\Backend;

use App\Models\Section;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception, DB;
use  App\Http\Requests\Backend\SectionRequest;
use App\Models\SectionTemplate;
use App\Models\SitePage;
use Illuminate\Support\Str;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $input = $request->all();
        $sections = (new Section)->newQuery();
        if(!empty($input['q'])){
           $sections->whereRaw("(title LIKE '%".$input['q']."%' OR title LIKE '%".$input['q']."%' )");
        }
        $sections = $sections->orderBy('title','ASC')->paginate(25);
        return view('Backend.section.index',compact('sections'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $sitepages = SitePage::where('status',1)->pluck('name','id');
        return view('Backend.section.create',compact('sitepages'));  
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SectionRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            /*$fileNameExt = $input['section_layout'][0]->getClientOriginalName();
            $fileName = pathinfo($fileNameExt, PATHINFO_FILENAME);
                $fileExt = $input['section_layout'][0]->getClientOriginalExtension();
            */
            echo "<pre>"; print_r($input); 
            exit;
            $input['status'] = isset($input['status']) ? 1 : 0;
            $input['default_section'] = isset($input['default_section']) ? 1 : 0;
            $section_position = Section::max('position');
            $input['position'] = empty($section_position) ? 1 : $section_position+1; 
            $section = new Section;
            if($section->fill($input)->save()){
                foreach($input['section_template'] as $key => $val){
                    $section->section_templates()->create($val);
                }
                if($section->default_section == 0){
                    $section->section_pages()->attach($input['section_page']);
                }
                DB::commit();
                return redirect()->back()->withSuccess('Section created successfully.');
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
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    public function show(Section $section)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    public function edit(Section $section)
    {
        $sitepages = SitePage::where('status',1)->pluck('name','id');
        $section_pages = $section->section_pages()->pluck('id')->toArray();
        return view('Backend.section.edit',compact('section','sitepages','section_pages'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    public function update(SectionRequest $request, Section $section)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = isset($input['status']) ? 1 : 0;
            if($section->fill($input)->save()){
                foreach($input['section_template'] as $key => $val){
                    //$val['layout_encoded_id'] = Str::uuid()->toString();
                    if(!empty($val['id'])){
                        $section_templates = SectionTemplate::find($val['id']);
                        $section_templates->fill($val)->save();
                    }else{
                        $section->section_templates()->create($val);
                    }
                }
                DB::commit();
                return redirect()->back()->withSuccess('Section updated successfully.');
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
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    public function destroy(Section $section)
    {
        //
    }

    public function mark(Request $request, Section $section, $status)
    {
        DB::beginTransaction();
        try {
            $section->status = $status == 1 ? 1 : 0;
            if($section->save()){
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
