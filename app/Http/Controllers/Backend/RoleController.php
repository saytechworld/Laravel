<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Exception, DB;
use  App\Http\Requests\Backend\RoleRequest;


class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $roles = Role::orderBy('name','ASC')->paginate(25);
        return view('Backend.role.index',compact('roles'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {    
        $total_role = Role::count();
        $sort = $total_role+1;
        $permissions = Permission::pluck('name','id');
        return view('Backend.Access.role.create',compact('permissions','sort'));
    }

    /**
     * Store a newly created resource in storage.
     * RoleRequest
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RoleRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $role = new Role;
            $input['all'] = isset($input['all']) ? 1 : 0;
            if($role->fill($input)->save()){
                if($role->all != 1 && !empty($input['permission_id'])){
                   $role->permissions()->attach($input['permission_id']); 
                }
                DB::commit();
                return redirect()->route('admin.access.role.index',['page' => $input['page']])->withSuccess('Role created successfully.');
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
    public function edit(Role $role)
    {
        $permissions = Permission::pluck('name','id');
        $role_permission = $role->permissions()->pluck('id')->toArray();
        return view('Backend.Access.role.edit',compact('role','permissions','role_permission'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(RoleRequest $request, Role $role)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['all'] = isset($input['all']) ? 1 : 0;
            if($role->fill($input)->save()){
                if($role->all != 1 && !empty($input['permission_id'])){
                   $role->permissions()->sync($input['permission_id']); 
                }else{
                   $role->permissions()->detach(); 
                }
                DB::commit();
                return redirect()->back()->withSuccess('Role updated successfully.');
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
