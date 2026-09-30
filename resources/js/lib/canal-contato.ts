export const CANAL_CONTATO_VALUES = ['WhatsApp', 'Instagram', 'Email'] as const;

export type CanalContato = (typeof CANAL_CONTATO_VALUES)[number];

export const CANAL_CONTATO_LABELS: Record<CanalContato, string> = {
    WhatsApp: 'WhatsApp',
    Instagram: 'Instagram',
    Email: 'E-mail',
};
