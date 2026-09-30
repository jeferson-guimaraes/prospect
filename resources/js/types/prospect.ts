import type { CanalContato } from '@/lib/canal-contato';
import type { RetornoContato } from '@/lib/prospect-retorno';

export type ProspectTimeline = {
    id: number;
    prospeccao_id: number;
    observacao: string;
    data_observacao: string;
    created_at: string | null;
    updated_at: string | null;
};

export type Prospect = {
    id: number;
    usuario_id: number;
    nome: string;
    possiveis_dores: string | null;
    oportunidades_identificadas: string | null;
    perguntas_para_descoberta: string | null;
    sugestao_primeiro_contato: string | null;
    lead_score: number | null;
    contato_responsavel: string | null;
    whatsapp: string | null;
    instagram: string | null;
    email: string | null;
    site: string | null;
    data_contato: string | null;
    canal_contato: CanalContato | null;
    data_resposta: string | null;
    retorno: RetornoContato;
    created_at: string | null;
    updated_at: string | null;
    timelines?: ProspectTimeline[];
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    first_page_url: string;
    last_page_url: string;
    prev_page_url: string | null;
    next_page_url: string | null;
    path: string;
    links: PaginationLink[];
};
