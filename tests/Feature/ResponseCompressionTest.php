<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponseCompressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_are_gzipped_for_browsers_that_accept_it(): void
    {
        $response = $this->withHeaders(['Accept-Encoding' => 'gzip, deflate, br'])->get(route('login'));

        $response->assertOk()->assertHeader('Content-Encoding', 'gzip');
        $this->assertStringContainsString('Welcome back', gzdecode($response->getContent()));
    }

    public function test_plain_responses_without_gzip_support_and_for_small_bodies(): void
    {
        $this->get(route('login'))->assertOk()->assertHeaderMissing('Content-Encoding')->assertSee('Welcome back');

        $user = User::factory()->create();
        $this->actingAs($user)->withHeaders(['Accept-Encoding' => 'gzip'])->get(route('dashboard'))
            ->assertRedirect()->assertHeaderMissing('Content-Encoding');
    }
}
