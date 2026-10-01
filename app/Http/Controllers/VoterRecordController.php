<?php

namespace App\Http\Controllers;

use App\Actions\RecordVoterSubmission;
use App\Http\Requests\StoreVoterRecordRequest;
use Illuminate\Http\JsonResponse;

class VoterRecordController extends Controller
{
    public function store(StoreVoterRecordRequest $request, RecordVoterSubmission $action): JsonResponse
    {
        $result = $action->handle(
            $request->validated(),
            $request->ip(),
            $request->userAgent(),
        );

        if (! $result['created']) {
            // Never echo the existing record back. A repeated phone number must
            // not reveal the details or reference of whoever registered first.
            return response()->json([
                'ok' => true,
                'duplicate' => true,
                'message' => 'This phone number has already been registered.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'duplicate' => false,
            'reference' => $result['record']->reference,
            'captured_at' => $result['record']->captured_at?->toIso8601String(),
            'message' => 'Record captured successfully.',
        ], 201);
    }
}
