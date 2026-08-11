<?php

namespace App\Http\Controllers\Api\Mensajero;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function monthly(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $orders = Order::where('courier_id', $request->user()->id)
            ->where('status', 'delivered')
            ->whereRaw("DATE_FORMAT(updated_at, '%Y-%m') = ?", [$month])
            ->orderBy('updated_at')
            ->get();

        return response()->json([
            'month' => $month,
            'total_delivered' => $orders->count(),
            'orders' => OrderResource::collection($orders),
        ]);
    }
}