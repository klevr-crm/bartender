<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChannelInstance;
use App\Models\Persona;
use App\Models\Scenario;
use Illuminate\Database\Seeder;

final class ScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $personaLead = Persona::where('slug', 'lead_pricing_curious')->first();
        $personaFrustrated = Persona::where('slug', 'frustrated_customer')->first();

        if ($personaLead) {
            Scenario::updateOrCreate(
                ['slug' => 'lead-asks-pricing-whatsapp'],
                [
                    'name' => 'Lead asks pricing on WhatsApp',
                    'description' => 'A pricing-curious lead reaches out via WhatsApp Cloud.',
                    'persona_id' => $personaLead->id,
                    'channel' => 'meta_whatsapp_cloud',
                    'target_org_id' => config('bartender.target_org_id', 'test-org'),
                    'script' => 'Ask for pricing. Resist upsell. Get a number.',
                ]
            );

            Scenario::updateOrCreate(
                ['slug' => 'lead-asks-pricing-instagram'],
                [
                    'name' => 'Lead asks pricing on Instagram',
                    'description' => 'A pricing-curious lead reaches out via Instagram Direct.',
                    'persona_id' => $personaLead->id,
                    'channel' => 'meta_instagram_direct',
                    'target_org_id' => config('bartender.target_org_id', 'test-org'),
                    'script' => 'Ask for pricing via Instagram DM.',
                ]
            );
        }

        if ($personaFrustrated) {
            Scenario::updateOrCreate(
                ['slug' => 'frustrated-customer-messenger'],
                [
                    'name' => 'Frustrated customer on Messenger',
                    'description' => 'An upset customer demands support via Messenger.',
                    'persona_id' => $personaFrustrated->id,
                    'channel' => 'meta_messenger',
                    'target_org_id' => config('bartender.target_org_id', 'test-org'),
                    'script' => 'Demand a refund. Escalate if needed.',
                ]
            );

            Scenario::updateOrCreate(
                ['slug' => 'frustrated-customer-evolution'],
                [
                    'name' => 'Frustrated customer on Evolution',
                    'description' => 'An upset customer demands support via Evolution WhatsApp.',
                    'persona_id' => $personaFrustrated->id,
                    'channel' => 'evolution_whatsapp',
                    'target_org_id' => config('bartender.target_org_id', 'test-org'),
                    'script' => 'Demand a refund via WhatsApp (Evolution).',
                ]
            );
        }

        ChannelInstance::firstOrCreate(
            ['external_id' => '1234567890'],
            [
                'provider' => 'meta_whatsapp_cloud',
                'channel_type' => 'whatsapp',
                'name' => 'Test WA Cloud',
                'config' => ['phone_number_id' => '1234567890', 'waba_id' => 'waba_123'],
            ]
        );

        ChannelInstance::firstOrCreate(
            ['external_id' => 'ig_account_1'],
            [
                'provider' => 'meta_instagram_direct',
                'channel_type' => 'instagram',
                'name' => 'Test Instagram',
                'config' => ['instagram_business_account_id' => 'ig_account_1'],
            ]
        );

        ChannelInstance::firstOrCreate(
            ['external_id' => 'page_1'],
            [
                'provider' => 'meta_messenger',
                'channel_type' => 'messenger',
                'name' => 'Test Messenger',
                'config' => ['page_id' => 'page_1'],
            ]
        );

        ChannelInstance::firstOrCreate(
            ['external_id' => 'evo_instance_1'],
            [
                'provider' => 'evolution_whatsapp',
                'channel_type' => 'whatsapp',
                'name' => 'Test Evolution',
                'config' => ['instance_name' => 'evo_instance_1'],
            ]
        );
    }
}
