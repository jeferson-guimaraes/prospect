import { Link, useForm } from '@inertiajs/react';
import {
    Building2,
    CalendarClock,
    Globe,
    Instagram,
    Mail,
    Phone,
    Plus,
    Trash2,
    User,
} from 'lucide-react';
import {
    useState,
    type ComponentProps,
    type ComponentType,
    type ReactNode,
} from 'react';
import InputError from '@/components/input-error';
import ProspectDiagnosisSection from '@/components/prospect-diagnosis-section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    CANAL_CONTATO_LABELS,
    CANAL_CONTATO_VALUES,
} from '@/lib/canal-contato';
import { toDateTimeLocal } from '@/lib/format';
import { HttpError, postJson } from '@/lib/http';
import { maskTelefone } from '@/lib/masks';
import {
    buildProspectsIndexUrl,
    loadProspectFilters,
} from '@/lib/prospect-filters';
import {
    RETORNO_CONTATO_DEFAULT,
    RETORNO_CONTATO_VALUES,
    getRetornoVariant,
} from '@/lib/prospect-retorno';
import { diagnostico, store, update } from '@/routes/prospects';
import type { Prospect, ProspectDiagnosis } from '@/types';

type TimelineFormData = {
    id?: number;
    observacao: string;
    data_observacao: string;
};

type ProspectFormData = ProspectDiagnosis & {
    nome: string;
    contato_responsavel: string;
    whatsapp: string;
    instagram: string;
    email: string;
    site: string;
    data_contato: string;
    canal_contato: string;
    data_resposta: string;
    retorno: string;
    timelines: TimelineFormData[];
};

type ProspectFormProps = {
    prospect?: Prospect;
};

function toLocalDateTime(value: string): string {
    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? '' : toDateTimeLocal(date);
}

function toIsoDateTime(value: string): string {
    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? '' : date.toISOString();
}

function FieldGroup({
    children,
    className = '',
}: {
    children: ReactNode;
    className?: string;
}) {
    return <div className={`space-y-2 ${className}`}>{children}</div>;
}

function IconInput({
    id,
    icon: Icon,
    ...props
}: ComponentProps<typeof Input> & {
    icon: ComponentType<{ className?: string }>;
}) {
    return (
        <div className="relative">
            <Icon className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input id={id} className="pl-10" {...props} />
        </div>
    );
}

