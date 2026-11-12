<?php

namespace Tests\Feature;

use Tests\TestCase;

/** A browser request with the wrong method is redirected instead of showing a 405 error page; AJAX / API keep the 405. */
class WrongMethodRedirectTest extends TestCase
{
    public function test_wrong_method_redirects_browsers_and_keeps_405_for_ajax_and_api()
    {
        $this->post('/resort/request-password')->assertRedirect(url('/resort/request-password'));   // GET page posted to
        $this->get('/resort/request-password-submit')->assertRedirect(url('/'));                     // POST-only URL opened
        $this->postJson('/resort/request-password')->assertStatus(405);
        $this->get('/api/login')->assertStatus(405);
    }
}
