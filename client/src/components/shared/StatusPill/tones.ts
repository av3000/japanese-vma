import type { IconName } from '@/components/shared/Icon';

export const STATUS_TONES = ['neutral', 'success', 'warning', 'info', 'danger'] as const;
export type StatusTone = (typeof STATUS_TONES)[number];

/** An icon from the shared set, or `spinner` for work that is still running. */
export type StatusPillIcon = IconName | 'spinner';
