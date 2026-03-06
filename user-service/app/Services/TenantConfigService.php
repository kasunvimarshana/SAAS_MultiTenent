<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TenantConfigService
{
    /**
     * Apply tenant-specific runtime configuration.
     * Overrides mail, payment, notification, and other settings dynamically.
     */
    public function applyTenantConfig(Tenant $tenant): void
    {
        try {
            $settings = $tenant->settings ?? [];

            // Apply mail configuration
            if (!empty($settings['mail'])) {
                $this->applyMailConfig($settings['mail']);
            }

            // Apply payment gateway configuration
            if (!empty($settings['payment'])) {
                $this->applyPaymentConfig($settings['payment']);
            }

            // Apply notification configuration
            if (!empty($settings['notifications'])) {
                $this->applyNotificationConfig($settings['notifications']);
            }

            // Apply custom environment settings
            if (!empty($settings['env'])) {
                foreach ($settings['env'] as $key => $value) {
                    Config::set($key, $value);
                }
            }

            Log::debug('TenantConfigService: Applied tenant config', ['tenant_id' => $tenant->id]);
        } catch (\Throwable $e) {
            Log::error('TenantConfigService: Failed to apply config', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function applyMailConfig(array $mailConfig): void
    {
        $mapping = [
            'mailer' => 'mail.default',
            'host' => 'mail.mailers.smtp.host',
            'port' => 'mail.mailers.smtp.port',
            'username' => 'mail.mailers.smtp.username',
            'password' => 'mail.mailers.smtp.password',
            'encryption' => 'mail.mailers.smtp.encryption',
            'from_address' => 'mail.from.address',
            'from_name' => 'mail.from.name',
        ];
        foreach ($mapping as $settingKey => $configKey) {
            if (isset($mailConfig[$settingKey])) {
                Config::set($configKey, $mailConfig[$settingKey]);
            }
        }
    }

    private function applyPaymentConfig(array $paymentConfig): void
    {
        foreach ($paymentConfig as $key => $value) {
            Config::set("payment.{$key}", $value);
        }
    }

    private function applyNotificationConfig(array $notificationConfig): void
    {
        foreach ($notificationConfig as $key => $value) {
            Config::set("notifications.{$key}", $value);
        }
    }
}
