import {
    Copy,
    Lightbulb,
    Loader2,
    MessageCircleQuestion,
    MessageSquare,
    Sparkles,
    Target,
} from 'lucide-react';
import { useState, type ComponentType } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    extractSuggestedMessage,
    getLeadScoreBadgeClasses,
    getLeadScoreLabel,
} from '@/lib/lead-score';
import type { ProspectDiagnosis } from '@/types';

type ProspectDiagnosisSectionProps = {
    diagnosis: ProspectDiagnosis;
    errors: Partial<Record<keyof ProspectDiagnosis, string>>;
    canGenerate: boolean;
    generating: boolean;
    error: string | null;
    onGenerate: () => void;
};

const PLACEHOLDER =
    "Clique em 'Gerar diagnóstico' para analisar o site e o Instagram.";

function DiagnosisField({
    id,
    label,
    hint,
    icon: Icon,
    value,
    error,
    rows = 12,
    className = 'min-h-[200px]',
}: {
    id: keyof ProspectDiagnosis;
    label: string;
    hint: string;
    icon: ComponentType<{ className?: string }>;
    value: string;
    error?: string;
    rows?: number;
    className?: string;
}) {
    return (
        <div className="space-y-2">
            <Label htmlFor={id} className="flex items-center gap-2">
                <Icon className="size-4 text-primary" />
                {label}
            </Label>
            <p className="text-xs text-muted-foreground">{hint}</p>
            <Textarea
                id={id}
                value={value}
                readOnly
                placeholder={PLACEHOLDER}
                rows={rows}
                className={`resize-none bg-muted/40 ${className}`}
            />
            <InputError message={error} />
        </div>
    );
}

export default function ProspectDiagnosisSection({
    diagnosis,
    errors,
    canGenerate,
    generating,
    error,
    onGenerate,
}: ProspectDiagnosisSectionProps) {
    const [copyFeedback, setCopyFeedback] = useState<string | null>(null);

    const hasDiagnosis =
        diagnosis.possiveis_dores.trim() !== '' ||
        diagnosis.oportunidades_identificadas.trim() !== '' ||
        diagnosis.perguntas_para_descoberta.trim() !== '' ||
        diagnosis.sugestao_primeiro_contato.trim() !== '' ||
        diagnosis.lead_score !== null;

    const suggestedMessage = extractSuggestedMessage(
        diagnosis.sugestao_primeiro_contato,
    );

    const copySuggestedMessage = async () => {
        if (!suggestedMessage) {
            return;
        }

        try {
            await navigator.clipboard.writeText(suggestedMessage);
            setCopyFeedback('Mensagem copiada!');
        } catch {
            setCopyFeedback('Não foi possível copiar a mensagem.');
        }

        window.setTimeout(() => setCopyFeedback(null), 2000);
    };

    return (
        <section className="overflow-hidden rounded-xl border border-primary/20 bg-gradient-to-br from-primary/5 via-card to-card shadow-sm">
            <div className="flex flex-col gap-4 border-b border-primary/10 bg-primary/5 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-start gap-3">
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Sparkles className="size-5" />
                    </div>
                    <div>
                        <h3 className="font-semibold">Diagnóstico com IA</h3>
                        <p className="text-sm text-muted-foreground">
                            Analisa site e Instagram para gerar hipóteses
                            operacionais e perguntas de descoberta.
                        </p>
                    </div>
                </div>
                <Button
                    type="button"
                    onClick={onGenerate}
                    disabled={!canGenerate || generating}
                    className="shrink-0 gap-2"
                >
                    {generating ? (
                        <Loader2 className="size-4 animate-spin" />
                    ) : (
                        <Sparkles className="size-4" />
                    )}
                    {generating
                        ? 'Gerando diagnóstico...'
                        : 'Gerar diagnóstico'}
                </Button>
            </div>

            <div className="space-y-4 p-6">
                {!canGenerate && (
                    <p className="rounded-lg border border-dashed bg-muted/30 px-4 py-3 text-sm text-muted-foreground">
                        Informe o nome e pelo menos o site ou o Instagram para
                        habilitar a geração do diagnóstico.
                    </p>
                )}
                {error && (
                    <p
                        role="alert"
                        className="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
                    >
                        {error}
                    </p>
                )}
                {hasDiagnosis && (
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" className="gap-1">
                            <Sparkles className="size-3" />
                            Diagnóstico gerado
                        </Badge>
                        {diagnosis.lead_score !== null && (
                            <Badge
                                variant="outline"
                                className={getLeadScoreBadgeClasses(
                                    diagnosis.lead_score,
                                )}
                            >
                                {getLeadScoreLabel(diagnosis.lead_score)}
                            </Badge>
                        )}
                    </div>
                )}

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <DiagnosisField
                        id="possiveis_dores"
                        label="Possíveis Dores"
                        hint="Hipóteses operacionais inferidas pela IA"
                        icon={Target}
                        value={diagnosis.possiveis_dores}
                        error={errors.possiveis_dores}
                    />
                    <DiagnosisField
                        id="oportunidades_identificadas"
                        label="Oportunidades"
                        hint="Automação, centralização e soluções recomendadas"
                        icon={Lightbulb}
                        value={diagnosis.oportunidades_identificadas}
                        error={errors.oportunidades_identificadas}
                    />
                    <DiagnosisField
                        id="perguntas_para_descoberta"
                        label="Perguntas para Descoberta"
                        hint="Perguntas para validar as hipóteses"
                        icon={MessageCircleQuestion}
                        value={diagnosis.perguntas_para_descoberta}
                        error={errors.perguntas_para_descoberta}
                    />
                </div>

                <div className="space-y-2">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <Label
                            htmlFor="sugestao_primeiro_contato"
                            className="flex items-center gap-2"
                        >
                            <MessageSquare className="size-4 text-primary" />
                            Sugestão de Primeiro Contato
                        </Label>
                        {suggestedMessage && (
                            <div className="flex items-center gap-2">
                                {copyFeedback && (
                                    <span className="text-xs text-muted-foreground">
                                        {copyFeedback}
                                    </span>
                                )}
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={copySuggestedMessage}
                                    className="gap-1"
                                >
                                    <Copy className="size-3.5" />
                                    Copiar mensagem
                                </Button>
                            </div>
                        )}
                    </div>
                    <p className="text-xs text-muted-foreground">
                        Canal recomendado e mensagem pronta para envio
                    </p>
                    <Textarea
                        id="sugestao_primeiro_contato"
                        value={diagnosis.sugestao_primeiro_contato}
                        readOnly
                        placeholder={PLACEHOLDER}
                        rows={8}
                        className="min-h-[160px] resize-none bg-muted/40"
                    />
                    <InputError message={errors.sugestao_primeiro_contato} />
                </div>
            </div>
        </section>
    );
}
