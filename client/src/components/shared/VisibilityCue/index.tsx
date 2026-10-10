import * as React from 'react';
import classNames from 'classnames';
import type { PublicityStatus } from '@/api/generated/model/publicityStatus';
import { isPublic } from '@/api/publicity';
import { Icon, type IconName } from '@/components/shared/Icon';
import styles from './VisibilityCue.module.css';

export type Visibility = 'public' | 'private';

export interface VisibilityCueContent {
	visibility: Visibility;
	label: 'Public' | 'Private';
	icon: IconName;
}

const VISIBILITY_CUES: Record<Visibility, VisibilityCueContent> = {
	public: { visibility: 'public', label: 'Public', icon: 'lockOpenSolid' },
	private: { visibility: 'private', label: 'Private', icon: 'lockSolid' },
};

/**
 * The cue's content for the API's `publicity`, read through `isPublic` (#497) so no page branches
 * on the number. Anything not explicitly public reads as private: showing a private item as
 * public is the mistake worth avoiding.
 */
export const visibilityCue = (publicity: PublicityStatus | null | undefined): VisibilityCueContent =>
	publicity != null && isPublic(publicity) ? VISIBILITY_CUES.public : VISIBILITY_CUES.private;

export interface VisibilityCueProps {
	publicity: PublicityStatus | null | undefined;
	className?: string;
}

/** "Public" or "Private" as a word with a lock icon: the meaning is in the text, not a colour. */
export const VisibilityCue: React.FC<VisibilityCueProps> = ({ publicity, className }) => {
	const cue = visibilityCue(publicity);

	return (
		<span className={classNames(styles.cue, className)} data-visibility={cue.visibility}>
			<Icon name={cue.icon} size="sm" />
			{cue.label}
		</span>
	);
};

export default VisibilityCue;
