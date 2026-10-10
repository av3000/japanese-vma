import React, { useId } from 'react';
import { POST_ROUTES, buildPostListSearchParams, type MappedPostDetail } from '@/api/posts/reads';
import { toParagraphs } from '@/components/features/articles/ArticleBody';
import CommentsBlock from '@/components/features/comment/CommentsBlock';
import { PostCues } from '@/components/features/community/PostCues';
import PostLikeButton from '@/components/features/community/PostLikeButton';
import PostOwnerActions from '@/components/features/community/PostOwnerActions';
import { Byline } from '@/components/shared/Byline';
import { DetailActions } from '@/components/shared/DetailActions';
import { DetailFacts } from '@/components/shared/DetailFacts';
import { DetailLayout } from '@/components/shared/DetailLayout';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { Cluster } from '@/components/shared/layout';
import { japaneseLang } from '@/helpers/japaneseLang';
import styles from './PostContent.module.css';

interface PostContentProps {
	post: MappedPostDetail;
}

/** The Community list filtered to one tag, so a tag on a post leads to its neighbours. */
export const postTagHref = (tag: string) => `${POST_ROUTES.list}?${buildPostListSearchParams({ hashtag: tag })}`;

/**
 * A Post in the Reading Room layout: the title and byline above a reading column, a rail with
 * the counts, the actions and the tags, and the comments under both. The mutating controls are
 * their own components, so this file stays a read surface.
 */
const PostContent: React.FC<PostContentProps> = ({ post }) => {
	const tagsHeadingId = useId();
	const commentsHeadingId = useId();
	const paragraphs = toParagraphs(post.content);

	return (
		<DetailLayout
			railLabel="About this post"
			header={
				<div className={styles.header}>
					<Link to={POST_ROUTES.list} className={styles.back}>
						<Icon name="arrowDownSolid" rotate="90" size="sm" /> Community
					</Link>
					<PostCues post={post} />
					<h1 className={styles.title} lang={japaneseLang(post.title)}>
						{post.title}
					</h1>
					<Byline name={post.authorName} date={post.created_at} views={post.engagementCounts.views} />
				</div>
			}
			main={
				<div className={styles.body} lang={japaneseLang(post.content)}>
					{paragraphs.map((paragraph, index) => (
						<p key={index} className={styles.paragraph}>
							{paragraph}
						</p>
					))}
				</div>
			}
			facts={
				<DetailFacts
					title="In this post"
					facts={[
						{ term: 'Likes', value: post.engagementCounts.likes },
						{ term: 'Comments', value: post.engagementCounts.comments },
						{ term: 'Views', value: post.engagementCounts.views },
					]}
				/>
			}
			actions={
				<DetailActions>
					<PostLikeButton
						postId={post.id}
						detailIdentifier={post.uuid}
						likesCount={post.engagementCounts.likes}
						isLiked={post.engagement.is_liked_by_viewer}
					/>
					<PostOwnerActions
						postId={post.id}
						uuid={post.uuid}
						title={post.title}
						authorId={post.author.id}
						isLocked={post.locked}
					/>
				</DetailActions>
			}
			extra={
				post.hashtags.length > 0 ? (
					<section aria-labelledby={tagsHeadingId}>
						<h2 id={tagsHeadingId} className={styles.railHeading}>
							Tags
						</h2>
						<Cluster as="ul" gap="2xs" className={styles.tags}>
							{post.hashtags.map((tag) => (
								<li key={tag.id}>
									<Link to={postTagHref(tag.content)} className={styles.tag}>
										#{tag.content.replace(/^#/, '')}
									</Link>
								</li>
							))}
						</Cluster>
					</section>
				) : null
			}
			after={
				<section aria-labelledby={commentsHeadingId} className={styles.comments}>
					<h2 id={commentsHeadingId} className={styles.sectionHeading}>
						Comments
					</h2>
					<CommentsBlock parent="post" entityId={post.id} entityUuid={post.uuid} isLocked={post.locked} />
				</section>
			}
		/>
	);
};

export default PostContent;
