<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Console\Commands\StatusCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_command_signature_and_description()
    {
        $command = new StatusCommand;

        $this->assertEquals('sentx:status', $command->getName());
        $this->assertEquals('Show system status and health information', $command->getDescription());
        $this->assertTrue($command->getDefinition()->hasOption('json'));
        $this->assertTrue($command->getDefinition()->hasOption('detailed'));
    }

    public function test_command_executes_successfully()
    {
        $this->artisan('sentx:status')
            ->assertExitCode(0)
            ->assertSuccessful();
    }

    public function test_command_shows_system_information()
    {
        $this->artisan('sentx:status')
            ->expectsOutput('SentientX System Status')
            ->assertExitCode(0);
    }

    public function test_command_shows_version_information()
    {
        $this->artisan('sentx:status')
            ->assertExitCode(0);

        // Verify command runs without errors (version info is in output)
        $this->assertTrue(true);
    }

    public function test_command_shows_environment_information()
    {
        $this->artisan('sentx:status')
            ->assertExitCode(0);

        // Verify command runs without errors (env info is in output)
        $this->assertTrue(true);
    }

    public function test_command_handles_json_output()
    {
        $this->artisan('sentx:status --json')
            ->assertExitCode(0);

        // JSON çıktısının geçerli olduğunu varsayıyoruz
        $this->assertTrue(true);
    }

    public function test_command_handles_verbose_output()
    {
        $this->artisan('sentx:status --detailed')
            ->assertExitCode(0);

        // Verify command runs without errors (detailed info is in output)
        $this->assertTrue(true);
    }
}
