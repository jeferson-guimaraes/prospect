import { Link } from '@inertiajs/react';
import {
    CalendarClock,
    Copy,
    Edit,
    ExternalLink,
    Instagram,
    Lightbulb,
    Mail,
    MessageCircleQuestion,
    MessageSquare,
    Phone,
    Sparkles,
    Target,
    User,
} from 'lucide-react';
import { useState, type ComponentType, type ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDate, formatDateTime } from '@/lib/format';
import {
    extractSuggestedMessage,
    getLeadScoreBadgeClasses,
    getLeadScoreLabel,
} from '@/lib/lead-score';
import { getProspectInitials } from '@/lib/prospect-filters';
import { getRetornoBadgeClasses } from '@/lib/prospect-retorno';
import { edit } from '@/routes/prospects';
import type { Prospect } from '@/types';

type ProspectDetailsModalProps = {
    prospect: Prospect;
};

function formatExternalUrl(value: string): string {
    if (/^https?:\/\//i.test(value)) {
        return value;
    }

    return `https://${value.replace(/^\/\//, '')}`;
}

function formatWhatsAppUrl(value: string): string {
    const digits = value.replace(/\D/g, '');

    if (!digits) {
        return '#';
    }

    const normalized = digits.startsWith('55') ? digits : `55${digits}`;

    return `https://wa.me/${normalized}`;
}

function formatInstagramUrl(value: string): string {
    if (/^https?:\/\//i.test(value)) {
        return value;
    }

    const handle = value.replace(/^@/, '');

    return `https://instagram.com/${handle}`;
}

function Section({
    title,
    icon: Icon,
    children,
    className = '',
}: {
    title: string;
    icon?: ComponentType<{ className?: string }>;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section
            className={`rounded-xl border bg-card p-4 sm:p-5 ${className}`}
        >
            <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold tracking-wide text-foreground uppercase">
                {Icon && <Icon className="size-4 shrink-0 text-primary" />}
                {title}
            </h3>
            {children}
        </section>
    );
}

function InfoRow({
    icon: Icon,
    label,
    value,
    href,
}: {
    icon: ComponentType<{ className?: string }>;
    label: string;
    value: string;
    href?: string;
}) {
    const content = (
        <>
            <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted">
                <Icon className="size-4 text-muted-foreground" />
            </div>
            <div className="min-w-0 flex-1">
                <p className="text-xs font-medium text-muted-foreground">
                    {label}
                </p>
                <p
                    className={`truncate text-sm font-medium ${href ? 'text-primary' : 'text-foreground'}`}
                >
                    {value}
                </p>
            </div>
            {href && (
                <ExternalLink className="size-3.5 shrink-0 text-muted-foreground" />
            )}
        </>
    );

    if (href) {
        return (
            <a
                href={href}
                target="_blank"
                rel="noreferrer"
                className="flex items-center gap-3 rounded-lg border border-transparent p-2 transition-colors hover:border-primary/20 hover:bg-primary/5"
            >
                {content}
            </a>
        );
    }

    return (
        <div className="flex items-center gap-3 rounded-lg p-2">{content}</div>
    );
}

function DiagnosisBlock({
    title,
    icon: Icon,
    content,
    className = '',
}: {
    title: string;
    icon: ComponentType<{ className?: string }>;
    content: string;
    className?: string;
}) {
    return (
        <div className={`rounded-lg border bg-muted/20 p-4 ${className}`}>
            <h4 className="mb-3 flex items-center gap-2 text-sm font-semibold">
                <Icon className="size-4 shrink-0 text-primary" />
                {title}
            </h4>
            <p className="max-h-48 overflow-y-auto text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground">
                {content}
            </p>
        </div>
    );
}

function StatCard({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border bg-muted/30 px-3 py-2.5">
            <p className="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p className="mt-1 truncate text-sm font-medium">{value}</p>
        </div>
    );
}

