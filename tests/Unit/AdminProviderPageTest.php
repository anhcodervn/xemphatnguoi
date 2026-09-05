<?php

it('exposes exclusive provider controls through the admin page and service', function (): void {
    $projectRoot = dirname(__DIR__, 2);
    $page = file_get_contents($projectRoot.'/resources/js/pages/admin/traffic-fines/ProviderPage.vue');
    $service = file_get_contents($projectRoot.'/resources/js/services/admin-traffic-fine.service.ts');

    expect($page)
        ->toContain("import DataTable from '@/components/shared/DataTable/index.vue'")
        ->toContain("import Modal from '@/components/shared/Modal/index.vue'")
        ->toContain('openCreateModal')
        ->toContain('deleteProvider')
        ->toContain('loadBalance')
        ->toContain('type="password"')
        ->and($service)
        ->toContain('AdminProviderOverview')
        ->toContain('createProvider(payload: AdminProviderStore)')
        ->toContain('deleteProvider(provider: string)')
        ->toContain('providerBalance(provider: string')
        ->toContain('updateProvider(provider: string')
        ->toContain('/api/admin-api/traffic-fines/provider/${encodeURIComponent(provider)}');
});
