import { useSearchParams } from 'react-router-dom';
import { type RadicalListFilters, useInfiniteRadicals } from '@/api/radicals/hooks/useInfiniteRadicals';
import RadicalItem from '@/components/features/japanese/radical/RadicalItem';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { PageLoading } from '@/components/shared/PageLoading';
import { Cluster, Container, Stack } from '@/components/shared/layout';
import styles from '../japaneseListPage.module.css';
import SearchBarRadicals from './SearchBarRadicals';

const DEFAULT_PER_PAGE = 10;

const RadicalsList = () => {
	const [searchParams, setSearchParams] = useSearchParams();
	const keyword = searchParams.get('keyword')?.trim() ?? '';
	const filters: RadicalListFilters = { per_page: DEFAULT_PER_PAGE, ...(keyword ? { keyword } : {}) };
	const { radicals, total, isLoading, isFetchingNextPage, hasNextPage, fetchNextPage, isError } = useInfiniteRadicals(
		{
			filters,
		},
	);

	const handleSearch = (nextKeyword: string) => {
		const nextParams = new URLSearchParams();

		if (nextKeyword !== '') {
			nextParams.set('keyword', nextKeyword);
		}

		setSearchParams(nextParams);
	};

	if (isLoading) {
		return <PageLoading family="generic" />;
	}

	const searchTotal = `Results total: '${total}'`;

	return (
		<Container className={styles.page}>
			<Stack gap="2xl">
				<SearchBarRadicals defaultKeyword={keyword} onSearch={handleSearch} />
				<Stack as="section" gap="md" className={styles.results}>
					<Cluster justify="center">
						<h4 className={styles.heading}>{searchTotal}</h4>
					</Cluster>
					{isError && <Alert tone="danger">Unable to load radicals.</Alert>}
					<ul className={styles.list}>
						{radicals.map((radical) => (
							<RadicalItem
								key={radical.uuid}
								entityId={radical.id}
								detailIdentifier={radical.uuid}
								radical={radical.radical}
								strokes={radical.strokes}
								meaning={radical.meaning}
								hiragana={radical.hiragana}
							/>
						))}
					</ul>
					{radicals.length === 0 && !isError && <p>No radicals found.</p>}
				</Stack>
				<Cluster justify="center">
					{hasNextPage ? (
						<Button
							variant="outline"
							className={styles.loadMore}
							onClick={() => void fetchNextPage()}
							disabled={isFetchingNextPage}
						>
							{isFetchingNextPage ? 'Loading...' : 'Load More'}
						</Button>
					) : (
						'no more results...'
					)}
				</Cluster>
			</Stack>
		</Container>
	);
};

export default RadicalsList;
