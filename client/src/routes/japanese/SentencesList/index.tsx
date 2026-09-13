import { useSearchParams } from 'react-router-dom';
import { useInfiniteSentences } from '@/api/sentences/hooks/useInfiniteSentences';
import SentenceItem from '@/components/features/japanese/sentence/SentenceItem';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Link } from '@/components/shared/Link';
import { PageLoading } from '@/components/shared/PageLoading';
import { Cluster, Container, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import styles from '../japaneseListPage.module.css';
import SearchBarSentences from './SearchBarSentences';

const DEFAULT_PER_PAGE = 10;

const getSentenceListFilters = (searchParams: URLSearchParams) => {
	const keyword = searchParams.get('keyword')?.trim();

	return { per_page: DEFAULT_PER_PAGE, ...(keyword ? { keyword } : {}) };
};

const SentencesList = () => {
	const { isAuthenticated } = useAuth();
	const [searchParams, setSearchParams] = useSearchParams();
	const filters = getSentenceListFilters(searchParams);
	const keyword = filters.keyword ?? '';

	const { sentences, total, isLoading, isFetchingNextPage, hasNextPage, fetchNextPage, error } = useInfiniteSentences(
		{
			filters,
		},
	);

	const handleSearch = (nextKeyword: string) => {
		const nextParams = new URLSearchParams();

		const keyword = nextKeyword.trim();

		if (keyword !== '') {
			nextParams.set('keyword', keyword);
		}

		setSearchParams(nextParams);
	};

	if (isLoading) {
		return <PageLoading family="list" />;
	}

	if (error) {
		return (
			<Container className={styles.page}>
				<Alert tone="danger">Sentences could not be loaded.</Alert>
			</Container>
		);
	}

	return (
		<Container className={styles.page}>
			<Stack gap="2xl">
				<SearchBarSentences defaultKeyword={keyword} onSearch={handleSearch} />

				{isAuthenticated && (
					<Cluster justify="center">
						<Link to="/sentences/new">Create sentence</Link>
					</Cluster>
				)}

				<Stack as="section" gap="md" className={styles.results}>
					<Cluster justify="center" align="baseline" gap="md">
						{keyword ? <h4 className={styles.heading}>keyword: {keyword}</h4> : null}
						<h4 className={styles.heading}>Results total: '{total}'</h4>
					</Cluster>
					<ul className={styles.list}>
						{sentences.map((sentence) => (
							<SentenceItem
								key={sentence.uuid}
								detailIdentifier={sentence.uuid}
								tatoeba_entry={sentence.tatoeba_entry ?? undefined}
								userId={sentence.user_id ?? undefined}
								sentence={sentence.content}
							/>
						))}
					</ul>
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

export default SentencesList;
