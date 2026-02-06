import React from 'react';
import classNames from 'classnames';
import { formatDate } from '@/helpers';
import { Chip } from '../Chip';
import { Link } from '../Link';
import styles from './Card.module.scss';

export interface CardImage {
	url: string;
	alt: string;
	title?: string;
}

export interface CardTag {
	id: string;
	content: string;
}

export interface CardProps {
	title?: string;
	image?: CardImage;
	url?: string;
	date?: string;
	tags?: CardTag[];
	children?: React.ReactNode;
	className?: string;
}

/**
 * Generic Card component that can be used as a base for specialized card types
 */
export const Card: React.FC<CardProps> = ({ title, image, date, tags, url, children, className }) => {
	return (
		<article className={classNames(styles.wrapper, className)}>
			<div>
				{image && (
					<Link className={styles.primaryCardAction} to={url ?? ''} title={title}>
						<div className={styles.img}>
							<img
								src={image.url}
								alt={image.alt}
								title={image.title || image.alt}
								className={styles.image}
							/>
						</div>
					</Link>
				)}

				{date && <div className={classNames(styles.date, 'mt-2')}>{formatDate(date, 'ja', true)}</div>}

				<Link className={styles.primaryCardAction} to={url ?? ''} title={title}>
					{title && <p className={classNames(styles.title)}>{title}</p>}
				</Link>

				{tags && tags.length > 0 && (
					<div className={classNames(styles.chipList, 'mt-2 d-flex align-items-center flex-wrap')}>
						{tags.map((tag) => (
							<Chip className="mr-1" key={tag.id} readonly title={tag.content}>
								{tag.content}
							</Chip>
						))}
					</div>
				)}
			</div>

			<>{children && <div className={styles.childrenWrapper}>{children}</div>}</>

			{/* // TODO: redo styling to have a link only on article header and image
			{url ? (
				<Link className={styles.primaryCardAction} to={url ?? ''} title={title}>
					{renderContent()}
				</Link>
			) : (
				renderContent()
			)} */}
		</article>
	);
};
