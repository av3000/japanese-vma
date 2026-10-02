import type { ChoiceOption } from '@/components/shared/FormControls';

export type Visibility = 'public' | 'private';

/** Article and Catalogue visibility, with what each choice means. Review does not depend on it. */
export const VISIBILITY_OPTIONS: ReadonlyArray<ChoiceOption<Visibility>> = [
	{ value: 'public', label: 'Public', description: 'Anyone can find and read it.' },
	{ value: 'private', label: 'Private', description: 'Only you can see it.' },
];
