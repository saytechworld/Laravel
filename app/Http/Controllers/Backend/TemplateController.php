<?php

namespace App\Http\Controllers\Backend;

use App\Models\Template;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Exception, DB;
use  App\Http\Requests\Backend\TemplateRequest;
use  App\Http\Requests\Backend\TemplatePageRequest;
use  App\Http\Requests\Backend\TemplatePageSectionRequest;
use  App\Http\Requests\Backend\TemplateSectionRequest;
use Illuminate\Support\Str;
use App\Models\SitePage;
use App\Models\Section;

class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $templates = (new Template)->newQuery();
        $templates = $templates->orderBy('title','ASC')->paginate(25);
        return view('Backend.templates.index',compact('templates'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::whereRaw("(parent_id IS NULL AND status = 1)")->pluck('name','id');
        return view('Backend.templates.create',compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TemplateRequest $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(isset($request->validator) && $request->validator->fails()){
                    throw new Exception(implode('<br/>',$request->validator->errors()->all()), 1);
                }
                $input = $request->all();
                $input['status'] = isset($input['status']) ? 1 : 0;
                $input['template_encoded_id'] = Str::uuid()->toString();
                $imagename = imagecompressupload($input['featured_image'], 'templates'); 
                if(empty($imagename)){
                    throw new Exception("Error occur while uploading image.", 1);
                } 
                $input['featured_image'] = $imagename;
                $template = new Template;
                if($template->fill($input)->save()){
                    $default_bootstrap_files = array('jquery.min.js','bootstrap.min.js');
                    foreach($default_bootstrap_files as $default_bootstrap_files_key => $default_bootstrap_files_val)
                    {
                        $default_upload_file = upload_default_bootstrap_template_file($default_bootstrap_files_val, 'templates/'.$template->slug, 'js');
                        if(!empty($default_upload_file)){
                            $template->template_files()->create(['file_name' => $default_bootstrap_files_val, 'file_type' => 3]);
                        }
                    }

                    $template->html = $template->slug;
                    $template->save();
                    $fontzip_uploaded = CreateExtractTemplateFontFolder($input['fonts'], 'templates/'.$template->slug);
                    if(empty($fontzip_uploaded)){
                        throw new Exception("Error occur while uploading font files.", 1);
                    }
                    $template->template_categories()->attach($input['category_id']);
                    foreach($input['template_files'] as $filekey => $file_type){
                        if(count($file_type) > 0 ){
                            foreach($file_type as $file_type_key => $filename){
                                $upload_file = template_default_file_upload($filename, 'templates/'.$template->slug);
                                if(!empty($upload_file)){
                                    $checkExt = $filename->getClientOriginalExtension() == 'css' ? 2 : ($filename->getClientOriginalExtension() == 'js' ? 3 : 1);
                                    $template->template_files()->create(['file_name' => $upload_file, 'file_type' => $checkExt]);
                                }
                            }
                        }
                    }
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = "Template created successfully.";
                    $this->WebApiArray['data']['result'] = $template;
                    $this->WebApiArray['data']['html'] =  view('Backend.templates.ajax.createpage',compact('template'))->render();
                    $this->WebApiArray['error'] = false;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Http request not allow", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }



    public function fetchSitePage(Request $request, $id)
    {
        try {
            if($request->ajax()){
                $template = Template::where('id',$id)->first();
                if(!empty($template)){
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = "Template fetched successfully.";
                    $this->WebApiArray['data']['result'] =  view('Backend.templates.ajax.createpage',compact('template'))->render();
                    $this->WebApiArray['error'] = false;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Template not found.", 1);
            }
            throw new Exception("Http request not allow", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }


    public function storeTemplateSitePage(TemplatePageRequest $request, Template $template)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(isset($request->validator) && $request->validator->fails()){
                    throw new Exception(implode('<br/>',$request->validator->errors()->all()), 1);
                }
                $input = $request->all();
                $input['status'] = isset($input['status']) ? 1 : 0;
                $input['optional'] = isset($input['optional']) ? 1 : 0;
                $input['site_page_ux_id'] = Str::uuid()->toString();
                $sitepage = new SitePage;
                if($sitepage->fill($input)->save()){
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = "Page created successfully.";
                    $this->WebApiArray['data']['result'] = $sitepage;
                    $this->WebApiArray['data']['html'] = view('Backend.templates.ajax.create_section',compact('template','sitepage'))->render();
                    $this->WebApiArray['error'] = false;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Http request not allow", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function storeTemplatePageSection(TemplatePageSectionRequest $request, Template $template, SitePage $sitepage)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                $input = $request->all();
                if(isset($request->validator) && $request->validator->fails()){
                    throw new Exception(implode('<br/>',$request->validator->errors()->all()), 1);
                }
                if($sitepage->template_id != $template->id){
                    throw new Exception("This page id does not belongs to this template.", 1);
                }
                $input['status'] = isset($input['status']) ? 1 : 0;
                $checksection = Section::where(['site_page_id' => $sitepage->id, 'title' => $input['title']])->first();
                if(!empty($checksection))
                {
                    throw new Exception("This section is already taken in this page.", 1);
                }
                $section_position  = Section::where(['site_page_id' => $sitepage->id,'template_id' => $template->id])->max('position'); 
                $input['position'] = empty($section_position) ? 1 : $section_position+1;
                if(!empty($input['section_layout']['js'])){
                    foreach($input['section_layout']['js'] as $js_key => $js_val)
                    {
                        if($js_val->getClientOriginalExtension() != 'js'){
                            throw new Exception("The js  must be a file of type: js", 1);
                        }
                    }
                }
                if(!empty($input['section_layout']['css'])){
                    foreach($input['section_layout']['css'] as $css_key => $css_val)
                    {
                        if($css_val->getClientOriginalExtension() != 'css'){
                            throw new Exception("The css  must be a file of type: css", 1);
                        }
                    }
                }
                $input['default_section'] = 0;
                $input['site_page_id'] = $input['site_page_id'];
                $input['section_ux_id'] = Str::uuid()->toString();
                $input['section_type'] = 5;
                $section = new Section;
                if($section->fill($input)->save()){
                    foreach($input['section_template'] as $key => $val){
                        $section->section_templates()->create([ 'layout_encoded_id' => Str::uuid()->toString(), 'content' => $val['content']]);
                    }
                    if(!empty($input['section_layout']['js'])){
                        foreach($input['section_layout']['js'] as $js_file)
                        {
                            $uploaded_js = template_section_file_upload($js_file, $template->slug,  $section->slug, 'js');
                            if(!empty($uploaded_js)){
                                $section->section_layouts()->create(['file_name' => $uploaded_js, 'file_type' => 3]);
                            }
                        }
                    }
                    if(!empty($input['section_layout']['css'])){
                        foreach($input['section_layout']['css'] as $css_file)
                        {
                            $uploaded_css = template_section_file_upload($css_file, $template->slug,  $section->slug, 'css');
                            if(!empty($uploaded_css)){
                                $section->section_layouts()->create(['file_name' => $uploaded_css, 'file_type' => 2]);
                            }
                        }
                    }
                    if(!empty($input['section_layout']['image'])){
                        foreach($input['section_layout']['image'] as $image_file)
                        {
                            $uploaded_images = template_section_file_upload($image_file, $template->slug,  $section->slug, 'images');
                            if(!empty($uploaded_images)){
                                $section->section_layouts()->create(['file_name' => $uploaded_images, 'file_type' => 1]);
                            }
                        }
                    }
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = "Section created successfully.";
                    $this->WebApiArray['data']['html'] = view('Backend.templates.ajax.create_section',compact('template','sitepage'))->render();
                    $this->WebApiArray['error'] = false;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Http request not allow", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }


    public function createDefaultTemplateSection(Request $request, Template $template)
    {
        return view('Backend.templates.sections.create',compact('template'));  
    }


    public function storeDefaultTemplateSection(TemplateSectionRequest $request, Template $template)
    {
        DB::beginTransaction();
        try {
                $input = $request->all();
               
                if(isset($request->validator) && $request->validator->fails()){
                    throw new Exception(implode('<br/>',$request->validator->errors()->all()), 1);
                }
                if($input['section_type'] == 1 || $input['section_type'] == 2 ||  $input['section_type'] == 3){
                   $checksection  = Section::where(['template_id' => $template->id, 'section_type' => $input['section_type'] ])->first();
                    $section_type = $input['section_type'] == 1 ? 'Header Section' : ($input['section_type'] == 2 ? 'Footer Section' : 'Blog Section');
                    if(!empty($checksection)){
                        throw new Exception("You can't add ".$section_type." again.", 1);
                    } 
                }
                $input['default_section'] = $input['section_type'] == 4 ? 1 : 0;
                $input['template_id'] = $template->id;
                $input['status'] = isset($input['status']) ? 1 : 0;
                $input['site_page_id'] = null;
                $input['section_ux_id'] = Str::uuid()->toString();
                if($input['section_type'] == 1 || $input['section_type'] == 2){
                    $input['position'] =  $input['section_type'];
                }else{
                    $section_position = Section::doesnthave('template_section_pages')
                                                ->whereRaw("(template_id = ".$template->id." AND ( section_type = 3 OR section_type = 4 ) )")
                                                ->max('position');
                    $input['position'] = empty($section_position) ? 1 : $section_position+1; 
                }
                if(!empty($input['section_layout']['js'])){
                    foreach($input['section_layout']['js'] as $js_key => $js_val)
                    {
                        if($js_val->getClientOriginalExtension() != 'js'){
                            throw new Exception("The js  must be a file of type: js", 1);
                        }
                    }
                }
                if(!empty($input['section_layout']['css'])){
                    foreach($input['section_layout']['css'] as $css_key => $css_val)
                    {
                        if($css_val->getClientOriginalExtension() != 'css'){
                            throw new Exception("The css  must be a file of type: css", 1);
                        }
                    }
                }
                $section = new Section;
                if($section->fill($input)->save()){
                    foreach($input['section_template'] as $key => $val){
                        $section->section_templates()->create([ 'layout_encoded_id' => Str::uuid()->toString(), 'content' => $val['content']]);
                    }
                    if(!empty($input['section_layout']['js'])){
                        foreach($input['section_layout']['js'] as $js_file)
                        {
                           $uploaded_js = template_section_file_upload($js_file, $template->slug, $section->slug,'js');
                            if(!empty($uploaded_js)){
                                $section->section_layouts()->create(['file_name' => $uploaded_js, 'file_type' => 3]);
                            }
                        }
                    }
                    if(!empty($input['section_layout']['css'])){
                        foreach($input['section_layout']['css'] as $css_file)
                        {
                            $uploaded_css = template_section_file_upload($css_file, $template->slug, $section->slug,'css');
                            if(!empty($uploaded_css)){
                                $section->section_layouts()->create(['file_name' => $uploaded_css, 'file_type' => 2]);
                            }
                        }
                    }
                    if(!empty($input['section_layout']['image'])){
                        foreach($input['section_layout']['image'] as $image_file)
                        {
                            $uploaded_images = template_section_file_upload($image_file, $template->slug, $section->slug,'images');
                            if(!empty($uploaded_images)){
                                $section->section_layouts()->create(['file_name' => $uploaded_images, 'file_type' => 1]);
                            }
                        }
                    }
                    DB::commit();
                    return redirect()->back()->withSuccess("Section created successfully.");
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
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function show(Template $template)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function edit(Template $template)
    {
        exit("work in progress");
        $categories = Category::whereRaw("(parent_id IS NULL AND status = 1)")->pluck('name','id');
        $template_categories = $template->template_categories()->pluck('id');
        return view('Backend.templates.edit',compact('template','categories','template_categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function update(TemplateRequest $request, Template $template)
    {
        exit("work in progress");
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = isset($input['status']) ? 1 : 0;
            if(isset($input['featured_image'])){
                $imagename = imagecompressupload($input['featured_image'], 'templates'); 
                if(empty($imagename)){
                    throw new Exception("Error occur while uploading image.", 1);
                }
                $input['featured_image'] = $imagename;
            }
            if($template->fill($input)->save()){
                $template->template_categories()->sync($input['category_id']);
                DB::commit();
                return redirect()->back()->withSuccess('Template updated successfully.');
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
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function destroy(Template $template)
    {
        //
    }

    public function mark(Request $request, Template $template, $status)
    {
        DB::beginTransaction();
        try {
            $template->status = $status == 1 ? 1 : 0;
            if($template->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Status updated successfully.');
            } 
            throw new Exception("Error Processing Request", 1);
        }catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withError($e->getMessage());
        }
    }


    /*
    public function store(TemplateRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = isset($input['status']) ? 1 : 0;
            $imagename = imagecompressupload($input['featured_image'], 'templates'); 
            if(empty($imagename)){
                throw new Exception("Error occur while uploading image.", 1);
            } 
            $input['featured_image'] = $imagename;
            $input['template_encoded_id'] = Str::uuid()->toString();
            $template = new Template;
            if($template->fill($input)->save()){
                $htmlzip = ZipFileupload($input['html_file'], $template->slug,'templates');
                if(empty($htmlzip)){
                    throw new Exception("Error occur while uploading zip file.", 1);
                }
                $template->html = $template->slug;
                $template->save();
                $template->template_categories()->attach($input['category_id']);
                DB::commit();
                return redirect()->route('admin.system.template.index',['page' => $input['page']])->withSuccess('Template created successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }
    */
}
