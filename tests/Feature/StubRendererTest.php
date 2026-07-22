<?php

namespace SamuelNunes\LaravelDddToolkit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use RuntimeException;
use SamuelNunes\LaravelDddToolkit\Support\StubRenderer;
use SamuelNunes\LaravelDddToolkit\Tests\TestCase;

class StubRendererTest extends TestCase
{
    public function test_it_rejects_non_string_replacement_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Replacement [module] must be a string, array given.');

        (new StubRenderer(app(), new Filesystem()))->render('module-routes.stub', [
            'module' => ['Order'],
        ]);
    }

    public function test_missing_stub_exception_includes_searched_paths(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stub [missing.stub] was not found. Searched paths:');
        $this->expectExceptionMessage(base_path('stubs/vendor/laravel-ddd-toolkit/missing.stub'));

        (new StubRenderer(app(), new Filesystem()))->stubPath('missing.stub');
    }
}
