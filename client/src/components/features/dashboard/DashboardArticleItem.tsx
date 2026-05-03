import React from 'react';
import { ListGroup } from 'react-bootstrap';
import type { HashtagResource } from '@/api/generated/model';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import ArticleStatus from '../../ui/article-status';

interface DashboardArticleItemProps {
	uuid: string;
	created_at: string;
	title_jp: string;
	status: number;
	commentsTotal: number;
	likesTotal: number;
	viewsTotal: number;
	hashtags?: HashtagResource[];
}

const DashboardArticleItem: React.FC<DashboardArticleItemProps> = ({
	uuid,
	created_at,
	title_jp,
	status,
	commentsTotal,
	likesTotal,
	viewsTotal,
	hashtags = [],
}) => (
	<div className="row">
		<div className="col-md-8 pb-3 mb-0 border-bottom border-gray">
			<p>{title_jp}</p>
			<div className="d-flex align-items-center">
				<span className="mr-2 text-muted">Tags:</span>
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
				<span className="mr-2 text-muted">Status:</span>
				<ArticleStatus status={status} />
			</div>
		</div>
		<div className="col-md-4">
			<ListGroup variant="flush" className="text-muted">
				<ListGroup.Item className="p-0 d-flex justify-content-between align-items-center">
					<span>{commentsTotal} Comments</span>
					<span>{viewsTotal} Views</span>
					<span>{likesTotal} Likes</span>
					<Button to={`/articles/${uuid}`} variant="outline" size="sm" type="button">
						<Icon name="externalLink" size="sm" />
					</Button>
				</ListGroup.Item>
				<ListGroup.Item className="p-0">
					<small>{created_at}</small>
				</ListGroup.Item>
			</ListGroup>
		</div>
	</div>
);

export default DashboardArticleItem;
