<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GeneralSetting;
use App\Services\SettingService;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SetGeneralSetting extends Command
{
    protected $signature = 'setting:set {key} {value}';
    protected $description = 'Set or update a general setting by key';

    public function handle()
    {
        $key = $this->argument('key');
        $value = $this->argument('value');

        app(SettingService::class)->set($key, $value);

        $this->info("Setting [{$key}] updated successfully.");
        $this->line("Value: {$value}");

        return CommandAlias::SUCCESS;
    }
}
