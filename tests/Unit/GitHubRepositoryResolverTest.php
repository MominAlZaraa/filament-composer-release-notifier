<?php

use MominAlZaraa\FilamentComposerReleaseNotifier\Services\GitHubRepositoryResolver;

it('parses https github urls', function () {
    $r = new GitHubRepositoryResolver;

    expect($r->resolve('https://github.com/foo/bar.git'))->toBe(['owner' => 'foo', 'repo' => 'bar'])
        ->and($r->resolve('https://github.com/foo/bar'))->toBe(['owner' => 'foo', 'repo' => 'bar']);
});

it('parses ssh github urls', function () {
    $r = new GitHubRepositoryResolver;

    expect($r->resolve('git@github.com:foo/bar.git'))->toBe(['owner' => 'foo', 'repo' => 'bar']);
});

it('returns null for non github', function () {
    $r = new GitHubRepositoryResolver;

    expect($r->resolve('https://gitlab.com/foo/bar.git'))->toBeNull();
});
