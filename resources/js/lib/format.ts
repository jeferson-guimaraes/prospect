const DATE_ONLY_PATTERN = /^(\d{4})-(\d{2})-(\d{2})$/;

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

/**
 * Interpreta a data sem deslocamento de fuso: valores "YYYY-MM-DD" são
 * tratados como data local, e não como meia-noite UTC.
 */
export function parseDate(value: string): Date {
    const match = value.match(DATE_ONLY_PATTERN);

    if (match) {
        return new Date(
            Number(match[1]),
            Number(match[2]) - 1,
            Number(match[3]),
        );
    }

    return new Date(value);
}

export function formatDate(
    value: string | null | undefined,
    fallback = '—',
): string {
    if (!value) {
        return fallback;
    }

    const date = parseDate(value);

    if (Number.isNaN(date.getTime())) {
        return fallback;
    }

    return date.toLocaleDateString('pt-BR');
}

export function formatDateTime(
    value: string | null | undefined,
    fallback = '—',
): string {
    if (!value) {
        return fallback;
    }

    const date = parseDate(value);

    if (Number.isNaN(date.getTime())) {
        return fallback;
    }

    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Formata a data no padrão aceito por inputs "datetime-local" ("YYYY-MM-DDTHH:mm"),
 * no fuso local do navegador.
 */
export function toDateTimeLocal(date: Date = new Date()): string {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
