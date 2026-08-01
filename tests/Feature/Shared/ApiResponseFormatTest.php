<?php

namespace Tests\Feature\Shared;

use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiResponseFormatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api/v1')->group(function (): void {
            Route::get('/__test/success', fn () => response()->json([
                'success' => true,
                'message' => 'ok',
                'data' => ['sample' => true],
            ]));

            Route::get('/__test/validation', function (): void {
                validator(['name' => ''], ['name' => 'required'])->validate();
            });

            Route::get('/__test/domain-error', function (): void {
                throw new DomainException('Stok tidak mencukupi.', 422, [
                    'quantity' => ['Stok tidak mencukupi.'],
                ]);
            });
        });
    }

    public function test_success_response_uses_standard_envelope(): void
    {
        $response = $this->getJson('/api/v1/__test/success');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'ok')
            ->assertJsonPath('data.sample', true);
    }

    public function test_validation_error_uses_standard_envelope(): void
    {
        $response = $this->getJson('/api/v1/__test/validation');

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Data yang dikirim tidak valid.')
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors' => ['name'],
            ]);
    }

    public function test_domain_exception_uses_standard_envelope(): void
    {
        $response = $this->getJson('/api/v1/__test/domain-error');

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Stok tidak mencukupi.')
            ->assertJsonPath('errors.quantity.0', 'Stok tidak mencukupi.');
    }
}
