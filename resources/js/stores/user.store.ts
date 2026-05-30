import api from "@/config/axios";
import type { UserType } from "@/types/user.type";
import { defineStore } from "pinia";
import type { AxiosError } from "axios";

type FetchCurrentUserOptions = {
    endpoint?: string;
    silent?: boolean;
};

type UserState = {
    user: UserType | null;
    isBootstrapped: boolean;
    isLoading: boolean;
    errorMessage: string;
};

type CurrentUserResponse = UserType;

export const useUserStore = defineStore("user", {
    state: (): UserState => ({
        user: null,
        isBootstrapped: false,
        isLoading: false,
        errorMessage: "",
    }),

    getters: {
        isAuthenticated: (state): boolean => state.user !== null,
        displayName: (state): string => {
            return state.user?.full_name || state.user?.username || "";
        },
    },

    actions: {
        setUser(user: UserType | null): void {
            this.user = user;
        },

        clearUser(): void {
            this.user = null;
        },

        resetState(): void {
            this.user = null;
            this.isBootstrapped = false;
            this.isLoading = false;
            this.errorMessage = "";
        },

        async fetchCurrentUser(options: FetchCurrentUserOptions = {}): Promise<UserType | null> {
            const { endpoint = "/api/user", silent = false } = options;

            if (!silent) {
                this.isLoading = true;
            }

            this.errorMessage = "";

            try {
                const response = await api.get<CurrentUserResponse>(endpoint);

                this.user = response.data;

                return this.user;
            } catch (error) {
                const axiosError = error as AxiosError<{ message?: string }>;

                if (axiosError.response?.status === 401) {
                    this.clearUser();
                } else {
                    this.errorMessage =
                        axiosError.response?.data?.message ||
                        "Không thể tải thông tin người dùng.";
                }

                return null;
            } finally {
                this.isBootstrapped = true;
                this.isLoading = false;
            }
        },

        async bootstrap(options: FetchCurrentUserOptions = {}): Promise<UserType | null> {
            if (this.isBootstrapped && this.user) {
                return this.user;
            }

            return this.fetchCurrentUser(options);
        },
    },
});
