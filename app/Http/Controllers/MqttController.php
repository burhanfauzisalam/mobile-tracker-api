<?php

namespace App\Http\Controllers;

use App\Models\MQTT;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class MqttController extends Controller
{
    /**
     * Return the MQTT connection records as JSON.
     */
    public function index(): JsonResponse
    {
        try {
            $connections = MQTT::query()->get();

            return response()->json([
                'status' => 'success',
                'total' => $connections->count(),
                'data' => $connections,
            ]);
        } catch (QueryException $exception) {
            $response = [
                'status' => 'error',
                'message' => 'Unable to fetch MQTT connections.',
            ];

            if ((bool) config('app.debug')) {
                $response['error'] = $exception->getMessage();
            }

            return response()->json($response, 500);
        }
    }
}
