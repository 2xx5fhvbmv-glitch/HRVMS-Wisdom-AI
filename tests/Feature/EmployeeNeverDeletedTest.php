<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Founder decision 2026-10-15: employee records are never deleted — no endpoint, no control, for anyone. */
class EmployeeNeverDeletedTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    public function test_there_is_no_way_to_delete_an_employee()
    {
        $this->buildDemoOrganisation();
        $this->assertFalse(Route::has('employee.delete'));
        $this->assertFalse(Route::has('employee.bulk.delete'));

        $victim = $this->emp('employee');
        $this->as('hr')->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/resort/people/employees/delete', ['id' => $victim->id])->assertNotFound();
        $this->assertSame('Active', $victim->fresh()->status);

        $this->as('hr')->get(route('people.employees'))->assertOk()->assertDontSee('delete-employee')->assertDontSee('Delete Employee');
        $list = $this->as('hr')->getJson(route('people.employees.list', ['length' => 200]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->getContent();
        $this->assertStringNotContainsString('delete-employee', $list);
        $this->assertStringNotContainsString('Delete Employee', $list);
    }
}
