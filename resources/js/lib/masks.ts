export function maskTelefone(value: string): string {
    const cleaned = value.replace(/\D/g, '');
    const { length } = cleaned;

    if (length === 0) {
        return '';
    }

    if (length <= 2) {
        return `(${cleaned}`;
    }

    if (length <= 6) {
        return `(${cleaned.slice(0, 2)}) ${cleaned.slice(2)}`;
    }

    if (length <= 10) {
        return `(${cleaned.slice(0, 2)}) ${cleaned.slice(2, 6)}-${cleaned.slice(6, 10)}`;
    }

    return `(${cleaned.slice(0, 2)}) ${cleaned.slice(2, 7)}-${cleaned.slice(7, 11)}`;
}
