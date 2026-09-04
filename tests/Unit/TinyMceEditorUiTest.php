<?php

it('preserves links and does not reload content emitted by the editor', function () {
    $editor = file_get_contents(dirname(__DIR__, 2).'/resources/js/components/shared/Editor/index.vue');

    expect($editor)
        ->toContain("if (tag === 'a')")
        ->toContain('href?: string;')
        ->toContain('lastEmittedFingerprint = valueFingerprint(value);')
        ->toContain('if (fingerprint === lastEmittedFingerprint)')
        ->not->toContain('span#mceu_56');
});
