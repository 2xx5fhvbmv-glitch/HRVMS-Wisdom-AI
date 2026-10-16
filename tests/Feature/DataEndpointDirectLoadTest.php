<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** DataTable data endpoints opened directly (not via AJAX) used to 500 ("Undefined variable $page_title"). */
class DataEndpointDirectLoadTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    public function test_direct_load_redirects_to_the_host_page_and_ajax_still_returns_data()
    {
        $this->buildDemoOrganisation();
        $endpoints = [
            'manning.departments.data' => 'resort.manning.index',
            'manning.sections.data' => 'resort.manning.index',
            'manning.positions.data' => 'resort.manning.index',
            'workforceplan.filledpositions.data' => 'resort.workforceplan.filledpositions',
            'people.resignation-reason.list' => 'people.resignation-reason.index',
            'people.increment-types.list' => 'people.increment-types.index',
        ];
        foreach ($endpoints as $data => $host) {
            $this->as('hr')->get(route($data))->assertRedirect(route($host));
            $this->as('hr')->get(route($host))->assertOk();
            $this->as('hr')->getJson(route($data), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonStructure(['data']);
        }
    }
}
