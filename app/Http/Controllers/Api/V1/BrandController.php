<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BrandController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $brands = Brand::paginate(2);

//        return BrandResource::collection($brands);

        return $this->successResponse([
            'data' => BrandResource::collection($brands),
            'links' => BrandResource::collection($brands)->response()->getData()->links,
            'meta' => BrandResource::collection($brands)->response()->getData()->meta
        ], 206);
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
            'name' => 'required | string',
            'display_name' => 'required | unique:brands'
        ]);

        if ($validate->fails()){
            return $this->errorResponse($validate->getMessageBag(), 401);
        }

        DB::beginTransaction();
        $brand = Brand::create([
            'name' => $request->name,
            'display_name' => $request->display_name
        ]);

        DB::commit();
        return $this->successResponse(new BrandResource($brand), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Brand $brand)
    {
        return $this->successResponse(new BrandResource($brand), 203);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Brand $brand)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required | string',
            'display_name' => 'required | unique:brands'
        ]);

        if ($validate->fails()){
            return $this->errorResponse($validate->getMessageBag(), 401);
        }

        DB::beginTransaction();
        $brand->update([
            'name' => $request->name,
            'display_name' => $request->display_name
        ]);

        DB::commit();

        return $this->successResponse(new BrandResource($brand), 205);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Brand $brand)
    {
        $brand->delete();

        return $this->successResponse(new BrandResource($brand), 200, 'test');
    }

    public function product(Brand $brand)
    {
        return $this->successResponse(new BrandResource($brand->load('products')), 200);
    }

}
