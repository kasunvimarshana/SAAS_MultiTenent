<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TenantConfigService
{
    public function applyTenantConfig(Tenant $tenant): void
    {
        try {
            $settings = $tenant->settings ?? [];
            if (!empty($settings['mail'])) {
                foreach (['host', 'port', 'username', 'password', 'encryption'] as $key) {
                    if (isset($settings['mail'][$key])) {
                        Config::set("mail.mailers.smtp.{$key}", $settings['mail'][$key]);
                    }
                }
            }
            if (!empty($settings['env'])) {
                foreach ($settings['env'] as $key => $value) {
                    Config::set($key, $value);
                }
            }
        } catch (\Throwable $e) {
            Log::error('TenantConfigService: Failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }
    }
}
