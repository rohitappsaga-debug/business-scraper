<?php

namespace Tests\Feature;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_returns_successful_response(): void
    {
        Business::factory()->count(3)->create();

        $response = $this->get(route('export.csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_excel_export_returns_successful_response(): void
    {
        Business::factory()->count(3)->create();

        $response = $this->get(route('export.excel'));

        $response->assertStatus(200);
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_csv_export_with_location_filter(): void
    {
        Business::factory()->create(['city' => 'Dallas', 'state' => 'TX']);
        Business::factory()->create(['city' => 'Chicago', 'state' => 'IL']);

        $response = $this->get(route('export.csv', ['location' => 'Dallas']));

        $response->assertStatus(200);
    }

    public function test_csv_export_with_has_email_filter(): void
    {
        Business::factory()->create(['email' => 'test@example.org']);
        Business::factory()->create(['email' => null]);

        $response = $this->get(route('export.csv', ['has_email' => '1']));

        $response->assertStatus(200);
    }
}
