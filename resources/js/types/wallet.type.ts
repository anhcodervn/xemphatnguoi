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
