<?php

namespace App\Services\Api\v3;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Models\EventCategory;
use App\Models\EventColor;
use Illuminate\Support\Facades\DB;
use Exception, Validator;


trait EventCategoryTrait
{
    public function getCategory() {
        try {

            $user = $this->getAuthenticatedUser();

            $categories = EventCategory::where('user_id', $user->id) ->orderBy('created_at','DESC')->get();

            
            $this->WebApiArray['status'] = true;
            if ($categories->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $categories;
                $this->WebApiArray['statusCode'] = 0;
            } else {
                $this->WebApiArray['message'] = 'Record not found.';
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function createCategory(Request $request)
    {
        DB::beginTransaction();
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            $validator = Validator::make($input,[
                'title' => 'required|max:50',
                'event_color_id' => 'required|exists:event_colors,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $color = EventColor::find($input['event_color_id']);

            if ($color->color_sort == 1) {
                throw new Exception('Color not valid', 1);
            }

            $input['user_id'] = $user->id;
            $input['color_id'] = $input['event_color_id'];
            $input['color_code'] = $color->color_code;

            $category = new EventCategory();

            if($category->fill($input)->save()){
                DB::commit();
                $this->WebApiArray['status'] = true;
                
                $this->WebApiArray['message'] = 'Category created successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateCategory(Request $request)
    {
        DB::beginTransaction();
        try {
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }

                $user = $this->getAuthenticatedUser();

                $input = $request->all();

                $validator = Validator::make($input,[
                    'title' => 'required|max:50',
                    'event_color_id' => 'required|exists:event_colors,id',
                    'category_id' => 'required|exists:event_categories,id',
                ]);

                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }

                $color = EventColor::find($input['event_color_id']);

                if ($color->color_sort == 1) {
                    throw new Exception('Color not valid', 1);
                }

                $input['color_id'] = $input['event_color_id'];
                $input['color_code'] = $color->color_code;

                $category = EventCategory::where(['id' => $input['category_id'], 'user_id' => $user->id])->first();
                if($category->fill($input)->save()){
                    DB::commit();

                    $this->WebApiArray['status'] = true;
                    
                    $this->WebApiArray['message'] = 'Category Updated successfully.';
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);

        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function deleteCategory(Request $request) {
        DB::beginTransaction();
        try {

            $input = $request->all();

            $validator = Validator::make($input,[
                'category_id' => 'required|exists:event_categories,id',
            ]);

            $user = $this->getAuthenticatedUser();

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $category = EventCategory::whereRaw("(id = ".$input['category_id']." AND user_id = ".$user->id." )")->first();
            if (!empty($category)) {

                $color = EventColor::where('color_sort', 2)->first();

                Event::where('category_id', $input['category_id'])->update([
                    'event_color_id' => $color->id,
                    'color_code' => $color->color_code,
                    'category_id' => null
                ]);

                $category->delete();
                DB::commit();
                $checkCategory = EventCategory::whereRaw("(id = ".$input['category_id']." )")->first();
                if(empty($checkCategory)){
                    $this->WebApiArray['status'] = true;
                    
                    $this->WebApiArray['message'] = 'Category deleted successfully.';
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error occurred while deleting Category! Please try again.", 1);
            }
            throw new Exception("Un-authorized", 1);

        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function getColors() {
        try {
            $color = EventColor::orderBy('color_sort','ASC')->get();
            $this->WebApiArray['status'] = true;
            if ($color->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $color;
                $this->WebApiArray['statusCode'] = 0;
            } else {
                $this->WebApiArray['message'] = 'Record not found.';
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function categoryListing(Request $request)
    {
        try {
            $input = $request->all();

            $user = $this->getAuthenticatedUser();

            $categories = EventCategory::selectRaw('id,color_id,color_code,title')->where('user_id', $user->id)->get();

            $this->WebApiArray['status'] = true;
            if ($categories->count() > 0) {
                $this->WebApiArray['message'] = "Category List";
                $this->WebApiArray['data'] = $categories;
                $this->WebApiArray['statusCode'] = 0;
            } else {
                $this->WebApiArray['message'] = "Record not found";
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }
}
