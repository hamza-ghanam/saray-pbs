<?php

namespace Database\Seeders;

use App\Models\GeneralSetting;
use Illuminate\Database\Seeder;

class GeneralSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            // ─── Company ──────────────────────────────────────────────
            ['group' => 'company', 'key' => 'name',       'value' => 'Unique Saray Properties L.L.C',                                                                          'type' => 'string'],
            ['group' => 'company', 'key' => 'name_ar',    'value' => 'يونيك سراي للعقارات ش.ذ.م.م',                                                                           'type' => 'string'],
            ['group' => 'company', 'key' => 'license',    'value' => '1343857',                                                                                                'type' => 'string'],
            ['group' => 'company', 'key' => 'dld',        'value' => '2055',                                                                                                   'type' => 'string'],
            ['group' => 'company', 'key' => 'email',      'value' => 'info@uniquesaray.com',                                                                                   'type' => 'string'],
            ['group' => 'company', 'key' => 'phone',      'value' => '+971 4 55 48787',                                                                                        'type' => 'string'],
            ['group' => 'company', 'key' => 'broker_auth_rep',      'value' => 'Feras Zaiter',                                                                                        'type' => 'string'],
            ['group' => 'company', 'key' => 'po_box',     'value' => '.....',                                                                                                  'type' => 'string'],
            ['group' => 'company', 'key' => 'address',    'value' => 'Offices 301 & 308, building 2, Bay Square, Business Bay Building, Dubai, United Arab Emirates',          'type' => 'string'],
            ['group' => 'company', 'key' => 'address_ar', 'value' => 'دبي، منطقة الخليج التجاري، بي سيكوير، المبنى رقم (2)، المكتبين (301،302)',                             'type' => 'string'],

            // ─── Bank Account ─────────────────────────────────────────
            ['group' => 'bank', 'key' => 'bank',           'value' => 'Emirates NBD Bank PJSC',           'type' => 'string'],
            ['group' => 'bank', 'key' => 'branch',         'value' => 'Main Branch',                      'type' => 'string'],
            ['group' => 'bank', 'key' => 'account_name',   'value' => 'UNIQUE SARAY PROPERTIES LLC.',     'type' => 'string'],
            ['group' => 'bank', 'key' => 'account_number', 'value' => '1015931383801',                    'type' => 'string'],
            ['group' => 'bank', 'key' => 'iban',           'value' => 'AE940260001015931383801',          'type' => 'string'],
            ['group' => 'bank', 'key' => 'swift',          'value' => 'EBILAEAD',                         'type' => 'string'],

            // ─── Escrow Account ───────────────────────────────────────
            ['group' => 'escrow', 'key' => 'bank',           'value' => 'Emirates NBD Bank PJSC',                    'type' => 'string'],
            ['group' => 'escrow', 'key' => 'branch',         'value' => 'Main Branch',                               'type' => 'string'],
            ['group' => 'escrow', 'key' => 'account_name',   'value' => 'Saray Prime Residence Escrow Account',      'type' => 'string'],
            ['group' => 'escrow', 'key' => 'account_number', 'value' => '0205931383803',                             'type' => 'string'],
            ['group' => 'escrow', 'key' => 'iban',           'value' => 'AE180260000205931383803',                   'type' => 'string'],
            ['group' => 'escrow', 'key' => 'bic',            'value' => 'EBILAEADXXX',                               'type' => 'string'],

        ];

        foreach ($settings as $setting) {
            GeneralSetting::updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }
    }
}
