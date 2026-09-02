<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the company token for valid credentials', function (): void {
    $company = Company::factory()->create(['api_token' => 'known-token']);
    User::factory()->for($company)->create([
        'email' => 'owner@example.test',
        'password' => 'secret-pass',
    ]);

    $this->postJson('/api/login', [
        'email' => 'owner@example.test',
        'password' => 'secret-pass',
    ])
        ->assertOk()
        ->assertJsonPath('token', 'known-token')
        ->assertJsonPath('company.id', $company->id);
});

it('rejects bad credentials with 422', function (): void {
    $company = Company::factory()->create();
    User::factory()->for($company)->create(['email' => 'owner@example.test', 'password' => 'secret-pass']);

    $this->postJson('/api/login', [
        'email' => 'owner@example.test',
        'password' => 'wrong',
    ])->assertStatus(422);
});

it('the issued token authenticates subsequent requests', function (): void {
    $company = Company::factory()->create();
    User::factory()->for($company)->create(['email' => 'owner@example.test', 'password' => 'secret-pass']);

    $token = $this->postJson('/api/login', [
        'email' => 'owner@example.test',
        'password' => 'secret-pass',
    ])->json('token');

    $this->withToken($token)->getJson('/api/resources')->assertOk();
});
