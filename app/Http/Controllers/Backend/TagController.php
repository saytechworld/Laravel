<?php

namespace App\Http\Controllers\Backend;

use App\Http\Requests\Backend\TagRequest;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $tags = (new Tag())->newQuery();
        $tags = $tags->orderBy('title','ASC')->paginate(25);
        return view('Backend.tag.index',compact('tags'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('Backend.tag.create');
    }

    /**
     * @param StaticPageRequest $request
     * @return mixed
     */
    public function store(TagRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = 1;
            $tag = new Tag;
            if($tag->fill($input)->save()){
                DB::commit();
                return redirect()->route('admin.system.tag.index')->withSuccess('Tag created successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }

    /**
     * @param Tag $tag
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Tag $tag)
    {
        return view('Backend.tag.edit',compact('tag'));
    }

    /**
     * @param TagRequest $request
     * @return mixed
     */
    public function update(TagRequest $request, Tag $tag)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = 1;

            if($tag->fill($input)->save()){
                DB::commit();
                return redirect()->route('admin.system.tag.index')->withSuccess('Tag updated successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }
}
