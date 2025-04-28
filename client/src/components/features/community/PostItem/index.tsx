import React from 'react';
import { Chip } from '@/components/shared/Chip';
import { Link } from '@/components/shared/Link';

interface Hashtag {
	id: string | number;
	content: string;
}

interface PostItemProps {
	id: string | number;
	title: string;
	type: string | number;
	commentsTotal: number;
	likesTotal: number;
	viewsTotal: number;
	hashtags: Hashtag[];
	userName: string;
	postType: string;
	date: string;
	isLocked: boolean;
}

const PostItem: React.FC<PostItemProps> = ({
	id,
	title,
	commentsTotal,
	likesTotal,
	viewsTotal,
	hashtags,
	userName,
	postType,
	date,
	isLocked,
}) => {
	return (
		<div className="row border-bottom border-gray">
			<div className="col-lg-10 col-md-12 col-12-sm pb-3 pt-3">
				<p>
					<strong className="d-block text-gray-dark">{userName}</strong>
				</p>
				<h5>
					<Link to={`/community/${id}`}>{title}</Link>
				</h5>
				Date: {date}
				<br />
				Tags:{' '}
				<section className="mt-2 d-flex align-items-center flex-wrap">
					{hashtags.map((tag) => (
						<Chip
							className="mr-1"
							readonly
							key={tag.id + tag.content}
							title={tag.content}
							name={tag.content}
						>
							{tag.content}
						</Chip>
					))}
				</section>
			</div>
			<div className="col-lg-2 col-12-sm pt-3">
				<small>
					<span>
						<strong className="d-block text-gray-dark">{postType}</strong>
					</span>
					<span>{commentsTotal}&nbsp;Comments</span> <br />
					<span>{viewsTotal}&nbsp;Views</span> <br />
					<span>{likesTotal}&nbsp;Likes &nbsp;</span> <br />
					{isLocked && (
						<span>
							<strong>Locked</strong>
						</span>
					)}
				</small>
			</div>
		</div>
	);
};

export default PostItem;
