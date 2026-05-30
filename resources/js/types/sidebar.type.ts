import type { LucideIcon } from 'lucide-vue-next';

export type SidebarItemType = {
    label: string;
    path?: string;
    icon?: LucideIcon;
    children?: SidebarItemType[];
};
