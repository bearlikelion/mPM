<?php

namespace App\Console\Commands;

use App\Support\PublicDemoWorkspace;
use Illuminate\Console\Command;

class ResetPublicDemo extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Reset the public demo organization from the checked-in scaffold dataset';

    public function handle(PublicDemoWorkspace $demo): int
    {
        if (! $demo->enabled()) {
            $this->warn('Public demo is disabled.');

            return self::SUCCESS;
        }

        $result = $demo->reset();

        $this->info(sprintf(
            'Reset %s with %d projects and %d tasks.',
            $result['organization']->name,
            $result['projects'],
            $result['tasks'],
        ));

        return self::SUCCESS;
    }
}
