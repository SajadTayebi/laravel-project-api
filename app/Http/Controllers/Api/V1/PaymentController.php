<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function dd;
use function env;

class PaymentController extends ApiController
{
    public function send(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'order_item' => 'required',
            'order_item.*.product_id' => 'required|integer',
            'order_item.*.quantity' => 'required|integer',
        ]);

        if ($validate->fails()){
            return $this->errorResponse($validate->getMessageBag(), 400);
        }

        $totalAmount = 0;
        $deliveryAmount = 0;
        foreach ($request->order_item as $orderItem){
            $product = Product::findOrFail($orderItem['product_id']);

            if($orderItem['quantity'] > $product->quantity){
                return $this->errorResponse('Error', 422);
            }

            $totalAmount += $product->price * $orderItem['quantity'];

            $deliveryAmount += $product->delivery_amount;
        }

        $payingAmount = $totalAmount + $deliveryAmount;

        $amounts = [
            'totalAmount' => $totalAmount,
            'deliveryAmount' => $deliveryAmount,
            'payingAmount' => $payingAmount,
        ];

        $api = env('PAYMENT_IR_API_KEY');
        $amount = $payingAmount . 0;
        $mobile = "شماره موبایل";
        $factorNumber = "شماره فاکتور";
        $description = "توضیحات";
        $redirect = env('HTTP_CALLBACK_URL');
        $result = $this->sendRequest($api, $amount, $redirect, $mobile, $factorNumber, $description);
        $result = json_decode($result);
        if ($result->status) {
            OrderController::create($request, $amounts, $result->token);
            $go = "https://pay.ir/pg/$result->token";
            return $this->successResponse($go, 200);
        } else {
            return $this->errorResponse($result->errorMessage, 422);
        }
    }

    public function verify(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'token' => $request->token
        ]);
        $api = env('PAYMENT_IR_API_KEY');
        $token = $request->token;
        $result = json_decode($this->verifyRequest($api,$token));
        if(isset($result->status)){
            if($result->status == 1){
                if (Transaction::where('trans_id', $result->transId)->exists()){
                    return $this->errorResponse('تراکنش تکراری است', 450);
                }
                OrderController::update($token, $result->transId);
                return $this->successResponse('', 200, 'تراکنش با موفقیت انجام شد');
            } else {
                return $this->errorResponse('تراکنش با خطا مواجه شد', 422);
            }
        } else {
            if($request->status == 0){
                return $this->errorResponse('تراکنش با خطا مواجه شد', 422);
            }
        }
    }

    public function verifyRequest($api, $token) {
        return $this->curl_post('https://pay.ir/pg/verify', [
            'api' 	=> $api,
            'token' => $token,
        ]);
    }

    public function sendRequest($api, $amount, $redirect, $mobile = null, $factorNumber = null, $description = null)
    {
        return $this->curl_post('https://pay.ir/pg/send', [
            'api' => $api,
            'amount' => $amount,
            'redirect' => $redirect,
            'mobile' => $mobile,
            'factorNumber' => $factorNumber,
            'description' => $description,
        ]);
    }

    public function curl_post($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
}
