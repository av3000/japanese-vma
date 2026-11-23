import * as React from 'react';
import classNames from 'classnames';
import { POST_TOPIC_LABELS, isPostTopic } from '@/api/posts/reads';
import { StatusPill, type StatusPillProps } from '@/components/shared/StatusPill';
import { Cluster } from '@/components/shared/layout';
import styles from './PostCues.module.css';

/**
 * The topic's label. The backend sends the canonical `topic_label` on every Post; the local
 * vocabulary covers a missing label so the cue never renders empty.
 */
export const postTopicLabel = (post: { topic: number; topic_label?: string | null }): string =>
	post.topic_label || (isPostTopic(post.topic) ? POST_TOPIC_LABELS[post.topic] : 'Other');

/** A locked Post: neutral, because locking is a state and not an error, and readable as text. */
export const LOCKED_POST_PILL = {
	tone: 'neutral',
	label: 'Locked',
	icon: 'lockSolid',
} as const satisfies Pick<StatusPillProps, 'tone' | 'label' | 'icon'>;

/** The topic as a neutral text tag. "Topic:" is read out, so the tag means something on its own. */
export const PostTopicTag: React.FC<{ label: string; className?: string }> = ({ label, className }) => (
	<span className={classNames(styles.topic, className)}>
		<span className={styles.visuallyHidden}>Topic: </span>
		{label}
	</span>
);

export interface PostCuesProps {
	post: { topic: number; topic_label?: string | null; locked: boolean };
	className?: string;
}

/** The topic tag and, when the Post is locked, the Locked pill. Both carry visible text. */
export const PostCues: React.FC<PostCuesProps> = ({ post, className }) => (
	<Cluster gap="2xs" className={className}>
		<PostTopicTag label={postTopicLabel(post)} />
		{post.locked ? <StatusPill {...LOCKED_POST_PILL} /> : null}
	</Cluster>
);

export default PostCues;
