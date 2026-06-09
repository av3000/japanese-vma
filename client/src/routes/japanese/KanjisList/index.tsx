import { useSearchParams } from 'react-router-dom';
import { getKanjiDisplayValues } from '@/api/kanjis/display';
import { type KanjiListFilters, useInfiniteKanjis } from '@/api/kanjis/hooks/useInfiniteKanjis';
import { KanjiIndexJlpt } from '@/api/generated/model/kanjiIndexJlpt';
import Spinner from '@/assets/images/spinner.gif';
import KanjiItem from '@/components/features/japanese/Kanji/KanjiItem';
import { Button } from '@/components/shared/Button';
import SearchBarKanjis from './SearchBarKanjis';
import type { KanjiSearchFilters } from './SearchBarKanjis';

const DEFAULT_PER_PAGE = 10;
const VALID_JLPT_FILTERS = new Set<string>(Object.values(KanjiIndexJlpt));

const getJlptFilter = (value: string | undefined) => {
	if (!value || !VALID_JLPT_FILTERS.has(value)) {
		return undefined;
	}

	return value as NonNullable<KanjiListFilters['jlpt']>;
};

const getKanjiListFilters = (searchParams: URLSearchParams): KanjiListFilters => {
	const keyword = searchParams.get('keyword')?.trim();
	const jlpt = getJlptFilter(searchParams.get('jlpt')?.trim());

	return {
		per_page: DEFAULT_PER_PAGE,
		...(keyword ? { keyword } : {}),
		...(jlpt ? { jlpt } : {}),
	};
};

const KanjisList = () => {
	const [searchParams, setSearchParams] = useSearchParams();
	const filters = getKanjiListFilters(searchParams);
	const keyword = filters.keyword ?? '';
	const jlpt = filters.jlpt ?? '';

	const { kanjis, total, error, fetchNextPage, hasNextPage, isFetchingNextPage, isPending, isError } =
		useInfiniteKanjis({
			filters,
		});

	const handleSearch = ({ keyword, jlpt }: KanjiSearchFilters) => {
		const nextParams = new URLSearchParams();

		if (keyword !== '') {
			nextParams.set('keyword', keyword);
		}

		if (jlpt !== '') {
			nextParams.set('jlpt', jlpt);
		}

		setSearchParams(nextParams);
	};

	if (isPending && kanjis.length === 0) {
		return (
			<div className="container text-center">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
	}

	if (isError) {
		const message = error instanceof Error ? error.message : 'Unable to load kanjis.';

		return <div className="container mt-5 text-danger">Error: {message}</div>;
	}

	return (
		<div className="container mt-5">
			<div className="row justify-content-center">
				<SearchBarKanjis defaultKeyword={keyword} defaultJlpt={jlpt} onSearch={handleSearch} />
			</div>
			<div className="container mt-5">
				<div className="row justify-content-center">
					{keyword && <h4>keyword: {keyword}</h4>}
					&nbsp;
					{jlpt && <h4>JLPT: {jlpt === '-' ? 'Uncommon' : `N${jlpt}`}</h4>}
					&nbsp;
					<h4>
						Showing {kanjis.length} of {total}
					</h4>
				</div>
				<div className="row">
					<div className="col-lg-8 col-md-10 mx-auto">
						{kanjis.length === 0 ? (
							<p>No kanjis found.</p>
						) : (
							kanjis.map((kanji) => {
								const display = getKanjiDisplayValues(kanji);

								return (
									<KanjiItem
										key={kanji.uuid}
										uuid={kanji.uuid}
										character={kanji.character}
										strokeCount={kanji.stroke_count}
										onyomi={display.onyomi}
										kunyomi={display.kunyomi}
										meaning={display.meaning}
										frequency={display.frequency}
										jlpt={display.jlpt}
										parts={display.radicalParts}
									/>
								);
							})
						)}
					</div>
				</div>
			</div>
			<div className="row justify-content-center">
				{isFetchingNextPage ? (
					<img src={Spinner} alt="Loading more..." style={{ height: '40px' }} />
				) : hasNextPage ? (
					<Button variant="secondary-outline" className="w-50" onClick={() => fetchNextPage()}>
						Load More
					</Button>
				) : (
					<span className="text-muted">No more results</span>
				)}
			</div>
		</div>
	);
};

export default KanjisList;
