import type { CurrentUserSubscriptionType } from "./user-subscription.type";
import { WalletType } from "./wallet.type";

export interface UserType {
    id: number;
    username: string;
    email: string | null;
    phone: string | null;
    full_name: string | null;
    avatar: string | null;
    email_verified_at: string | null;
    role: string;
    status: number;
    last_login_at: string | null;
    last_login_ip: string | null;
    referral_code: string | null;
    referred_by: string | null;
    created_at: string;
    updated_at: string;
    wallet: WalletType;
    user_subscriptions: CurrentUserSubscriptionType | null;
}
