<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Feature\Commands;

use JaapTech\NepaliPayment\Tests\TestCase;

class CheckConfigCommandTest extends TestCase
{
    public function test_check_config_command_exists()
    {
        $this->artisan('nepali-payment:check')
            ->assertSuccessful();
    }
}
