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

        return response()->json([
            'ok' => true,
            'duplicate' => ! $result['created'],
            'reference' => $result['record']->reference,
            'captured_at' => $result['record']->captured_at?->toIso8601String(),
            'message' => $result['created']
                ? 'Record captured successfully.'
                : 'This record has already been captured.',
        ], $result['created'] ? 201 : 200);
    }
}
