export class HttpError extends Error {
    constructor(
        public readonly status: number,
        message: string,
        public readonly errors: Record<string, string[]> = {},
    ) {
        super(message);
        this.name = 'HttpError';
    }

    /**
     * Junta todas as mensagens de validação em um único texto.
     */
    get validationMessage(): string {
        return Object.values(this.errors).flat().join(' ');
    }
}

function getCsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Envia JSON para uma rota da própria aplicação, com o token CSRF da sessão,
 * e devolve a resposta decodificada. Respostas de erro viram HttpError.
 */
export async function postJson<TResponse>(
    url: string,
    body: unknown,
    fallbackMessage = 'Não foi possível concluir a requisição.',
): Promise<TResponse> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify(body),
    });

    const payload: unknown = await response.json().catch(() => null);

    if (!response.ok) {
        const data =
            payload && typeof payload === 'object'
                ? (payload as {
                      message?: string;
                      errors?: Record<string, string[]>;
                  })
                : {};

        throw new HttpError(
            response.status,
            data.message || fallbackMessage,
            data.errors ?? {},
        );
    }

    return payload as TResponse;
}
