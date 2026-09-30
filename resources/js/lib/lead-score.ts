export type LeadScoreTier = 'high' | 'medium' | 'low' | 'none';

export function getLeadScoreTier(
    score: number | null | undefined,
): LeadScoreTier {
    if (score === null || score === undefined) {
        return 'none';
    }

    if (score >= 70) {
        return 'high';
    }

    if (score >= 40) {
        return 'medium';
    }

    return 'low';
}

export function getLeadScoreLabel(score: number | null | undefined): string {
    const tier = getLeadScoreTier(score);

    if (tier === 'none') {
        return 'Sem score';
    }

    return `Score ${score}`;
}

export function getLeadScoreBadgeClasses(
    score: number | null | undefined,
): string {
    const tier = getLeadScoreTier(score);

    const styles: Record<LeadScoreTier, string> = {
        high: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900/50',
        medium: 'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-950/30 dark:text-yellow-400 dark:border-yellow-900/50',
        low: 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-900/50',
        none: 'bg-muted text-muted-foreground border-border',
    };

    return styles[tier];
}

export function extractSuggestedMessage(
    suggestion: string | null | undefined,
): string {
    if (!suggestion?.trim()) {
        return '';
    }

    const match = suggestion.match(
        /Mensagem sugerida:\s*\n([\s\S]*?)(?:\n\nPor que este canal:|$)/,
    );

    return match?.[1]?.trim() ?? '';
}
