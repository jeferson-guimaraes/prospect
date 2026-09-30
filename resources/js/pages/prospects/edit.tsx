import { Head } from '@inertiajs/react';
import ProspectForm from '@/components/prospect-form';
import { edit, index } from '@/routes/prospects';
import type { BreadcrumbItem, Prospect } from '@/types';

type EditProps = {
    prospect: Prospect;
};

export default function Edit({ prospect }: EditProps) {
    return (
        <>
            <Head title={`Editar Prospect - ${prospect.nome}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Editar Prospect</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Atualize os dados de{' '}
                        <span className="font-medium text-foreground">
                            {prospect.nome}
                        </span>
                    </p>
                </div>

                <ProspectForm prospect={prospect} />
            </div>
        </>
    );
}

Edit.layout = ({ prospect }: EditProps): { breadcrumbs: BreadcrumbItem[] } => ({
    breadcrumbs: [
        {
            title: 'Prospecção',
            href: index(),
        },
        {
            title: 'Editar Prospect',
            href: edit(prospect.id),
        },
    ],
});