export default function ProspectForm({ prospect }: ProspectFormProps) {
    const [generatingDiagnosis, setGeneratingDiagnosis] = useState(false);
    const [diagnosisError, setDiagnosisError] = useState<string | null>(null);

    const { data, setData, submit, transform, processing, errors } =
        useForm<ProspectFormData>(
            prospect ? update.patch(prospect.id) : store(),
            {
                nome: prospect?.nome ?? '',
                possiveis_dores: prospect?.possiveis_dores ?? '',
                oportunidades_identificadas:
                    prospect?.oportunidades_identificadas ?? '',
                perguntas_para_descoberta:
                    prospect?.perguntas_para_descoberta ?? '',
                sugestao_primeiro_contato:
                    prospect?.sugestao_primeiro_contato ?? '',
                lead_score: prospect?.lead_score ?? null,
                contato_responsavel: prospect?.contato_responsavel ?? '',
                whatsapp: prospect?.whatsapp ?? '',
                instagram: prospect?.instagram ?? '',
                email: prospect?.email ?? '',
                site: prospect?.site ?? '',
                data_contato: prospect?.data_contato ?? '',
                canal_contato: prospect?.canal_contato ?? '',
                data_resposta: prospect?.data_resposta ?? '',
                retorno: prospect?.retorno ?? RETORNO_CONTATO_DEFAULT,
                timelines: (prospect?.timelines ?? []).map((timeline) => ({
                    id: timeline.id,
                    observacao: timeline.observacao,
                    data_observacao: toLocalDateTime(timeline.data_observacao),
                })),
            },
        );

    transform((formData) => ({
        ...formData,
        timelines: formData.timelines.map((timeline) => ({
            ...timeline,
            data_observacao: toIsoDateTime(timeline.data_observacao),
        })),
    }));

    const addTimeline = () => {
        setData('timelines', [
            ...data.timelines,
            { observacao: '', data_observacao: toDateTimeLocal() },
        ]);
    };

    const removeTimeline = (index: number) => {
        setData(
            'timelines',
            data.timelines.filter((_, i) => i !== index),
        );
    };

    const updateTimeline = (
        index: number,
        changes: Partial<Omit<TimelineFormData, 'id'>>,
    ) => {
        setData(
            'timelines',
            data.timelines.map((timeline, i) =>
                i === index ? { ...timeline, ...changes } : timeline,
            ),
        );
    };

    const canGenerateDiagnosis =
        data.nome.trim() !== '' &&
        (data.site.trim() !== '' || data.instagram.trim() !== '');

    const generateDiagnosis = async () => {
        setGeneratingDiagnosis(true);
        setDiagnosisError(null);

        try {
            const result = await postJson<ProspectDiagnosis>(
                diagnostico.url(),
                {
                    nome: data.nome,
                    site: data.site,
                    instagram: data.instagram,
                    whatsapp: data.whatsapp,
                    contato_responsavel: data.contato_responsavel,
                    email: data.email,
                    canal_contato: data.canal_contato,
                    timelines: data.timelines,
                    prospect_id: prospect?.id ?? null,
                },
                'Não foi possível gerar o diagnóstico.',
            );

            setData((current) => ({
                ...current,
                possiveis_dores: result.possiveis_dores ?? '',
                oportunidades_identificadas:
                    result.oportunidades_identificadas ?? '',
                perguntas_para_descoberta:
                    result.perguntas_para_descoberta ?? '',
                sugestao_primeiro_contato:
                    result.sugestao_primeiro_contato ?? '',
                lead_score: result.lead_score ?? null,
            }));
        } catch (error) {
            setDiagnosisError(
                error instanceof HttpError
                    ? error.validationMessage || error.message
                    : 'Não foi possível gerar o diagnóstico.',
            );
        } finally {
            setGeneratingDiagnosis(false);
        }
    };

    const cancelHref = buildProspectsIndexUrl(loadProspectFilters());

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                submit();
            }}
            className="space-y-8"
        >
            <fieldset className="space-y-6 rounded-xl border bg-card p-6 shadow-sm">
                <legend className="flex items-center gap-2 px-2 text-lg font-medium text-primary">
                    <Building2 className="size-5" />
                    Identificação do Prospect
                </legend>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FieldGroup className="md:col-span-2">
                        <Label htmlFor="nome">Nome do Prospect</Label>
                        <IconInput
                            id="nome"
                            icon={Building2}
                            value={data.nome}
                            onChange={(e) => setData('nome', e.target.value)}
                            placeholder="Ex: Empresa X"
                            required
                        />
                        <InputError message={errors.nome} />
                    </FieldGroup>
                    <FieldGroup className="md:col-span-2">
                        <Label htmlFor="contato_responsavel">
                            Contato Responsável
                        </Label>
                        <IconInput
                            id="contato_responsavel"
                            icon={User}
                            value={data.contato_responsavel}
                            onChange={(e) =>
                                setData('contato_responsavel', e.target.value)
                            }
                            placeholder="Nome da pessoa responsável"
                        />
                        <InputError message={errors.contato_responsavel} />
                    </FieldGroup>
                </div>
            </fieldset>

            <fieldset className="space-y-6 rounded-xl border bg-card p-6 shadow-sm">
                <legend className="flex items-center gap-2 px-2 text-lg font-medium text-primary">
                    <Phone className="size-5" />
                    Canais de Contato
                </legend>
                <p className="text-sm text-muted-foreground">
                    Informe ao menos um canal. Site e Instagram alimentam o
                    diagnóstico com IA.
                </p>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FieldGroup>
                        <Label htmlFor="whatsapp">WhatsApp</Label>
                        <IconInput
                            id="whatsapp"
                            icon={Phone}
                            value={data.whatsapp}
                            onChange={(e) =>
                                setData(
                                    'whatsapp',
                                    maskTelefone(e.target.value),
                                )
                            }
                            placeholder="(00) 00000-0000"
                        />
                        <InputError message={errors.whatsapp} />
                    </FieldGroup>
                    <FieldGroup>
                        <Label htmlFor="instagram">Instagram</Label>
                        <IconInput
                            id="instagram"
                            icon={Instagram}
                            value={data.instagram}
                            onChange={(e) =>
                                setData('instagram', e.target.value)
                            }
                            placeholder="@perfil"
                        />
                        <InputError message={errors.instagram} />
                    </FieldGroup>
                    <FieldGroup>
                        <Label htmlFor="email">E-mail</Label>
                        <IconInput
                            id="email"
                            icon={Mail}
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="email@empresa.com"
                        />
                        <InputError message={errors.email} />
                    </FieldGroup>
                    <FieldGroup>
                        <Label htmlFor="site">Site</Label>
                        <IconInput
                            id="site"
                            icon={Globe}
                            value={data.site}
                            onChange={(e) => setData('site', e.target.value)}
                            placeholder="www.empresa.com.br"
                        />
                        <InputError message={errors.site} />
                    </FieldGroup>
                </div>
            </fieldset>

            <ProspectDiagnosisSection
                diagnosis={data}
                errors={errors}
                canGenerate={canGenerateDiagnosis}
                generating={generatingDiagnosis}
                error={diagnosisError}
                onGenerate={generateDiagnosis}
            />

            <fieldset className="space-y-6 rounded-xl border bg-card p-6 shadow-sm">
                <legend className="flex items-center gap-2 px-2 text-lg font-medium text-primary">
                    <CalendarClock className="size-5" />
                    Acompanhamento
                </legend>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FieldGroup>
                        <Label htmlFor="data_contato">Data de Contato</Label>
                        <Input
                            id="data_contato"
                            type="date"
                            value={data.data_contato}
                            onChange={(e) =>
                                setData('data_contato', e.target.value)
                            }
                        />
                        <InputError message={errors.data_contato} />
                    </FieldGroup>
                    <FieldGroup>
                        <Label htmlFor="data_resposta">Data de Resposta</Label>
                        <Input
                            id="data_resposta"
                            type="date"
                            value={data.data_resposta}
                            onChange={(e) =>
                                setData('data_resposta', e.target.value)
                            }
                        />
                        <InputError message={errors.data_resposta} />
                    </FieldGroup>
                    <FieldGroup>
                        <Label htmlFor="canal_contato">Canal de Contato</Label>
                        <Select
                            value={data.canal_contato || undefined}
                            onValueChange={(value) =>
                                setData('canal_contato', value)
                            }
                        >
                            <SelectTrigger id="canal_contato">
                                <SelectValue placeholder="Selecione o canal" />
                            </SelectTrigger>
                            <SelectContent>
                                {CANAL_CONTATO_VALUES.map((canal) => (
                                    <SelectItem key={canal} value={canal}>
                                        {CANAL_CONTATO_LABELS[canal]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.canal_contato} />
                    </FieldGroup>
                    <FieldGroup>
                        <div className="flex items-center justify-between gap-2">
                            <Label htmlFor="retorno">Retorno</Label>
                            <Badge variant={getRetornoVariant(data.retorno)}>
                                {data.retorno}
                            </Badge>
                        </div>
                        <Select
                            value={data.retorno}
                            onValueChange={(value) => setData('retorno', value)}
                        >
                            <SelectTrigger id="retorno">
                                <SelectValue placeholder="Selecione o status" />
                            </SelectTrigger>
                            <SelectContent>
                                {RETORNO_CONTATO_VALUES.map((retorno) => (
                                    <SelectItem key={retorno} value={retorno}>
                                        {retorno}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.retorno} />
                    </FieldGroup>
                </div>
            </fieldset>

            <fieldset className="space-y-6 rounded-xl border bg-card p-6 shadow-sm">
                <legend className="sr-only">Timeline de Observações</legend>
                <div className="flex items-center justify-between gap-4">
                    <div className="flex items-center gap-2 text-lg font-medium text-primary">
                        <CalendarClock className="size-5" />
                        Timeline de Observações
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={addTimeline}
                        className="gap-1"
                    >
                        <Plus className="size-4" />
                        Adicionar
                    </Button>
                </div>

                <div className="space-y-4">
                    {data.timelines.map((timeline, index) => (
                        <div
                            key={timeline.id ?? `nova-${index}`}
                            className="rounded-lg border bg-muted/20 p-4"
                        >
                            <div className="mb-3 flex items-center justify-between">
                                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Observação #{index + 1}
                                </span>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => removeTimeline(index)}
                                    className="size-8 text-destructive hover:bg-destructive/10"
                                >
                                    <Trash2 className="size-4" />
                                    <span className="sr-only">
                                        Remover observação
                                    </span>
                                </Button>
                            </div>
                            <div className="space-y-3">
                                <Input
                                    type="datetime-local"
                                    aria-label="Data da observação"
                                    value={timeline.data_observacao}
                                    onChange={(e) =>
                                        updateTimeline(index, {
                                            data_observacao: e.target.value,
                                        })
                                    }
                                />
                                <Textarea
                                    aria-label="Observação"
                                    value={timeline.observacao}
                                    onChange={(e) =>
                                        updateTimeline(index, {
                                            observacao: e.target.value,
                                        })
                                    }
                                    placeholder="Descreva o contato ou observação"
                                    rows={3}
                                />
                                <InputError
                                    message={
                                        errors[
                                            `timelines.${index}.observacao`
                                        ] ||
                                        errors[
                                            `timelines.${index}.data_observacao`
                                        ] ||
                                        errors[`timelines.${index}.id`]
                                    }
                                />
                            </div>
                        </div>
                    ))}

                    {data.timelines.length === 0 && (
                        <div className="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed py-10 text-center">
                            <CalendarClock className="size-8 text-muted-foreground/50" />
                            <p className="text-sm text-muted-foreground">
                                Nenhuma observação registrada na timeline.
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addTimeline}
                                className="mt-2 gap-1"
                            >
                                <Plus className="size-4" />
                                Adicionar primeira observação
                            </Button>
                        </div>
                    )}
                </div>
                <InputError message={errors.timelines} />
            </fieldset>

            <div className="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:justify-end">
                <Button type="button" variant="outline" asChild>
                    <Link href={cancelHref}>Cancelar</Link>
                </Button>
                <Button
                    type="submit"
                    disabled={processing}
                    className="min-w-32"
                >
                    {processing
                        ? 'Salvando...'
                        : prospect
                          ? 'Atualizar Prospect'
                          : 'Cadastrar Prospect'}
                </Button>
            </div>
        </form>
    );
}
