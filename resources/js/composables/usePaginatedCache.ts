import { ref } from "vue";
import api from "@/config/axios";

export function usePaginatedCache(apiUrl: string) {
    const cache = ref<Record<number, any[]>>({});
    const loading = ref(false);
    const currentPage = ref(1);
    const totalPages = ref(1);

    const fetchPage = async (page: number, showLoading = true) => {
        if (cache.value[page]) return;

        if (showLoading) loading.value = true;

        try {
            const res = await api.get(apiUrl, {
                params: { page },
            });

            // 👇 FIX ở đây
            const pagination = res.data.data;

            cache.value[page] = pagination.data; // data thật
            totalPages.value = pagination.last_page;
        } catch (err) {
            console.error("fetchPage error:", err);
        } finally {
            if (showLoading) loading.value = false;
        }
    };

    const prefetch = (page: number) => {
        [page + 1, page + 2].forEach((p) => {
            if (p <= totalPages.value && !cache.value[p]) {
                fetchPage(p, false);
            }
        });
    };

    const goToPage = async (page: number) => {
        currentPage.value = page;

        if (!cache.value[page]) {
            await fetchPage(page, true);
        }

        prefetch(page);
    };

    const init = async () => {
        await Promise.all([
            fetchPage(1),
            fetchPage(2, false),
            fetchPage(3, false),
        ]);

        currentPage.value = 1;
    };

    return {
        cache,
        loading,
        currentPage,
        totalPages,
        goToPage,
        init,
    };
}