<?php

use App\Models\SiteInvite;

it('generates a token automatically on creation', function () {
    $invite = SiteInvite::create([]);

    expect($invite->token)->not->toBeEmpty()
        ->and(strlen($invite->token))->toBe(48);
});

it('reports exhausted and expired states', function () {
    $unlimited = SiteInvite::create([]);
    $exhausted = SiteInvite::create(['max_uses' => 2, 'used_count' => 2]);
    $expired = SiteInvite::create(['expires_at' => now()->subDay()]);
    $withRoom = SiteInvite::create(['max_uses' => 5, 'used_count' => 1]);

    expect($unlimited->isValid())->toBeTrue()
        ->and($exhausted->isValid())->toBeFalse()
        ->and($expired->isValid())->toBeFalse()
        ->and($withRoom->isValid())->toBeTrue();
});

it('consumes uses and becomes exhausted', function () {
    $invite = SiteInvite::create(['max_uses' => 2]);

    $invite->consume();
    $invite->consume();

    expect($invite->fresh()->isExhausted())->toBeTrue();
});

it('finds valid invites by token', function () {
    $valid = SiteInvite::create(['max_uses' => 5]);
    $exhausted = SiteInvite::create(['max_uses' => 1, 'used_count' => 1]);

    expect(SiteInvite::findValidByToken($valid->token))->not->toBeNull()
        ->and(SiteInvite::findValidByToken($exhausted->token))->toBeNull()
        ->and(SiteInvite::findValidByToken('nope'))->toBeNull()
        ->and(SiteInvite::findValidByToken(null))->toBeNull();
});