export default function ProspectDetailsModal({
    prospect,
}: ProspectDetailsModalProps) {
    const [copyFeedback, setCopyFeedback] = useState<string | null>(null);

    const suggestedMessage = extractSuggestedMessage(
        prospect.sugestao_primeiro_contato,
    );

    const hasDiagnosis = Boolean(
        prospect.possiveis_dores ||
        prospect.oportunidades_identificadas ||
        prospect.perguntas_para_descoberta ||
        prospect.sugestao_primeiro_contato,
    );

    const hasContactInfo = Boolean(
        prospect.contato_responsavel ||
        prospect.email ||
        prospect.whatsapp ||
        prospect.instagram ||
        prospect.site,
    );

    const timelines = prospect.timelines ?? [];

    const copySuggestedMessage = async () => {
        if (!suggestedMessage) {
            return;
        }

        try {
            await navigator.clipboard.writeText(suggestedMessage);
            setCopyFeedback('Copiado!');
        } catch {
            setCopyFeedback('Erro ao copiar');
        }

        window.setTimeout(() => setCopyFeedback(null), 2000);
    };

    return (
        <div className="flex min-h-0 flex-1 flex-col">
            <div className="shrink-0 border-b px-4 py-4 pr-14 sm:px-6 sm:pr-16">
                <DialogHeader className="space-y-4 text-left">
                    <div className="flex items-start gap-3 sm:gap-4">
                        <div className="flex size-12 shrink-0 items-center justify-center rounded-full border border-primary/20 bg-primary/10 text-sm font-bold text-primary sm:size-14 sm:text-base">
                            {getProspectInitials(prospect.nome)}
                        </div>
                        <div className="min-w-0 flex-1">
                            <DialogTitle className="text-left text-xl leading-tight font-bold break-words sm:text-2xl">
                                {prospect.nome}
                            </DialogTitle>
                            <DialogDescription className="mt-1 text-left">
                                {prospect.contato_responsavel ? (
                                    <span className="inline-flex items-center gap-1.5">
                                        <User className="size-3.5 shrink-0" />
                                        {prospect.contato_responsavel}
                                    </span>
                                ) : (
                                    'Sem responsável informado'
                                )}
                            </DialogDescription>
                            <p className="mt-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Lead #{prospect.id}
                            </p>
                            <div className="mt-3 flex flex-wrap items-center gap-2">
                                <Badge
                                    variant="outline"
                                    className={`border text-[10px] font-bold tracking-tight uppercase ${getRetornoBadgeClasses(prospect.retorno)}`}
                                >
                                    {prospect.retorno}
                                </Badge>
                                {prospect.lead_score !== null && (
                                    <Badge
                                        variant="outline"
                                        className={getLeadScoreBadgeClasses(
                                            prospect.lead_score,
                                        )}
                                    >
                                        {getLeadScoreLabel(prospect.lead_score)}
                                    </Badge>
                                )}
                            </div>
                        </div>
                    </div>
                </DialogHeader>
            </div>

            <div className="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4 sm:space-y-5 sm:px-6 sm:py-5">
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3">
                    <StatCard
                        label="Canal"
                        value={prospect.canal_contato ?? '—'}
                    />
                    <StatCard
                        label="Contato"
                        value={formatDate(prospect.data_contato)}
                    />
                    <StatCard
                        label="Resposta"
                        value={formatDate(prospect.data_resposta)}
                    />
                    <StatCard
                        label="Score"
                        value={
                            prospect.lead_score !== null
                                ? String(prospect.lead_score)
                                : '—'
                        }
                    />
                </div>

                <div className="flex flex-col gap-4">
                    <Section title="Canais de Contato" icon={Phone}>
                        {hasContactInfo ? (
                            <div className="grid gap-1">
                                {prospect.contato_responsavel && (
                                    <InfoRow
                                        icon={User}
                                        label="Responsável"
                                        value={prospect.contato_responsavel}
                                    />
                                )}
                                {prospect.whatsapp && (
                                    <InfoRow
                                        icon={MessageSquare}
                                        label="WhatsApp"
                                        value={prospect.whatsapp}
                                        href={formatWhatsAppUrl(
                                            prospect.whatsapp,
                                        )}
                                    />
                                )}
                                {prospect.email && (
                                    <InfoRow
                                        icon={Mail}
                                        label="E-mail"
                                        value={prospect.email}
                                        href={`mailto:${prospect.email}`}
                                    />
                                )}
                                {prospect.instagram && (
                                    <InfoRow
                                        icon={Instagram}
                                        label="Instagram"
                                        value={prospect.instagram}
                                        href={formatInstagramUrl(
                                            prospect.instagram,
                                        )}
                                    />
                                )}
                                {prospect.site && (
                                    <InfoRow
                                        icon={ExternalLink}
                                        label="Site"
                                        value={prospect.site}
                                        href={formatExternalUrl(prospect.site)}
                                    />
                                )}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Nenhum canal de contato informado.
                            </p>
                        )}
                    </Section>

                    <Section title="Linha do Tempo" icon={CalendarClock}>
                        {timelines.length > 0 ? (
                            <div className="space-y-3">
                                {timelines.map((timeline) => (
                                    <div
                                        key={timeline.id}
                                        className="relative border-l-2 border-primary/40 pl-4"
                                    >
                                        <span className="absolute top-1.5 -left-[5px] size-2 rounded-full bg-primary" />
                                        <p className="text-xs font-medium text-muted-foreground">
                                            {formatDateTime(
                                                timeline.data_observacao,
                                            )}
                                        </p>
                                        <p className="mt-1 text-sm leading-relaxed">
                                            {timeline.observacao}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Nenhuma observação registrada.
                            </p>
                        )}
                    </Section>
                </div>

                {hasDiagnosis ? (
                    <Section
                        title="Diagnóstico com IA"
                        icon={Sparkles}
                        className="border-primary/20 bg-gradient-to-br from-primary/5 via-card to-card"
                    >
                        <div className="grid gap-4 lg:grid-cols-2">
                            {prospect.possiveis_dores && (
                                <DiagnosisBlock
                                    title="Possíveis Dores"
                                    icon={Target}
                                    content={prospect.possiveis_dores}
                                />
                            )}
                            {prospect.oportunidades_identificadas && (
                                <DiagnosisBlock
                                    title="Oportunidades"
                                    icon={Lightbulb}
                                    content={
                                        prospect.oportunidades_identificadas
                                    }
                                />
                            )}
                            {prospect.perguntas_para_descoberta && (
                                <DiagnosisBlock
                                    title="Perguntas para Descoberta"
                                    icon={MessageCircleQuestion}
                                    content={prospect.perguntas_para_descoberta}
                                    className="lg:col-span-2"
                                />
                            )}
                            {prospect.sugestao_primeiro_contato && (
                                <div className="rounded-lg border border-primary/20 bg-primary/5 p-4 lg:col-span-2">
                                    <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <h4 className="flex items-center gap-2 text-sm font-semibold">
                                            <MessageSquare className="size-4 shrink-0 text-primary" />
                                            Sugestão de Primeiro Contato
                                        </h4>
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
                                                    onClick={
                                                        copySuggestedMessage
                                                    }
                                                    className="w-full gap-1.5 sm:w-auto"
                                                >
                                                    <Copy className="size-3.5" />
                                                    Copiar mensagem
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                    <p className="max-h-56 overflow-y-auto text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground">
                                        {prospect.sugestao_primeiro_contato}
                                    </p>
                                </div>
                            )}
                        </div>
                    </Section>
                ) : (
                    <Section title="Diagnóstico com IA" icon={Sparkles}>
                        <p className="text-sm text-muted-foreground">
                            Nenhum diagnóstico gerado ainda.
                        </p>
                    </Section>
                )}
            </div>

            <div className="shrink-0 border-t bg-muted/20 px-4 py-3 sm:px-6">
                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Button
                        variant="outline"
                        asChild
                        className="w-full sm:w-auto"
                    >
                        <Link href={edit(prospect.id)}>
                            <Edit className="size-4" />
                            Editar prospect
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    );
}
