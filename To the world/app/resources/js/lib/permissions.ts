import { usePage } from '@inertiajs/react';
import type { PageProps, Permissions } from '@/types';

const EMPTY_PERMISSIONS: Permissions = {
    isAdmin: false,
    isManager: false,
    isClerk: false,
    canApprove: false,
    canManageUsers: false,
    canManageDocuments: false,
};

export function usePermissions(): Permissions {
    const page = usePage<PageProps>();
    return page.props.permissions ?? EMPTY_PERMISSIONS;
}

export function useUnreadMentions(): number {
    const page = usePage<PageProps>();
    return page.props.unreadMentions ?? 0;
}
