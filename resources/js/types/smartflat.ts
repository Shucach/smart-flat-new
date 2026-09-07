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
    | 'frame.restart'
    | 'torrent.view'
    | 'torrent.add'
    | 'torrent.manage'
    | 'torrent.delete'
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

export type FrameMediaKind = 'image' | 'video';

export type FrameImage = {
    name: string;
    preview: string;
    kind: FrameMediaKind;
};

export type FrameUploadStatus =
    | 'queued'
    | 'transcoding'
    | 'uploading'
    | 'completed'
    | 'failed';

/** One file the queue is working on, as the frame page reads it back. */
export type FrameUpload = {
    id: number;
    name: string;
    kind: FrameMediaKind;
    status: FrameUploadStatus;
    statusLabel: string;
    progress: number;
    message: string | null;
};

export type FrameLimits = {
    videoSeconds: number;
    videoMegabytes: number;
};

export type FramePagination = {
    page: number;
    perPage: number;
    lastPage: number;
    total: number;
};

/* ----------------------------------------------------------------- torrents */

export type TorrentStatus =
    | 'stopped'
    | 'check-wait'
    | 'checking'
    | 'download-wait'
    | 'downloading'
    | 'seed-wait'
    | 'seeding';

export type TorrentFilePriority = 'low' | 'normal' | 'high';

/** Everything `torrents.action` accepts; mirrors the TorrentAction enum. */
export type TorrentActionName =
    | 'start'
    | 'start-now'
    | 'stop'
    | 'verify'
    | 'reannounce'
    | 'queue-top'
    | 'queue-up'
    | 'queue-down'
    | 'queue-bottom';

export type Torrent = {
    id: number;
    hash: string;
    name: string;
    status: TorrentStatus;
    statusLabel: string;
    /** Already scaled to 0–100. */
    percentDone: number;
    recheckPercent: number;
    metadataPercent: number;
    rateDownload: number;
    rateUpload: number;
    /** Null when Transmission cannot tell yet. */
    etaSeconds: number | null;
    ratio: number;
    totalSizeBytes: number;
    sizeForHumans: string;
    sizeWhenDoneBytes: number;
    leftUntilDoneBytes: number;
    downloadedBytes: number;
    uploadedBytes: number;
    peersConnected: number;
    peersSendingToUs: number;
    peersGettingFromUs: number;
    queuePosition: number;
    isStalled: boolean;
    isFinished: boolean;
    isPaused: boolean;
    downloadDir: string;
    error: string | null;
    addedAt: string | null;
    doneAt: string | null;
    labels: string[];
};

export type TorrentFile = {
    index: number;
    name: string;
    lengthBytes: number;
    completedBytes: number;
    sizeForHumans: string;
    percentDone: number;
    wanted: boolean;
    priority: TorrentFilePriority;
};

export type TorrentTracker = {
    id: number;
    host: string;
    announce: string;
    seederCount: number;
    leecherCount: number;
    lastAnnounceResult: string | null;
    lastAnnounceSucceeded: boolean;
};

export type TorrentPeer = {
    address: string;
    client: string;
    progressPercent: number;
    rateDownload: number;
    rateUpload: number;
    flags: string;
};

/** Per-torrent overrides; a null speed means the session limits apply. */
export type TorrentLimits = {
    downloadKilobytes: number | null;
    uploadKilobytes: number | null;
    seedRatio: number | null;
    seedForever: boolean;
    honorsSessionLimits: boolean;
};

export type TorrentDetail = Torrent & {
    files: TorrentFile[];
    trackers: TorrentTracker[];
    peers: TorrentPeer[];
    limits: TorrentLimits;
    comment: string | null;
    creator: string | null;
    magnetLink: string | null;
    lastActivityAt: string | null;
    pieceCount: number;
    pieceSizeBytes: number;
};

export type TorrentStats = {
    rateDownload: number;
    rateUpload: number;
    torrentCount: number;
    activeTorrentCount: number;
    pausedTorrentCount: number;
    sessionDownloadedBytes: number;
    sessionUploadedBytes: number;
    totalDownloadedBytes: number;
    totalUploadedBytes: number;
    freeSpaceBytes: number;
    totalSpaceBytes: number;
    version: string;
};

/** Speeds are kilobytes per second, the unit Transmission itself uses. */
export type TorrentSessionSettings = {
    downloadDir: string;
    startAddedTorrents: boolean;
    speedLimitDown: number;
    speedLimitDownEnabled: boolean;
    speedLimitUp: number;
    speedLimitUpEnabled: boolean;
    altSpeedDown: number;
    altSpeedUp: number;
    altSpeedEnabled: boolean;
    downloadQueueSize: number;
    downloadQueueEnabled: boolean;
    seedQueueSize: number;
    seedQueueEnabled: boolean;
    seedRatioLimit: number;
    seedRatioLimited: boolean;
    peerLimitGlobal: number;
    peerPort: number;
};

export type TorrentLimitsInfo = {
    maxFileMegabytes: number;
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
