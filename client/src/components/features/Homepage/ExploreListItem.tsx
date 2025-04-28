// @ts-nocheck
/* eslint-disable */
import React from 'react';
import { Link } from 'react-router-dom';
import DefaultArticleImg from '@/assets/images/smartphone-screen-with-art-photo-gallery-application-3850271-mid.jpg';
import { Chip } from '@/components/shared/Chip';
import '../article/ArticleItem/ArticleItem.css';

const ExploreListItem = ({
	id,
	created_at,
	title,
	listType,
	type,
	commentsTotal,
	viewsTotal,
	likesTotal,
	downloadsTotal,
	itemsTotal,
	hashtags,
	n1,
	n2,
	n3,
	n4,
	n5,
	uncommon,
}) => (
	<div className="col-lg-4 col-md-6 col-sm-8">
		<div className="card mb-4 shadow-sm">
			<Link to={'/list/' + id}>
				<img
					src={DefaultArticleImg}
					alt="article-logo"
					height="320"
					width="100%"
					className="timelines-image hover"
				/>
			</Link>
			<div className="card-body">
				<Link to={'/list/' + id} className="article-title-link">
					<h4 className="card-text article-title"> {title}</h4>
				</Link>
				<br />
				<strong>{listType}</strong>
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
				<p className="text-muted">{created_at}</p>
				<p className="text-muted">
					{viewsTotal} views &nbsp;
					{commentsTotal} comments&nbsp;
					{likesTotal} likes &nbsp;
					<br />
					{itemsTotal} items &nbsp;
				</p>
				{type !== 9 ? (
					<React.Fragment>
						<hr />
						<div className="d-flex justify-content-between align-items-center text-muted">
							<ruby className="h4 mr-2">
								{n1}
								<rp>(</rp>
								<rt>N1</rt>
								<rp>)</rp>
							</ruby>
							<ruby className="h4 mr-2">
								{n2}
								<rp>(</rp>
								<rt>N2</rt>
								<rp>)</rp>
							</ruby>
							<ruby className="h4 mr-2">
								{n3}
								<rp>(</rp>
								<rt>N3</rt>
								<rp>)</rp>
							</ruby>
							<ruby className="h4 mr-2">
								{n4}
								<rp>(</rp>
								<rt>N4</rt>
								<rp>)</rp>
							</ruby>
							<ruby className="h4 mr-2">
								{n5}
								<rp>(</rp>
								<rt>N5</rt>
								<rp>)</rp>
							</ruby>
							<ruby className="h4 mr-2">
								{uncommon}
								<rp>(</rp>
								<rt>NA</rt>
								<rp>)</rp>
							</ruby>
						</div>
					</React.Fragment>
				) : (
					''
				)}
			</div>
		</div>
	</div>
);

export default ExploreListItem;
