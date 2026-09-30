import { Head } from '@inertiajs/react';
import ProspectForm from '@/components/prospect-form';
import { create, index } from '@/routes/prospects';

export default function Create() {
    return (
        <>
            <Head title="Novo Prospect" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Novo Prospect</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Cadastre um cliente potencial e acompanhe o contato.
                    </p>
                </div>

                <ProspectForm />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        {
            title: 'Prospecção',
            href: index(),
        },
        {
            title: 'Novo Prospect',
            href: create(),
        },
    ],
};
