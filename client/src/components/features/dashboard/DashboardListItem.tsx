import React from 'react';
import { ListGroup } from 'react-bootstrap';
import type { CatalogueHashtag } from '@/api/catalogues/catalogues';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';

interface DashboardListItemProps {
	id: number;
	created_at: string;
	title: string;
	commentsTotal: number;
	likesTotal: number;
	viewsTotal: number;
	hashtags?: CatalogueHashtag[];
	typeTitle: string;
}

const DashboardListItem: React.FC<DashboardListItemProps> = ({
	id,
	created_at,
	title,
	commentsTotal,
	likesTotal,
	viewsTotal,
	hashtags = [],
	typeTitle,
}) => (
	<div className="row border-bottom border-gray">
		<div className="col-md-8 ">
			<p className="text-muted">{title}</p>
			<div className="d-flex align-items-center mt-3">
				<span className="text-muted">Tags:</span>
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
		</div>
		<div className="col-md-4">
			<ListGroup variant="flush" className="text-muted">
				<ListGroup.Item className="p-0 d-flex justify-content-between align-items-center">
					<span>{commentsTotal} Comments</span>
					<span>{viewsTotal} Views</span>
					<span>{likesTotal} Likes</span>
					<Button to={`/list/${id}`} variant="ghost" size="sm" type="button">
						<Icon name="externalLink" size="sm" />
					</Button>
				</ListGroup.Item>
				<small>ListType: {typeTitle}</small>
				<ListGroup.Item className="p-0">
					<small>{created_at}</small>
				</ListGroup.Item>
			</ListGroup>
		</div>
	</div>
);

export default DashboardListItem;
