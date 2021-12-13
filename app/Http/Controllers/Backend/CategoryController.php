<?php

namespace App\Http\Controllers\Backend;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception, DB;
use  App\Http\Requests\Backend\CategoryRequest;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $input = $request->all();
        $categories = (new Category)->newQuery();
        if(!empty($input['q'])){
           $categories->whereRaw("(name LIKE '%".$input['q']."%' OR slug LIKE '%".$input['q']."%' )");
        }
        $categories = $categories->orderBy('name','ASC')->paginate(25);
        return view('Backend.category.index',compact('categories'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::whereNull('parent_id')->where('status',1)->get();
        return view('Backend.category.create',compact('categories'));

        //validated
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['parent_id'] = !empty($input['child_category']) ? $input['child_category'] : (!empty($input['parent_category']) ? $input['parent_category'] : NULL );
            $input['status'] = isset($input['status']) ? 1 : 0;
            $category = new Category;
            if($category->fill($input)->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Category created successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }


    public function store12(Request $request)
    {   
        DB::beginTransaction();
        try {
            if($request->ajax()){
                $input = $request->all();
                $input['parent_id'] = !empty($input['child_category']) ? $input['child_category'] : (!empty($input['parent_category']) ? $input['parent_category'] : NULL );
                $input['status'] = isset($input['status']) ? 1 : 0;
                 $category = new Category;
                if($category->fill($input)->save()){
                    DB::commit();
                    return response()->json(['status' => true, 'messsage' => 'Category created successfully.']);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['status' => false, 'messsage' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        $categories = Category::whereNull('parent_id')->where('status',1)->get();
        return view('Backend.category.edit',compact('categories','category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(CategoryRequest $request, Category $category)
    {
       DB::beginTransaction();
        try {
            $input = $request->all();
            $input['parent_id'] = !empty($input['child_category']) ? $input['child_category'] : (!empty($input['parent_category']) ? $input['parent_category'] : NULL );
            $input['status'] = isset($input['status']) ? 1 : 0;
            if($category->fill($input)->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Category updated successfully.');
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
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        //
    }

    public function mark(Request $request, Category $category, $status)
    {
        DB::beginTransaction();
        try {
            $category->status = $status == 1 ? 1 : 0;
            if($category->save()){
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
