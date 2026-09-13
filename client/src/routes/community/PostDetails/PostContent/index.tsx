import React from 'react';
import { POST_ROUTES, type MappedPostDetail } from '@/api/posts/reads';
import AvatarImg from '@/assets/images/avatar-woman.svg';
import CommentsBlock from '@/components/features/comment/CommentsBlock';
import PostLikeButton from '@/components/features/community/PostLikeButton';
import PostOwnerActions from '@/components/features/community/PostOwnerActions';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { Cluster, Container, Stack } from '@/components/shared/layout';
import { Badge } from '@/components/ui/badge';
import { ObjectTemplateType } from '@/shared/constants/enums';
import styles from './PostContent.module.css';

interface PostContentProps {
	post: MappedPostDetail;
}

/**
 * Read presentation for a single Post. Everything here comes from the v1 detail contract; the
 * mutating controls are separate components so this file stays a pure read surface.
 */
const PostContent: React.FC<PostContentProps> = ({ post }) => {
	return (
		<Container size="sm" as="article" className={styles.page}>
			<Stack gap="lg">
				<div>
					<Link to={POST_ROUTES.list} className="tag-link">
						<Icon name="arrowDownSolid" rotate="90" size="sm" /> Back to Community
					</Link>
				</div>

				<h1 className={styles.title}>{post.title}</h1>

				<Cluster justify="between" className={styles.meta}>
					<div>
						{post.formattedDate}
						<br />
						<Cluster as="span" gap="xs">
							<span>{post.engagementCounts.views} views | </span>
							<Badge variant="secondary">{post.topic_label}</Badge>
							{post.locked && <Badge variant="secondary">Locked</Badge>}
						</Cluster>
					</div>

					<PostOwnerActions
						postId={post.id}
						uuid={post.uuid}
						title={post.title}
						authorId={post.author.id}
						isLocked={post.locked}
					/>
				</Cluster>

				<p className={styles.body}>{post.content}</p>

				<Cluster gap="2xs">
					{post.hashtags.map((tag) => (
						<Chip readonly key={tag.id} title={tag.content} name={tag.content}>
							{tag.content}
						</Chip>
					))}
				</Cluster>

				<hr className={styles.divider} />

				<Cluster justify="between">
					<Cluster gap="md">
						<img src={AvatarImg} alt="user" width="40" className={styles.avatar} />
						<p className={styles.author}>
							Posted by <strong>{post.authorName}</strong>
						</p>
					</Cluster>

					<PostLikeButton
						postId={post.id}
						detailIdentifier={post.uuid}
						likesCount={post.engagementCounts.likes}
					/>
				</Cluster>
			</Stack>

			<section className={styles.comments}>
				<CommentsBlock
					readObjectType="post"
					readObjectUuid={post.uuid}
					entityId={post.id}
					entityType={ObjectTemplateType.POST}
					entityUuid={post.uuid}
					isLocked={post.locked}
				/>
			</section>
		</Container>
	);
};

export default PostContent;
