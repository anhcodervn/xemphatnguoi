export interface BankType {
    id: number;
    code: string;
    name: string;
    short_name: string | null;
    logo: string | null;
    bg_color: string;
    metadata: Record<string, unknown> | null;
}

export interface BankAccountType {
    id: number;
    bank_code: string;
    bank_name: string;
    bank_full_name: string | null;
    bank_short_name: string | null;
    bank_logo: string | null;
    bank_bg_color: string;
    account_name: string;
    account_number: string;
    username: string | null;
    status: 'active' | 'inactive' | 'error';
    last_sync_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface BankTransactionType {
    id: number;
    transaction_id: string;
    amount: string;
    description: string | null;
    transaction_time: string | null;
    type: 'credit' | 'debit' | null;
    raw_data: Record<string, unknown> | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface WebhookType {
    id: number;
    bank_account_id: number | null;
    name: string | null;
    url: string;
    secret_key: string;
    events: string[];
    event_keyword: string | null;
    status: 'active' | 'inactive';
    created_at: string | null;
    updated_at: string | null;
}

export interface WebhookLogType {
    id: number;
    event_keyword: string | null;
    payload: string | null;
    response: string | null;
    status_code: number | null;
    attempt: number;
    created_at: string | null;
    updated_at: string | null;
}
