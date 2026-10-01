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
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecordController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
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

        $records = VoterRecord::query()
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
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.records', [
            'records' => $records,
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
}
