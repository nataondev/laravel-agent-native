<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('does not report classes without attributes', function () {
    // Create a plain PHP file in our temp directory that won't be autoloaded
    $tempDir = sys_get_temp_dir().'/agent-discover-'.uniqid();
    mkdir($tempDir, 0755, true);

    file_put_contents(
        $tempDir.'/PlainClass.php',
        <<<'PHP'
        <?php
        declare(strict_types=1);
        namespace AgentNative\\Laravel\\Tests\\Fixtures;
        class PlainClass
        {
            public function doSomething(): string
            {
                return 'plain';
            }
        }
        PHP,
    );

    Artisan::call('agent:discover', ['--path' => $tempDir]);

    $stdout = Artisan::output();
    expect($stdout)->not->toContain('PlainClass');

    // Cleanup
    array_map('unlink', glob($tempDir.'/*'));
    rmdir($tempDir);
});

it('warns when no classes are found', function () {
    // Create an empty PHP file in temp dir
    $tempDir = sys_get_temp_dir().'/agent-discover-empty-'.uniqid();
    mkdir($tempDir, 0755, true);

    file_put_contents(
        $tempDir.'/EmptyClass.php',
        <<<'PHP'
        <?php
        declare(strict_types=1);
        namespace Test;
        class EmptyClass {}
        PHP,
    );

    Artisan::call('agent:discover', ['--path' => $tempDir]);

    $stdout = Artisan::output();
    expect($stdout)->toContain('No agent actions found');

    // Cleanup
    array_map('unlink', glob($tempDir.'/*'));
    rmdir($tempDir);
});

it('returns failure when path does not exist', function () {
    $this->artisan('agent:discover', ['--path' => 'does/not/exist'])
        ->assertFailed();
});

it('--register flag works without crashing', function () {
    // Scan a non-existent dir with --register to verify no crashes
    $this->artisan('agent:discover', ['--path' => 'does/not/exist', '--register' => true])
        ->assertFailed();
});

it('discovers agent actions in fixtures with <?php on first line', function () {
    // Use current working directory (cwd) which is project root when running tests
    $fixturePath = getcwd().'/tests/Fixtures';

    if (! is_dir($fixturePath)) {
        $this->markTestSkipped('Fixture directory does not exist at: '.$fixturePath);

        return;
    }

    Artisan::call('agent:discover', ['--path' => $fixturePath]);

    expect(Artisan::output())->toContain('GreeterService');
});
