<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_manifest_is_installable(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertIsArray($manifest);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['name']);
        $this->assertNotEmpty($manifest['short_name']);
        $this->assertNotEmpty($manifest['start_url']);

        $sizes = collect($manifest['icons'])->pluck('sizes')->all();
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        $this->assertContains('maskable', collect($manifest['icons'])->pluck('purpose')->all());

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path($icon['src']));
        }
    }

    public function test_service_worker_and_offline_page_exist(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertStringContainsString('offline.html', file_get_contents(public_path('sw.js')));
    }

    public function test_panel_layout_links_manifest_and_registers_service_worker(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->get(route('products.index'))
            ->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('apple-touch-icon', false)
            ->assertSee('viewport-fit=cover', false)
            ->assertSee('serviceWorker', false)
            ->assertSee('data-pwa-install', false)
            ->assertSee('sidebar-backdrop', false);
    }

    public function test_login_layout_links_manifest_and_shows_version(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('serviceWorker', false)
            ->assertSee('v' . trim(file_get_contents(base_path('VERSION'))), false);
    }
}
