<?php

use Illuminate\Http\Request;

use App\Service\TwitterOAuth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('api')->get('/tweets', function (Request $request) {
    if (!$request->search) {
        return response('Search required', 418);
    }

    $twitterOauth = new TwitterOAuth(env('CONSUMER_KEY'), env('CONSUMER_SECRET'), env('TOKEN'), env('TOKEN_SECRET'));
    $response = $twitterOauth->search([
        "q" => $request->search,
        "count" => $request->count ?: 10,
        "result_type" => $request->result_type ?: "recent",
    ]);

    return response()->json($response->statuses)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', '*')
            ->header('Access-Control-Allow-Headers', '*')
            ->header('Access-Control-Max-Age', '86400');
});
