import * as React from 'react';
import { useMemo } from 'react';
import { getKanjiIndexQueryKey } from '@/api/generated/kanji/kanji';
import { KANJI_VIEWER_CATALOGUE_INCLUDE } from '@/api/kanjis/hooks/useInfiniteKanjis';
import { useViewerStateWriter } from '@/api/pagedIndex';
import { useRadicalKanjiPages } from '@/api/radicals/related';
import { KanjiTable } from '@/components/features/japanese/Kanji/KanjiTable';
import { PagedControls, PagedListFilters, RelatedSection } from '@/components/features/japanese/dictionaryList';
import styles from './Related.module.css';

interface RadicalKanjiProps {
	radicalUuid: string;
	/** Signed-in viewers get a Save column. */
	showSave: boolean;
}

/**
 * The kanji built on one radical, in place on the radical page: it is the page's only list and the
 * reason to open it, so it pages and searches inline (25 to a page) rather than in a modal.
 */
export const RadicalKanji: React.FC<RadicalKanjiProps> = ({ radicalUuid, showSave }) => {
	const list = useRadicalKanjiPages(radicalUuid);
	const saveKey = useMemo(
		() => getKanjiIndexQueryKey({ radical_uuid: radicalUuid, include: KANJI_VIEWER_CATALOGUE_INCLUDE }),
		[radicalUuid],
	);
	const writeSaveState = useViewerStateWriter(saveKey);
	const failed = list.isError && list.rows.length === 0 && !list.keyword;

	return (
		<RelatedSection title="Kanji with this radical" count={list.keyword ? undefined : list.total}>
			{failed ? (
				<p className={styles.message} role="status">
					The kanji could not be loaded. Reload the page to try again.
				</p>
			) : (
				<>
					<PagedListFilters list={list} noun="kanji" placeholder="Kanji, reading or meaning" />
					<KanjiTable
						kanjis={list.rows}
						loading={list.isPending}
						showSave={showSave}
						empty={
							list.keyword
								? {
										title: `No kanji match “${list.keyword}”.`,
										hint: 'Try a reading or an English meaning.',
									}
								: { title: 'No kanji use this radical yet.' }
						}
						onBookmarkStateChange={writeSaveState}
					/>
					<PagedControls list={list} noun="kanji" label="Kanji pages" />
				</>
			)}
		</RelatedSection>
	);
};

export default RadicalKanji;
