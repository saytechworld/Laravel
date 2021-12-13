<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\FAQ;
use DB, Exception;
use  App\Http\Requests\Backend\FAQRequest;

class FAQController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $faq = (new FAQ)->newQuery();
        $faq = $faq->paginate(2);
        return view('Backend.faq.index',compact('faq'))
            ->with('i', ($request->input('page', 1) - 1) * 2);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('Backend.faq.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(FAQRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = 1 ;
            $faq = new FAQ;
            if($faq->fill($input)->save()){
                DB::commit();
                return redirect()->route('admin.system.faq.index',['page' => $input['page']])->withSuccess('FAQ created successfully.');
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(FAQ $faq)
    {
        return view('Backend.faq.edit',compact('faq'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(FAQRequest $request, FAQ $faq)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = 1 ;
            if($faq->fill($input)->save()){
                DB::commit();
                return redirect()->route('admin.system.faq.index',['page' => $input['page']])->withSuccess('FAQ updated successfully.');
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
