import React from 'react';
import { POST_ROUTES, type MappedPostListItem } from '@/api/posts/reads';
import {
	CardDate,
	CardOwner,
	CardStats,
	CardTags,
	CardTitle,
	LibraryCard,
} from '@/components/features/LibraryCards/LibraryCard';
import { formatDate } from '@/helpers/date';
import { japaneseLang } from '@/helpers/japaneseLang';
import { PostCues } from '../PostCues';
import styles from './PostCard.module.css';

export interface PostCardProps {
	post: MappedPostListItem;
	className?: string;
}

/**
 * A Community post as a Library Card with no cover: topic and locked cues, the title (one link
 * over the whole card), author and date, tags, and the engagement counts.
 */
export const PostCard: React.FC<PostCardProps> = ({ post, className }) => (
	<LibraryCard className={className}>
		<PostCues post={post} />
		<CardTitle to={POST_ROUTES.detail(post.uuid)} lang={japaneseLang(post.title)}>
			{post.title}
		</CardTitle>
		<p className={styles.meta}>
			<CardOwner name={post.authorName} />
			<span aria-hidden="true">·</span>
			<CardDate dateTime={post.created_at}>{formatDate(post.created_at)}</CardDate>
		</p>
		<CardTags tags={post.hashtags} />
		<CardStats
			stats={[
				{ kind: 'views', count: post.engagementCounts.views },
				{ kind: 'comments', count: post.engagementCounts.comments },
				{ kind: 'likes', count: post.engagementCounts.likes },
			]}
		/>
	</LibraryCard>
);

export default PostCard;
