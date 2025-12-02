<?php

namespace App\Http\Controllers;

use App\Models\TenantPayment;
use Illuminate\Http\Request;

class TenantPaymentController extends Controller
{
    public function index()
    {
        return TenantPayment::with(['tenant', 'plan'])->get();
    }

    public function store(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $payment = TenantPayment::create($request->all());
        return response()->json($payment, 201);
    }

    public function show(TenantPayment $payment)
    {
        return $payment->load(['tenant', 'plan']);
    }

    public function update(Request $request, TenantPayment $payment)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $payment->update($request->all());
        return response()->json($payment);
    }

    public function destroy(TenantPayment $payment)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $payment->delete();
        return response()->noContent();
    }
}
