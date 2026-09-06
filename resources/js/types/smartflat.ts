/**
 * Inertia prop contracts for the SmartFlat sections.
 * Shapes mirror the payloads produced by the Media, Frame, System
 * and Admin controllers — keep them in sync with the backend Data objects.
 */

export type Permission =
    | 'media.view'
    | 'media.delete'
    | 'frame.view'
    | 'frame.upload'
    | 'frame.delete'
    | 'system.view'
    | 'system.power'
    | 'users.manage';

/* ------------------------------------------------------------------ system */

export type HostInfo = {
    name: string;
    os: string;
    uptimeSeconds: number;
    bootedAt: string;
};

export type CpuLoad = {
    usagePercent: number;
    cores: number;
    loadAverage: [number, number, number];
    temperatureCelsius: number | null;
};

export type MemoryUsage = {
    totalBytes: number;
    usedBytes: number;
    freeBytes: number;
    usagePercent: number;
    swapTotalBytes: number;
    swapUsedBytes: number;
};

export type DiskUsage = {
    device: string;
    mountPoint: string;
    label: string;
    totalBytes: number;
    usedBytes: number;
    freeBytes: number;
    usagePercent: number;
};

export type SystemSnapshot = {
    host: HostInfo;
    cpu: CpuLoad;
    memory: MemoryUsage;
    disks: DiskUsage[];
    capturedAt: string;
};

/* ------------------------------------------------------------------- media */

export type MediaEntry = {
    name: string;
    path: string;
    isDirectory: boolean;
    size: number;
    sizeForHumans: string;
    modifiedAt: string;
};

export type MediaBreadcrumb = {
    name: string;
    path: string;
};

export type MediaListing = {
    path: string;
    parentPath: string | null;
    breadcrumbs: MediaBreadcrumb[];
    entries: MediaEntry[];
};

/* ------------------------------------------------------------------- frame */

export type FrameImage = {
    name: string;
    preview: string;
};

export type FramePagination = {
    page: number;
    perPage: number;
    lastPage: number;
    total: number;
};

/* ------------------------------------------------------------------- admin */

export type AdminRole = {
    id: number;
    name: string;
    label: string;
    permissions: string[];
    usersCount: number;
};

export type AdminUserRole = Pick<AdminRole, 'id' | 'name' | 'label'>;

export type AdminUser = {
    id: number;
    name: string;
    email: string;
    roles: AdminUserRole[];
    createdAt: string;
};

export type PermissionOption = {
    value: string;
    label: string;
};

export type PermissionGroup = {
    group: string;
    items: PermissionOption[];
};

/* -------------------------------------------------------------- pagination */

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
    next_page_url: string | null;
    prev_page_url: string | null;
};

/* ------------------------------------------------------------------ charts */

export type ChartTone = 'accent' | 'ok' | 'warn' | 'danger' | 'neutral';

export type ChartThresholds = {
    warn: number;
    danger: number;
};
