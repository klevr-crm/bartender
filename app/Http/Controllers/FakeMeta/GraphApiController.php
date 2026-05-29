<?php

declare(strict_types=1);

namespace App\Http\Controllers\FakeMeta;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class GraphApiController extends Controller
{
    public function businesses(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'id' => config('bartender.fake_meta.business_id'),
                    'name' => 'Bartender Business',
                ],
            ],
        ]);
    }

    public function ownedWhatsappBusinessAccounts(string $businessId): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'id' => config('bartender.fake_meta.waba_id'),
                    'name' => 'Bartender WABA',
                    'phone_numbers' => [
                        'data' => [
                            [
                                'id' => config('bartender.fake_meta.phone_number_id'),
                                'display_phone_number' => config('bartender.fake_meta.display_phone_number'),
                                'verified_name' => config('bartender.fake_meta.verified_name'),
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function phoneNumber(string $phoneId): JsonResponse
    {
        return response()->json([
            'id' => $phoneId,
            'display_phone_number' => config('bartender.fake_meta.display_phone_number'),
            'verified_name' => config('bartender.fake_meta.verified_name'),
        ]);
    }

    public function subscribedApps(string $nodeId): JsonResponse
    {
        return response()->json([
            'success' => true,
        ]);
    }
}
