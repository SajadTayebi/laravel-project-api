<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $product = Product::paginate(10);

        return $this->successResponse([
            'product' => ProductResource::collection($product->load('images')),
            'links' => ProductResource::collection($product)->response()->getData()->links,
            'meta' => ProductResource::collection($product)->response()->getData()->meta,
        ], 202);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'brand_id' => 'required|integer',
            'category_id' => 'required|integer',
            'primary_image' => 'required',
            'price' => 'integer',
            'quantity' => 'integer',
            'description' => 'required|string',
            'delivery_amount' => 'integer',
            'images.*' => 'nullable|image'
        ]);

        if ($validate->fails()){
            return $this->errorResponse($validate->getMessageBag(), 400);
        }


        DB::beginTransaction();

        $primaryImage = Carbon::now()->microsecond . '.' . $request->primary_image->extension();
        $request->primary_image->storeAs('images/products', $primaryImage, 'public');

        if ($request->has('images')){
            $fileNameImages = [];
            foreach ($request->images as $image){
                $fileNameImage = Carbon::now()->microsecond . '.' . $image->extension();
                $image->storeAs('images/products', $fileNameImage, 'public');
                array_push($fileNameImages, $fileNameImage);
            }
        }

        $product = Product::create([
            'name' => $request->name,
            'brand_id' => $request->brand_id,
            'category_id' => $request->category_id,
            'primary_image' => $primaryImage,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'description' => $request->description,
            'delivery_amount' => $request->delivery_amount,
        ]);

        if ($request->has('images')){
            foreach ($fileNameImages as $fileNameImage){
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $fileNameImage
                ]);
            }
        }

        DB::commit();

        return $this->successResponse(new ProductResource($product), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return $this->successResponse(new ProductResource($product->load('images')), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'brand_id' => 'required|integer',
            'category_id' => 'required|integer',
            'primary_image' => 'nullable',
            'price' => 'integer',
            'quantity' => 'integer',
            'description' => 'nullable|string',
            'delivery_amount' => 'integer',
            'images.*' => 'nullable|image'
        ]);

        if ($validate->fails()){
            return $this->errorResponse($validate->getMessageBag(), 400);
        }


        DB::beginTransaction();

        if ($request->has('primary_image')){
            $primaryImage = Carbon::now()->microsecond . '.' . $request->primary_image->extension();
            $request->primary_image->storeAs('images/products', $primaryImage, 'public');
        }


        if ($request->has('images')){
            $fileNameImages = [];
            foreach ($request->images as $image){
                $fileNameImage = Carbon::now()->microsecond . '.' . $image->extension();
                $image->storeAs('images/products', $fileNameImage, 'public');
                array_push($fileNameImages, $fileNameImage);
            }
        }

        $product->update([
            'name' => $request->name,
            'brand_id' => $request->brand_id,
            'category_id' => $request->category_id,
            'primary_image' => $request->has('primary_image') ? $primaryImage : $product->primary_image,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'description' => $request->description,
            'delivery_amount' => $request->delivery_amount,
        ]);

        if ($request->has('images')){
            foreach ($product->images as $image){
                $image->delete();
            }
            foreach ($fileNameImages as $fileNameImage){
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $fileNameImage
                ]);
            }
        }

        DB::commit();

        return $this->successResponse(new ProductResource($product), 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        DB::beginTransaction();
        $product->delete();
        DB::commit();

        return $this->successResponse(new ProductResource($product), 200);
    }
}
