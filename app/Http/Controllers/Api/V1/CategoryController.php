<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoryController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $category = Category::paginate(2);

        return $this->successResponse([
            'category' => CategoryResource::collection($category),
            'links' => CategoryResource::collection($category)->response()->getData()->links,
            'meta' => CategoryResource::collection($category)->response()->getData()->meta,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'parent_id' => 'required | integer',
            'name' => 'required | string'
        ]);

        if ($validate->fails()){
            return $this->errorResponse($validate->getMessageBag(), 406);
        }

        DB::beginTransaction();
        $category = Category::create([
            'parent_id' => $request->parent_id,
            'name' => $request->name
        ]);

        DB::commit();
        return $this->successResponse(new CategoryResource($category), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        return $this->successResponse(new CategoryResource($category), 203);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $valifate = Validator::make($request->all(), [
            'parent_id' => 'integer',
            'name' => 'string'
        ]);

        if ($valifate->fails()){
            return $this->errorResponse($valifate->getMessageBag(), 401);
        }

        DB::beginTransaction();
        $category->update([
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'description' => $request->description
        ]);
        DB::commit();

        return $this->successResponse(new CategoryResource($category), 203);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        DB::beginTransaction();
        $category->delete();
        DB::commit();

        return $this->successResponse(new CategoryResource($category), 200);
    }

    public function children(Category $category)
    {
        return $this->successResponse(new CategoryResource($category->load('children')), 201);
    }

    public function parent(Category $category)
    {
        return $this->successResponse(new CategoryResource($category->load('parent')), 200);
    }

    public function product(Category $category)
    {
        return $this->successResponse(new CategoryResource($category->load('products')), 200);
    }
}
