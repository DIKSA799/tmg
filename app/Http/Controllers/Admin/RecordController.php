<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgeBand;
use App\Enums\Gender;
use App\Enums\Occupation;
use App\Enums\PreferredChannel;
use App\Enums\PvcStatus;
use App\Enums\RegisteredVoterStatus;
use App\Enums\VolunteerCategory;
use App\Http\Controllers\Controller;
use App\Models\Lga;
use App\Models\State;
use App\Models\VoterRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecordController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.records', [
            'records' => $this->filtered($filters)->paginate(25)->withQueryString(),
            'filters' => $filters,
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'lgas' => isset($filters['state_id']) ? Lga::query()->where('state_id', $filters['state_id'])->orderBy('name')->get(['id', 'name']) : collect(),
            'options' => [
                'gender' => Gender::options(),
                'age_band' => AgeBand::options(),
                'pvc_status' => PvcStatus::options(),
                'voter_status' => RegisteredVoterStatus::options(),
                'channel' => PreferredChannel::options(),
                'volunteer_category' => VolunteerCategory::options(),
                'occupation' => Occupation::options(),
            ],
        ]);
    }

    /**
     * Stream every record matching the current filters as CSV. Streaming keeps
     * memory flat however many records the filter matches.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);

        $headings = [
            'Reference', 'Name', 'Phone', 'WhatsApp', 'Email', 'Gender', 'Age band',
            'State', 'LGA', 'Ward', 'Polling unit', 'Registered voter', 'PVC status',
            'Volunteer category', 'Occupation', 'Disability', 'Pledge',
            'Contact consent', 'Data consent', 'Captured at',
        ];

        $filename = 'tmg-records-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($filters, $headings): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $headings, ',', '"', '');

            foreach ($this->filtered($filters)->lazy(500) as $record) {
                fputcsv($handle, [
                    $record->reference,
                    $record->full_name,
                    $record->phone,
                    $record->whatsapp ?? '',
                    $record->email ?? '',
                    Gender::tryFrom((string) $record->gender)?->label() ?? $record->gender,
                    AgeBand::tryFrom((string) $record->age_band)?->label() ?? $record->age_band,
                    $record->state?->name ?? '',
                    $record->lga?->name ?? '',
                    $record->ward?->name ?? '',
                    $record->pollingUnit?->name ?? '',
                    RegisteredVoterStatus::tryFrom((string) $record->registered_voter_status)?->label() ?? '',
                    PvcStatus::tryFrom((string) $record->pvc_status)?->label() ?? '',
                    VolunteerCategory::tryFrom((string) $record->volunteer_category)?->label() ?? '',
                    Occupation::tryFrom((string) $record->occupation)?->label() ?? '',
                    is_null($record->has_disability) ? '' : ($record->has_disability ? 'Yes' : 'No'),
                    $record->pledge_accepted ? 'Accepted' : 'Missing',
                    $record->consent_to_contact ? 'Yes' : 'No',
                    $record->consent_to_data ? 'Yes' : 'No',
                    $record->captured_at?->toDateTimeString() ?? '',
                ], ',', '"', '');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:60'],
            'state_id' => ['nullable', 'integer'],
            'lga_id' => ['nullable', 'integer'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'age_band' => ['nullable', Rule::enum(AgeBand::class)],
            'pvc_status' => ['nullable', Rule::enum(PvcStatus::class)],
            'voter_status' => ['nullable', Rule::enum(RegisteredVoterStatus::class)],
            'volunteer_category' => ['nullable', Rule::enum(VolunteerCategory::class)],
            'occupation' => ['nullable', Rule::enum(Occupation::class)],
            'consent' => ['nullable', Rule::in(['contact', 'data'])],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<VoterRecord>
     */
    private function filtered(array $filters): Builder
    {
        return VoterRecord::query()
            ->with(['state:id,name', 'lga:id,name', 'ward:id,name', 'pollingUnit:id,name'])
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                fn ($inner) => $inner
                    ->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('whatsapp', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('reference', 'like', '%'.$search.'%')
            ))
            ->when($filters['state_id'] ?? null, fn ($query, int|string $state) => $query->where('state_id', $state))
            ->when($filters['lga_id'] ?? null, fn ($query, int|string $lga) => $query->where('lga_id', $lga))
            ->when($filters['gender'] ?? null, fn ($query, string $gender) => $query->where('gender', $gender))
            ->when($filters['age_band'] ?? null, fn ($query, string $band) => $query->where('age_band', $band))
            ->when($filters['pvc_status'] ?? null, fn ($query, string $pvc) => $query->where('pvc_status', $pvc))
            ->when($filters['voter_status'] ?? null, fn ($query, string $status) => $query->where('registered_voter_status', $status))
            ->when($filters['volunteer_category'] ?? null, fn ($query, string $category) => $query->where('volunteer_category', $category))
            ->when($filters['occupation'] ?? null, fn ($query, string $occupation) => $query->where('occupation', $occupation))
            ->when($filters['consent'] ?? null, fn ($query, string $consent) => $query->where($consent === 'contact' ? 'consent_to_contact' : 'consent_to_data', true))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
