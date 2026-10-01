<?php

namespace Tests\Feature\Admin;

use App\Enums\Gender;
use App\Enums\PvcStatus;
use App\Models\Admin;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\VoterRecord;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ConsolePagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_dashboard_renders_for_an_admin(): void
    {
        VoterRecord::factory()->count(4)->create();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get('/'.config('admin.path'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Total records', false)
            ->assertSee('chart-trend', false)
            ->assertSee('data-state-rows', false);
    }

    public function test_the_data_endpoint_returns_the_analytics_payload(): void
    {
        VoterRecord::factory()->count(3)->create();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->getJson(route('admin.data', ['range' => 30]))
            ->assertOk()
            ->assertJsonPath('data.range', 30)
            ->assertJsonPath('data.kpis.total', 3)
            ->assertJsonStructure([
                'data' => [
                    'range',
                    'generated_at',
                    'kpis' => ['total', 'in_range', 'today', 'consent_contact_rate'],
                    'series' => ['labels', 'values'],
                    'gender' => ['labels', 'values'],
                    'age_bands' => ['labels', 'values'],
                    'pvc',
                    'voter_status',
                    'languages',
                    'channels',
                    'coverage',
                    'top_lgas',
                    'top_wards',
                    'top_units',
                    'by_state',
                ],
            ]);
    }

    public function test_an_unsupported_range_falls_back_to_the_default(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->getJson(route('admin.data', ['range' => 9999]))
            ->assertOk()
            ->assertJsonPath('data.range', 30);
    }

    public function test_the_records_table_lists_and_filters_records(): void
    {
        VoterRecord::factory()->create(['full_name' => 'Amina Musa', 'gender' => Gender::Female->value]);
        VoterRecord::factory()->create(['full_name' => 'Bello Adamu', 'gender' => Gender::Male->value]);

        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.records'))
            ->assertOk()
            ->assertSee('Amina Musa', false)
            ->assertSee('Bello Adamu', false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.records', ['gender' => Gender::Female->value]))
            ->assertOk()
            ->assertSee('Amina Musa', false)
            ->assertDontSee('Bello Adamu', false);
    }

    public function test_the_records_search_matches_a_name(): void
    {
        VoterRecord::factory()->create(['full_name' => 'Zainab Okon']);
        VoterRecord::factory()->create(['full_name' => 'Chidi Nwosu']);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.records', ['search' => 'Zainab']))
            ->assertOk()
            ->assertSee('Zainab Okon', false)
            ->assertDontSee('Chidi Nwosu', false);
    }

    public function test_the_geography_page_lists_states_and_lgas(): void
    {
        $state = State::factory()->create(['name' => 'Zamfara']);
        PollingUnit::factory()->create();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.geography'))
            ->assertOk()
            ->assertSee('Zamfara', false)
            ->assertSee('Local government areas', false);
    }

    public function test_the_geography_page_renders_the_registration_heat_map(): void
    {
        State::factory()->create();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.geography'))
            ->assertOk()
            ->assertSee('data-geo-map', false)
            ->assertSee('geo/nigeria-states.geojson', false)
            ->assertSee('window.__GEO_MAP', false);
    }

    public function test_the_records_table_can_be_exported_as_csv(): void
    {
        VoterRecord::factory()->create([
            'full_name' => 'Amina Musa',
            'reference' => 'TMG-DEL-NDW-UTO-033-K7Q2M9',
        ]);

        $response = $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.records.export'));

        $response->assertOk()->assertHeader('content-type', 'text/csv');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Reference,Name,Phone', $csv);
        $this->assertStringContainsString('TMG-DEL-NDW-UTO-033-K7Q2M9', $csv);
        $this->assertStringContainsString('Amina Musa', $csv);
    }

    public function test_the_records_export_respects_the_active_filters(): void
    {
        VoterRecord::factory()->create(['full_name' => 'Amina Musa', 'gender' => Gender::Female->value]);
        VoterRecord::factory()->create(['full_name' => 'Bello Adamu', 'gender' => Gender::Male->value]);

        $csv = $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.records.export', ['gender' => Gender::Female->value]))
            ->streamedContent();

        $this->assertStringContainsString('Amina Musa', $csv);
        $this->assertStringNotContainsString('Bello Adamu', $csv);
    }

    public function test_an_invalid_record_filter_is_rejected(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.records', ['gender' => 'not-a-gender']))
            ->assertSessionHasErrors('gender');
    }

    public function test_the_records_page_uses_valid_pvc_labels(): void
    {
        VoterRecord::factory()->create(['pvc_status' => PvcStatus::Collected->value]);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.records'))
            ->assertOk()
            ->assertSee('Collected', false);
    }
}
