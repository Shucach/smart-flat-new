const BYTE_UNITS = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ', 'ПБ'] as const;

const LOCALE = 'uk-UA';

function pluralize(value: number, one: string, few: string, many: string) {
    const mod10 = value % 10;
    const mod100 = value % 100;

    if (mod10 === 1 && mod100 !== 11) {
        return one;
    }

    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
        return few;
    }

    return many;
}

/**
 * Formats a byte count using binary units with Ukrainian labels.
 */
export function formatBytes(bytes: number | null | undefined): string {
    if (bytes === null || bytes === undefined || !Number.isFinite(bytes)) {
        return '—';
    }

    if (bytes <= 0) {
        return `0 ${BYTE_UNITS[0]}`;
    }

    const exponent = Math.min(
        BYTE_UNITS.length - 1,
        Math.floor(Math.log(bytes) / Math.log(1024)),
    );
    const value = bytes / 1024 ** exponent;
    const fractionDigits = exponent === 0 ? 0 : value < 10 ? 1 : 0;

    return `${value.toLocaleString(LOCALE, {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    })} ${BYTE_UNITS[exponent]}`;
}

/**
 * Formats a transfer rate, e.g. «1,4 МБ/с».
 */
export function formatSpeed(bytesPerSecond: number | null | undefined): string {
    if (
        bytesPerSecond === null ||
        bytesPerSecond === undefined ||
        !Number.isFinite(bytesPerSecond)
    ) {
        return '—';
    }

    return `${formatBytes(bytesPerSecond)}/с`;
}

/**
 * Renders "використано з усього" for a disk or memory pair.
 */
export function formatBytesRatio(used: number, total: number): string {
    return `${formatBytes(used)} з ${formatBytes(total)}`;
}

/**
 * Turns a number of seconds into a short human phrase, e.g. "3 дні 4 год".
 */
export function formatDuration(seconds: number | null | undefined): string {
    if (
        seconds === null ||
        seconds === undefined ||
        !Number.isFinite(seconds) ||
        seconds < 0
    ) {
        return '—';
    }

    const totalMinutes = Math.floor(seconds / 60);

    if (totalMinutes < 1) {
        return 'менше хвилини';
    }

    const days = Math.floor(totalMinutes / (60 * 24));
    const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
    const minutes = totalMinutes % 60;

    const parts: string[] = [];

    if (days > 0) {
        parts.push(`${days} ${pluralize(days, 'день', 'дні', 'днів')}`);
    }

    if (hours > 0) {
        parts.push(`${hours} год`);
    }

    if (minutes > 0 && days === 0) {
        parts.push(`${minutes} хв`);
    }

    return parts.join(' ');
}

function toDate(value: string | null | undefined): Date | null {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

/**
 * Formats an ISO timestamp as "12 січ 2026, 14:05".
 */
export function formatDateTime(value: string | null | undefined): string {
    const date = toDate(value);

    if (!date) {
        return '—';
    }

    return date.toLocaleString(LOCALE, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Formats an ISO timestamp as a bare date, "12 січ 2026".
 */
export function formatDate(value: string | null | undefined): string {
    const date = toDate(value);

    if (!date) {
        return '—';
    }

    return date.toLocaleDateString(LOCALE, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

/**
 * Formats an ISO timestamp as a clock reading, "14:05:32".
 */
export function formatTime(value: string | null | undefined): string {
    const date = toDate(value);

    if (!date) {
        return '—';
    }

    return date.toLocaleTimeString(LOCALE, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

/**
 * Rounds a 0–100 value and appends the percent sign.
 */
export function formatPercent(
    value: number | null | undefined,
    fractionDigits = 0,
): string {
    if (value === null || value === undefined || !Number.isFinite(value)) {
        return '—';
    }

    return `${value.toLocaleString(LOCALE, {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    })}%`;
}

/**
 * Formats a CPU temperature, or a dash when the host does not report one.
 */
export function formatTemperature(value: number | null | undefined): string {
    if (value === null || value === undefined || !Number.isFinite(value)) {
        return '—';
    }

    return `${Math.round(value)} °C`;
}

/**
 * Ukrainian pluralisation helper for counters shown in the UI.
 */
export function pluralizeUk(
    value: number,
    one: string,
    few: string,
    many: string,
): string {
    return `${value} ${pluralize(value, one, few, many)}`;
}
