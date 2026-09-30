import { index } from '@/routes/prospects';

export type ProspectFilters = {
    search?: string;
    sort_field?: string;
    sort_direction?: string;
    per_page?: number;
    retorno?: string;
    lead_score_tier?: string;
    page?: number;
};

export type ProspectFilterKey = keyof ProspectFilters;

const STORAGE_KEY = 'prospects.index.filters';

export const DEFAULT_PROSPECT_FILTERS: ProspectFilters = {
    sort_field: 'created_at',
    sort_direction: 'desc',
};

export function getProspectInitials(name: string): string {
    const initials = name
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

    return initials || '?';
}

export function clearProspectFilters(): void {
    if (typeof window === 'undefined') {
        return;
    }

    sessionStorage.removeItem(STORAGE_KEY);
}

export function hasActiveProspectFilters(filters: ProspectFilters): boolean {
    if (filters.search?.trim()) {
        return true;
    }

    if (filters.retorno) {
        return true;
    }

    if (filters.lead_score_tier) {
        return true;
    }

    if (filters.page && filters.page > 1) {
        return true;
    }

    if (filters.per_page) {
        return true;
    }

    if (
        filters.sort_field &&
        filters.sort_field !== DEFAULT_PROSPECT_FILTERS.sort_field
    ) {
        return true;
    }

    if (
        filters.sort_direction &&
        filters.sort_direction !== DEFAULT_PROSPECT_FILTERS.sort_direction
    ) {
        return true;
    }

    return false;
}

export function saveProspectFilters(filters: ProspectFilters): void {
    if (typeof window === 'undefined') {
        return;
    }

    const normalized = normalizeProspectFilters(filters);

    if (Object.keys(normalized).length === 0) {
        clearProspectFilters();

        return;
    }

    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(normalized));
}

export function loadProspectFilters(): ProspectFilters {
    if (typeof window === 'undefined') {
        return {};
    }

    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return {};
        }

        return normalizeProspectFilters(JSON.parse(raw) as ProspectFilters);
    } catch {
        return {};
    }
}

export function normalizeProspectFilters(
    filters: ProspectFilters,
): ProspectFilters {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) =>
                value !== undefined &&
                value !== null &&
                value !== '' &&
                value !== 'all',
        ),
    ) as ProspectFilters;
}

export function buildProspectsIndexUrl(
    filters: ProspectFilters = {},
    options: { mergeStored?: boolean } = {},
): string {
    const merged = normalizeProspectFilters(
        options.mergeStored
            ? { ...loadProspectFilters(), ...filters }
            : filters,
    );

    return index.url({ query: merged });
}
