export const RETORNO_CONTATO_VALUES = [
    'Não Contatado',
    'Pendente',
    'Negativo',
    'Positivo',
    'Em Conversa',
    'Sem Oportunidade',
] as const;

export type RetornoContato = (typeof RETORNO_CONTATO_VALUES)[number];

export const RETORNO_CONTATO_DEFAULT: RetornoContato = 'Não Contatado';

export const RETORNO_BADGE_CLASSES: Record<RetornoContato, string> = {
    'Não Contatado':
        'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700',
    Positivo:
        'bg-green-100 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900/50',
    Negativo:
        'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-900/50',
    Pendente:
        'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/50',
    'Em Conversa':
        'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-950/30 dark:text-yellow-400 dark:border-yellow-900/50',
    'Sem Oportunidade':
        'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800/50 dark:text-gray-400 dark:border-gray-700',
};

export type RetornoVariant =
    | 'default'
    | 'secondary'
    | 'destructive'
    | 'outline';

export const RETORNO_VARIANTS: Record<RetornoContato, RetornoVariant> = {
    'Não Contatado': 'outline',
    Pendente: 'secondary',
    Positivo: 'default',
    Negativo: 'destructive',
    'Em Conversa': 'outline',
    'Sem Oportunidade': 'secondary',
};

export function isRetornoContato(value: string): value is RetornoContato {
    return (RETORNO_CONTATO_VALUES as readonly string[]).includes(value);
}

export function getRetornoBadgeClasses(retorno: string): string {
    return isRetornoContato(retorno)
        ? RETORNO_BADGE_CLASSES[retorno]
        : RETORNO_BADGE_CLASSES[RETORNO_CONTATO_DEFAULT];
}

export function getRetornoVariant(retorno: string): RetornoVariant {
    return isRetornoContato(retorno) ? RETORNO_VARIANTS[retorno] : 'secondary';
}
