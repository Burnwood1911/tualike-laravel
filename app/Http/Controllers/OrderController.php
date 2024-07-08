<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|string',
            'location' => 'required|string',
            'security' => 'required|boolean',
            'excel' => 'required|string',
            'card_id' => 'required|exists:cards,id',
        ]);

        // Create the order
        $order = Order::create($validatedData);

        // Return a JSON response
        return response()->json([
            'statusCode' => 201,
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }
}
