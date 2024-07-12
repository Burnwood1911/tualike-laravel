<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Category;

class CardController extends Controller
{
    public function getCardCategories()
    {

        $categories = Category::all();

        $result = [
            'statusCode' => 200,
            'data' => $categories,
            'message' => 'Categories retrieved successfully',
        ];

        return response()->json($result, $result['statusCode']);
    }

    public function getCards()
    {

        $cards = Card::all();

        $result = [
            'statusCode' => 200,
            'data' => $cards,
            'message' => 'Cards retrieved successfully',
        ];

        return response()->json($result, $result['statusCode']);
    }

    public function getCard(int $id)
    {
        $card = Card::find($id);

        if (! $card) {
            return [
                'statusCode' => 404,
                'message' => 'Card not found',
            ];
        }

        $result = [
            'statusCode' => 200,
            'data' => $card,
            'message' => 'Card retrieved successfully',
        ];

        return response()->json($result, $result['statusCode']);
    }
}
