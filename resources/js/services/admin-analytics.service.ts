import api from "@/config/axios";
import type { AdminAnalyticsResponse, AnalyticsRange } from "@/types/admin-analytics.type";

export const adminAnalyticsService = {
    async dashboard(range: AnalyticsRange = "7d"): Promise<AdminAnalyticsResponse> {
        const response = await api.get("/api/admin-api/analytics", {
            params: { range },
        });

        return response.data.data as AdminAnalyticsResponse;
    },

    async testDiscordWebhook(payload: { webhook_index: number; event: string }): Promise<void> {
        await api.post("/api/admin-api/analytics/discord-webhooks/test", payload);
    },
};
