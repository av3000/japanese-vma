import classNames from 'classnames';
import ArticleCardSkeleton from '@/components/shared/ArticleCard/ArticleCardSkeleton';
import skeletonStyles from '@/components/shared/ArticleCard/ArticleCardSkeleton.module.scss';

const ARTICLES_LIST_SKELETON_COUNT = 12;

const SEARCH_PLACEHOLDERS = [
	{ className: 'col-lg-4 col-md-6 col-sm-12 mt-3', width: '100%' },
	{ className: 'col-lg-4 col-md-4 col-sm-12 mt-3', width: '100%' },
	{ className: 'col-lg-2 col-md-2 col-sm-4 mt-3', width: '100%' },
	{ className: 'col-lg-2 mt-3', width: '7rem' },
];

const SearchControlSkeleton = ({ width }: { width: string }) => (
	<span
		className={classNames(skeletonStyles.block, skeletonStyles.line)}
		style={{
			display: 'block',
			height: '38px',
			width,
		}}
	/>
);

const ArticlesListSkeleton = () => (
	<div className="container" data-testid="articles-list-skeleton" aria-hidden="true">
		<div className="u-container">
			<div className="row">
				{SEARCH_PLACEHOLDERS.map((placeholder) => (
					<div key={placeholder.className} className={placeholder.className}>
						<SearchControlSkeleton width={placeholder.width} />
					</div>
				))}
			</div>
		</div>

		<span
			className={classNames('mb-3 mt-3', skeletonStyles.block, skeletonStyles.line)}
			style={{
				display: 'block',
				height: '1rem',
				width: '9rem',
			}}
		/>

		<div className="row">
			{Array.from({ length: ARTICLES_LIST_SKELETON_COUNT }).map((_, index) => (
				<div key={index} className="col-lg-3 col-md-4 col-sm-6 col-6 mb-4">
					<ArticleCardSkeleton />
				</div>
			))}
		</div>
	</div>
);

export default ArticlesListSkeleton;
