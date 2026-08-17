<?php

it('keeps one app-managed turnstile response field', function () {
    $projectRoot = dirname(__DIR__, 2);
    $lookupForm = file_get_contents($projectRoot.'/resources/views/components/lookup-form.blade.php');
    $lookupScript = file_get_contents($projectRoot.'/resources/js/public-lookup.ts');

    expect(substr_count($lookupForm, 'name="cf-turnstile-response"'))
        ->toBe(1)
        ->and($lookupScript)
        ->toContain("'response-field': boolean;")
        ->toContain("'response-field': false,")
        ->toContain('tokenInput.value = token;');
});
