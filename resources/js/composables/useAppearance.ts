import { usePreferredDark } from '@vueuse/core';
import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
    toggleAppearance: () => void;
};

/**
 * Browser chrome colours (mobile status bar, PWA title bar) for each theme;
 * they mirror `--background` in resources/css/app.css.
 */
const themeColors: Record<ResolvedAppearance, string> = {
    light: '#f6f7fc',
    dark: '#0a0a0a',
};

/**
 * Points every `theme-color` meta tag at the active theme, otherwise the
 * browser would follow the OS scheme even when the user picked the opposite.
 */
const updateThemeColor = (resolved: ResolvedAppearance): void => {
    document
        .querySelectorAll<HTMLMetaElement>('meta[name="theme-color"]')
        .forEach((meta) => {
            meta.content = themeColors[resolved];
        });
};

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    const resolved: ResolvedAppearance =
        value === 'system' ? (prefersDark() ? 'dark' : 'light') : value;

    document.documentElement.classList.toggle('dark', resolved === 'dark');
    updateThemeColor(resolved);
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const getStoredAppearance = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
};

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const handleSystemThemeChange = () => {
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || 'system');
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    // Initialize theme from saved preference or default to system...
    const savedAppearance = getStoredAppearance();
    updateTheme(savedAppearance || 'system');

    // Set up system theme change listener...
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

const appearance = ref<Appearance>('system');

export function useAppearance(): UseAppearanceReturn {
    const systemPrefersDark = usePreferredDark();

    onMounted(() => {
        const savedAppearance = localStorage.getItem(
            'appearance',
        ) as Appearance | null;

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (appearance.value === 'system') {
            return systemPrefersDark.value ? 'dark' : 'light';
        }

        return appearance.value;
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR...
        setCookie('appearance', value);

        updateTheme(value);
    }

    /** Flips to the opposite of what is on screen right now. */
    function toggleAppearance() {
        updateAppearance(
            resolvedAppearance.value === 'dark' ? 'light' : 'dark',
        );
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
        toggleAppearance,
    };
}
