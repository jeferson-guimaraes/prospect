import { Head, Link, router } from '@inertiajs/react';
import {
    Calendar,
    Edit,
    Link as LinkIcon,
    MessageSquare,
    Search,
    SortAsc,
    SortDesc,
    Trash2,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import ProspectDetailsModal from '@/components/prospect-details-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDebounce } from '@/hooks/use-debounce';
import { formatDate } from '@/lib/format';
import { getLeadScoreBadgeClasses, getLeadScoreLabel } from '@/lib/lead-score';
import {
    buildProspectsIndexUrl,
    clearProspectFilters,
    getProspectInitials,
    hasActiveProspectFilters,
    saveProspectFilters,
    type ProspectFilterKey,
    type ProspectFilters,
} from '@/lib/prospect-filters';
import {
    RETORNO_CONTATO_VALUES,
    getRetornoBadgeClasses,
} from '@/lib/prospect-retorno';
import { create, destroy, edit, index } from '@/routes/prospects';
import type { Paginated, Prospect } from '@/types';

type IndexProps = {
    prospects: Paginated<Prospect>;
    filters: ProspectFilters;
};

export default function Index({ prospects, filters }: IndexProps) {
    const skipSearchSyncRef = useRef(false);
    const filtersRef = useRef(filters);
    filtersRef.current = filters;

    const [search, setSearch] = useState(filters.search || '');
    const debouncedSearch = useDebounce(search, 500);

    const sortField = filters.sort_field || 'created_at';
    const sortDirection = filters.sort_direction || 'desc';
    const statusFilter = filters.retorno || 'all';
    const leadScoreFilter = filters.lead_score_tier || 'all';

    const applyFilters = useCallback((newFilters: ProspectFilters) => {
        saveProspectFilters(newFilters);
        router.get(
            buildProspectsIndexUrl(newFilters),
            {},
            { preserveState: true, replace: true },
        );
    }, []);

    const handleFilterChange = useCallback(
        (key: ProspectFilterKey, value: string | number) => {
            const newFilters: ProspectFilters = {
                ...filtersRef.current,
                [key]: value,
            };
            if (value === 'all' && key === 'retorno') {
                delete newFilters.retorno;
            }
            if (value === 'all' && key === 'lead_score_tier') {
                delete newFilters.lead_score_tier;
            }
            if (key !== 'page') {
                delete newFilters.page;
            }
            applyFilters(newFilters);
        },
        [applyFilters],
    );

    useEffect(() => {
        saveProspectFilters(filters);
    }, [filters]);

    useEffect(() => {
        if (skipSearchSyncRef.current) {
            return;
        }

        const serverSearch = filters.search ?? '';

        if (debouncedSearch === serverSearch) {
            return;
        }

        const newFilters: ProspectFilters = {
            ...filtersRef.current,
            search: debouncedSearch || undefined,
        };

        if (!debouncedSearch) {
            delete newFilters.search;
        }

        delete newFilters.page;
        applyFilters(newFilters);
    }, [debouncedSearch, filters.search, applyFilters]);

    const handleDelete = (e: React.MouseEvent, id: number) => {
        e.stopPropagation();
        if (confirm('Tem certeza que deseja excluir este prospect?')) {
            router.delete(destroy.url(id));
        }
    };

    const handleFilterReset = useCallback(() => {
        skipSearchSyncRef.current = true;
        clearProspectFilters();
        setSearch('');

        const releaseSearchSync = () => {
            skipSearchSyncRef.current = false;
        };

        window.setTimeout(releaseSearchSync, 1000);

        router.get(
            index.url({ query: { reset_filters: 1 } }),
            {},
            {
                preserveState: true,
                replace: true,
                onFinish: releaseSearchSync,
                onCancel: releaseSearchSync,
            },
        );
    }, []);

    const hasActiveFilters = hasActiveProspectFilters(filters);

    return (
        <>
            <Head title="Prospecção" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Prospecção
                        </h1>
                        <p className="mt-1 text-muted-foreground">
                            Gerencie seus leads e oportunidades de negócio.
                        </p>
                    </div>
                    <Button asChild className="shrink-0 shadow-sm">
                        <Link href={create()}>Novo Prospect</Link>
                    </Button>
                </div>

                <div className="rounded-xl border border-border/50 bg-muted/40 p-4">
                    <div className="flex flex-col items-stretch gap-4 lg:flex-row lg:items-center">
                        <div className="relative max-w-xl flex-grow">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Buscar por nome, e-mail, site ou contato..."
                                className="h-10 bg-background pl-10"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <div className="flex items-center gap-2">
                                <span className="hidden text-xs font-medium text-muted-foreground sm:inline">
                                    Status:
                                </span>
                                <Select
                                    value={statusFilter}
                                    onValueChange={(value) =>
                                        handleFilterChange('retorno', value)
                                    }
                                >
                                    <SelectTrigger className="h-10 w-[160px] bg-background">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos os Status
                                        </SelectItem>
                                        {RETORNO_CONTATO_VALUES.map(
                                            (retorno) => (
                                                <SelectItem
                                                    key={retorno}
                                                    value={retorno}
                                                >
                                                    {retorno}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-center gap-2">
                                <span className="hidden text-xs font-medium text-muted-foreground sm:inline">
                                    Score:
                                </span>
                                <Select
                                    value={leadScoreFilter}
                                    onValueChange={(value) =>
                                        handleFilterChange(
                                            'lead_score_tier',
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="h-10 w-[160px] bg-background">
                                        <SelectValue placeholder="Lead score" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos os Scores
                                        </SelectItem>
                                        <SelectItem value="high">
                                            Alto (70+)
                                        </SelectItem>
                                        <SelectItem value="medium">
                                            Médio (40-69)
                                        </SelectItem>
                                        <SelectItem value="low">
                                            Baixo (&lt;40)
                                        </SelectItem>
                                        <SelectItem value="none">
                                            Sem score
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-center gap-2">
                                <span className="hidden text-xs font-medium text-muted-foreground sm:inline">
                                    Ordenar:
                                </span>
                                <Select
                                    value={sortField}
                                    onValueChange={(value) =>
                                        handleFilterChange('sort_field', value)
                                    }
                                >
                                    <SelectTrigger className="h-10 w-[160px] bg-background">
                                        <SelectValue placeholder="Ordenar por" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="created_at">
                                            Mais Recentes
                                        </SelectItem>
                                        <SelectItem value="nome">
                                            Nome
                                        </SelectItem>
                                        <SelectItem value="data_contato">
                                            Data de Contato
                                        </SelectItem>
                                        <SelectItem value="retorno">
                                            Status
                                        </SelectItem>
                                        <SelectItem value="canal_contato">
                                            Canal
                                        </SelectItem>
                                        <SelectItem value="lead_score">
                                            Lead Score
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <Select
                                value={sortDirection}
                                onValueChange={(value) =>
                                    handleFilterChange('sort_direction', value)
                                }
                            >
                                <SelectTrigger className="h-10 w-[50px] bg-background px-2 sm:w-[140px] sm:px-3">
                                    <div className="flex items-center gap-2">
                                        {sortDirection === 'asc' ? (
                                            <SortAsc className="h-4 w-4" />
                                        ) : (
                                            <SortDesc className="h-4 w-4" />
                                        )}
                                        <span className="hidden sm:inline">
                                            {sortDirection === 'asc'
                                                ? 'Crescente'
                                                : 'Decrescente'}
                                        </span>
                                    </div>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="asc">
                                        Crescente
                                    </SelectItem>
                                    <SelectItem value="desc">
                                        Decrescente
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="h-10 w-10 rounded-full transition-colors hover:bg-primary/10 hover:text-primary disabled:pointer-events-none disabled:opacity-40"
                                onClick={handleFilterReset}
                                disabled={!hasActiveFilters}
                                title="Limpar filtros"
                            >
                                <X className="h-4 w-4" />
                                <span className="sr-only">Limpar filtros</span>
                            </Button>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {prospects.data.length > 0 ? (
                        prospects.data.map((prospect) => (
                            <Dialog key={prospect.id}>
                                <DialogTrigger asChild>
                                    <Card className="group flex h-full cursor-pointer flex-col overflow-hidden rounded-xl border border-border bg-card shadow-sm transition-all duration-300 hover:border-primary/30 hover:shadow-md">
                                        <CardHeader className="p-5 pb-0">
                                            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div className="flex min-w-0 flex-1 items-start gap-3">
                                                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-primary/20 bg-primary/10 text-base font-bold text-primary">
                                                        {getProspectInitials(
                                                            prospect.nome,
                                                        )}
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <CardTitle className="text-base leading-snug font-bold break-words transition-colors group-hover:text-primary">
                                                            {prospect.nome}
                                                        </CardTitle>
                                                        <CardDescription className="mt-0.5 text-xs break-words">
                                                            {prospect.contato_responsavel ||
                                                                'Sem responsável'}
                                                        </CardDescription>
                                                    </div>
                                                </div>
                                                <div className="flex shrink-0 flex-wrap items-center gap-1 sm:flex-col sm:items-end">
                                                    <Badge
                                                        variant="outline"
                                                        className={`${getRetornoBadgeClasses(prospect.retorno)} rounded-full border px-2 py-0.5 text-[10px] font-bold tracking-tight uppercase`}
                                                    >
                                                        {prospect.retorno}
                                                    </Badge>
                                                    {prospect.lead_score !==
                                                        null && (
                                                        <Badge
                                                            variant="outline"
                                                            className={`px-2 py-0.5 text-[10px] font-bold ${getLeadScoreBadgeClasses(prospect.lead_score)}`}
                                                        >
                                                            {getLeadScoreLabel(
                                                                prospect.lead_score,
                                                            )}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                        </CardHeader>

                                        <CardContent className="flex-grow p-5 pt-4">
                                            <div className="space-y-3 text-sm text-muted-foreground">
                                                {prospect.site && (
                                                    <div className="group/item flex items-center gap-2">
                                                        <div className="rounded-md bg-muted p-1.5 transition-colors group-hover/item:bg-primary/10">
                                                            <LinkIcon className="h-3.5 w-3.5" />
                                                        </div>
                                                        <span className="truncate">
                                                            {prospect.site}
                                                        </span>
                                                    </div>
                                                )}
                                                <div className="group/item flex items-center gap-2">
                                                    <div className="rounded-md bg-muted p-1.5 transition-colors group-hover/item:bg-primary/10">
                                                        <Calendar className="h-3.5 w-3.5" />
                                                    </div>
                                                    <span>
                                                        {formatDate(
                                                            prospect.data_contato,
                                                            'Data não informada',
                                                        )}
                                                    </span>
                                                </div>
                                                <div className="group/item flex items-center gap-2">
                                                    <div className="rounded-md bg-muted p-1.5 transition-colors group-hover/item:bg-primary/10">
                                                        <MessageSquare className="h-3.5 w-3.5" />
                                                    </div>
                                                    <span className="truncate">
                                                        {prospect.canal_contato ||
                                                            'Canal não informado'}
                                                    </span>
                                                </div>
                                            </div>
                                        </CardContent>

                                        <CardFooter className="flex items-center justify-between gap-2 border-t bg-muted/30 px-5 py-4">
                                            <span className="text-[10px] font-bold tracking-wider text-muted-foreground/60 uppercase">
                                                Lead #{prospect.id}
                                            </span>
                                            <div className="flex gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 rounded-full transition-colors hover:bg-primary/10 hover:text-primary"
                                                    asChild
                                                    onClick={(e) =>
                                                        e.stopPropagation()
                                                    }
                                                >
                                                    <Link
                                                        href={edit(prospect.id)}
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                        <span className="sr-only">
                                                            Editar
                                                        </span>
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 rounded-full transition-colors hover:bg-destructive/10 hover:text-destructive"
                                                    onClick={(e) =>
                                                        handleDelete(
                                                            e,
                                                            prospect.id,
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                    <span className="sr-only">
                                                        Excluir
                                                    </span>
                                                </Button>
                                            </div>
                                        </CardFooter>
                                    </Card>
                                </DialogTrigger>
                                <DialogContent className="flex max-h-[92dvh] w-[calc(100%-1rem)] max-w-6xl flex-col gap-0 overflow-hidden p-0 sm:max-h-[90vh] sm:max-w-4xl">
                                    <ProspectDetailsModal prospect={prospect} />
                                </DialogContent>
                            </Dialog>
                        ))
                    ) : (
                        <p className="col-span-full rounded-xl border border-dashed bg-muted/20 py-20 text-center text-muted-foreground">
                            Nenhum prospect encontrado.
                        </p>
                    )}
                </div>

                {prospects.last_page > 1 && (
                    <Pagination>
                        <PaginationContent>
                            {prospects.current_page > 1 && (
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={buildProspectsIndexUrl({
                                            ...filters,
                                            page: prospects.current_page - 1,
                                        })}
                                    />
                                </PaginationItem>
                            )}
                            {Array.from(
                                { length: prospects.last_page },
                                (_, i) => i + 1,
                            ).map((page) => (
                                <PaginationItem key={page}>
                                    <PaginationLink
                                        href={buildProspectsIndexUrl({
                                            ...filters,
                                            page,
                                        })}
                                        isActive={
                                            prospects.current_page === page
                                        }
                                    >
                                        {page}
                                    </PaginationLink>
                                </PaginationItem>
                            ))}
                            {prospects.current_page < prospects.last_page && (
                                <PaginationItem>
                                    <PaginationNext
                                        href={buildProspectsIndexUrl({
                                            ...filters,
                                            page: prospects.current_page + 1,
                                        })}
                                    />
                                </PaginationItem>
                            )}
                        </PaginationContent>
                    </Pagination>
                )}
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        {
            title: 'Prospecção',
            href: index(),
        },
    ],
};
