<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function orders(Request $request)
    {
        $query = Order::with(['clinic', 'courier'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('courier_id')) {
            $query->where('courier_id', $request->courier_id);
        }

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->clinic_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $orders = $query->paginate(30);

        return OrderResource::collection($orders);
    }

    public function couriersSummary(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $summary = User::role('mensajero')
            ->get()
            ->map(function ($courier) use ($month) {
                $delivered = Order::where('courier_id', $courier->id)
                    ->where('status', 'delivered')
                    ->whereRaw("DATE_FORMAT(updated_at, '%Y-%m') = ?", [$month])
                    ->count();

                return [
                    'courier_id' => $courier->id,
                    'name' => $courier->name,
                    'total_delivered' => $delivered,
                ];
            });

        return response()->json([
            'month' => $month,
            'couriers' => $summary,
        ]);
    }
}