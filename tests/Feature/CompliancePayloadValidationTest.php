<?php
namespace Tests\Feature;
use App\Services\ComplianceSettingsService; use Illuminate\Validation\ValidationException; use Tests\TestCase;
class CompliancePayloadValidationTest extends TestCase
{
    public function test_incomplete_legal_settings_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(ComplianceSettingsService::class)->validatePayload(['ssc_employee_percent'=>'1']);
    }
}
