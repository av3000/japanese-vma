import React from 'react';
import { POST_ROUTES, type MappedPostDetail } from '@/api/posts/reads';
import AvatarImg from '@/assets/images/avatar-woman.svg';
import CommentsBlock from '@/components/features/comment/CommentsBlock';
import PostLikeButton from '@/components/features/community/PostLikeButton';
import PostOwnerActions from '@/components/features/community/PostOwnerActions';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { Badge } from '@/components/ui/badge';

interface PostContentProps {
	post: MappedPostDetail;
}

/**
 * Read presentation for a single Post. Everything here comes from the v1 detail contract; the
 * mutating controls are separate components so this file stays a pure read surface.
 */
const PostContent: React.FC<PostContentProps> = ({ post }) => {
	return (
		<div className="container pb-5">
			<div className="row justify-content-center">
				<div className="col-lg-8">
					<span className="row mt-4">
						<Link to={POST_ROUTES.list} className="tag-link">
							<Icon name="arrowDownSolid" rotate="90" size="sm" /> Back to Community
						</Link>
					</span>

					<h1 className="mt-4">{post.title}</h1>

					<div className="row text-muted w-100 mb-3 justify-content-between align-items-center">
						<div className="col">
							{post.formattedDate}
							<br />
							<span>{post.engagementCounts.views} views | </span>
							<Badge variant="secondary" className="mr-2">
								{post.topic_label}
							</Badge>
							{post.locked && <Badge variant="secondary">Locked</Badge>}
						</div>

						<PostOwnerActions
							postId={post.id}
							uuid={post.uuid}
							title={post.title}
							authorId={post.author.id}
							isLocked={post.locked}
						/>
					</div>

					<p className="lead mt-5">{post.content}</p>

					<section className="mt-2 d-flex align-items-center flex-wrap">
						{post.hashtags.map((tag) => (
							<Chip className="mr-1 mb-1" readonly key={tag.id} title={tag.content} name={tag.content}>
								{tag.content}
							</Chip>
						))}
					</section>

					<hr className="my-4" />

					<div className="d-flex justify-content-between align-items-center">
						<div className="d-flex align-items-center">
							<img src={AvatarImg} alt="user" width="40" className="rounded-circle" />
							<p className="ml-3 mb-0">
								Posted by <strong>{post.authorName}</strong>
							</p>
						</div>

						<PostLikeButton
							postId={post.id}
							detailIdentifier={post.uuid}
							likesCount={post.engagementCounts.likes}
						/>
					</div>
				</div>
			</div>

			<div className="row justify-content-center mt-5">
				<div className="col-lg-8">
					<CommentsBlock parent="post" entityId={post.id} entityUuid={post.uuid} isLocked={post.locked} />
				</div>
			</div>
		</div>
	);
};

export default PostContent;
