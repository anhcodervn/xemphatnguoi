export type ClientProfileType = {
    id: number;
    username: string;
    email: string | null;
    phone: string | null;
    full_name: string | null;
    avatar: string | null;
    status: string | number;
    role: string | null;
    email_verified_at: string | null;
    last_login_at: string | null;
    last_login_ip: string | null;
    created_at: string | null;
    updated_at: string | null;
    security: {
        has_2fa: boolean;
        email_verified: boolean;
    };
    api_access?: {
        can_create: boolean;
        message: string;
    } | null;
};

export type ClientProfileUpdatePayload = {
    avatar: string | null;
    full_name: string | null;
    email: string | null;
    phone: string | null;
    username: string;
};

export type ClientPasswordUpdatePayload = {
    current_password: string;
    password: string;
    password_confirmation: string;
    logout_other_devices?: boolean;
};

export type LogoutOtherDevicesPayload = {
    current_password: string;
};

export type ClientProfilePaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

export type UserLogItem = {
    id: number;
    time: string | null;
    action: 'login' | 'password_change' | 'profile_update' | 'register' | string;
    label: string;
    ip: string | null;
    device: string;
    browser: string;
    status: 'success' | 'warning' | 'failed' | string;
};

export type WalletTransactionItem = {
    id: number;
    code: string;
    time: string | null;
    content: string | null;
    amount: number;
    balanceAfter: number;
    status: 'success' | 'processing' | 'failed' | string;
    type: 'recharge' | 'deduct' | 'refund' | 'bonus' | string;
};

export type ClientProfilePaginatedResponse<T> = {
    data: T[];
    meta: ClientProfilePaginationMeta;
};
