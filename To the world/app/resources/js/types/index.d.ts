export interface UserPreferences {
    language?: 'en' | 'pt';
    theme?: 'light' | 'dark' | 'auto';
    timezone?: string | null;
    rows_per_page?: number | null;
    default_country?: string | null;
    notifications?: {
        measurement_reviewed?: boolean;
        threshold_exceeded?: boolean;
        incident_reported?: boolean;
        incident_status_changed?: boolean;
        pin_used?: boolean;
    };
}

export interface User {
    id: string;
    display_name: string;
    email: string;
    role: Role;
    photo_url?: string | null;
    country?: string | null;
    organization?: string | null;
    telephone?: string | null;
    email_verified_at: string | null;
    preferences?: UserPreferences | null;
}

export type Role = 'admin' | 'manager' | 'clerk';

export interface Permissions {
    isAdmin: boolean;
    isManager: boolean;
    isClerk: boolean;
    canApprove: boolean;
    canManageUsers: boolean;
    canManageDocuments: boolean;
}

export interface MentionUserRef {
    id: string;
    display_name: string;
    role?: Role | string;
}

export interface CommentAuthorRef {
    id: string;
    display_name: string;
    email?: string;
    photo_url?: string | null;
    role?: Role | string;
}

export interface CommentMentionRef {
    id: string;
    user: MentionUserRef | null;
    read_at?: string | null;
}

export interface CommentRecord {
    id: string;
    commentable_type: string;
    commentable_id: string;
    parent_id: string | null;
    field_name: string | null;
    body: string;
    resolved_at: string | null;
    resolved_by_id: string | null;
    created_at: string;
    updated_at: string;
    author: CommentAuthorRef | null;
    mentions: CommentMentionRef[];
}

export interface StationRevisionRecord {
    id: string;
    station_id: string | null;
    submitted_by_id: string;
    status: 'pending' | 'approved' | 'rejected';
    change_type: 'create' | 'update' | 'delete';
    /**
     * For `update`/`delete` revisions this is a diff of `{ from, to }` per field.
     * For `create` revisions this is the full station payload (no `from`/`to` wrapper).
     */
    proposed_changes: Record<string, { from: unknown; to: unknown } | unknown>;
    reviewed_by_id: string | null;
    reviewed_at: string | null;
    review_notes: string | null;
    is_self_override: boolean;
    created_at: string;
    updated_at: string;
}

export interface NavItem {
    id: string;
    label: string;
    icon: string;
    href: string;
    method: 'get' | 'post';
    group: 'main' | 'bottom';
    supportsGis: boolean;
    badge: number | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    permissions: Permissions;
    unreadMentions?: number;
    navigation: {
        main: NavItem[];
        bottom: NavItem[];
    };
    navigationMeta: {
        derivedFromDatabase: boolean;
        userRole: string | null | undefined;
    };
    flash: {
        status?: string | null;
    };
};
