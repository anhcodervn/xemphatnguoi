export interface WalletType {
    id: number;
    user_id: number;
    type: string;
    balance: string;
    hold_balance: string;
    total_recharge: string;
    total_spent: string;
    created_at: string | null;
    updated_at: string | null;
}

export interface WalletDepositCreditedEvent {
    payment_transaction_id: number;
    transaction_code: string;
    amount: string;
    balance: string;
    total_recharge: string;
    status: 'paid';
    message: string;
    credited_at: string;
    notification: {
        id: number;
        scope: 'user';
        title: string;
        content: string;
        redirect_url: string | null;
        type: string | null;
        is_read: boolean;
        created_at: string | null;
    };
}

export interface WalletBalanceChangedEvent {
    wallet_type: string;
    balance: string;
    hold_balance: string;
    total_recharge: string;
    total_spent: string;
    change_type: string;
    amount: string;
    transaction_id: number;
    description: string;
    changed_at: string;
    notification: WalletDepositCreditedEvent['notification'] | null;
}
