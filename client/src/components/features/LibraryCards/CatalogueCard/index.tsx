import type { Catalogue } from '@/api/catalogues/catalogues';
import { dominantJlptLevel, JlptBar } from '@/components/shared/JlptBar';
import { CATALOGUE_ROUTES, resolveCatalogueTypeLabel } from '@/shared/constants/catalogues';
import { CardCover, CoverChip } from '../CardCover';
import {
	CardOwner,
	CardStats,
	CardSubtitle,
	CardTags,
	CardTitle,
	japaneseLang,
	LibraryCard,
	toCount,
} from '../LibraryCard';
import { catalogueCoverGlyph, catalogueJlptCounts } from '../coverRule';

interface CatalogueCardProps {
	catalogue: Catalogue;
}

const itemsCaption = (count: number) =>
	`${new Intl.NumberFormat('en-US').format(count)} ${count === 1 ? 'item' : 'items'}`;

/**
 * A catalogue as a Library Card: the type glyph, type and item count on the cover, then the
 * title, description, owner, tags, the JLPT bar and the engagement counts.
 *
 * The bar shows only when some N1–N5 level has a count. Words carry no JLPT level in the word
 * bank yet, so a Words or Sentences catalogue would otherwise show one grey "uncommon" bar (#381).
 */
export const CatalogueCard = ({ catalogue }: CatalogueCardProps) => {
	const typeLabel = catalogue.type_label || resolveCatalogueTypeLabel(catalogue.type);
	const levels = catalogue.jlpt_levels;
	const engagement = catalogue.engagement;

	return (
		<LibraryCard
			cover={
				<CardCover
					glyph={catalogueCoverGlyph(catalogue.type)}
					badge={<CoverChip>{typeLabel}</CoverChip>}
					caption={itemsCaption(catalogue.items_count)}
				/>
			}
		>
			<CardTitle to={CATALOGUE_ROUTES.detail(catalogue.uuid)} lang={japaneseLang(catalogue.title)}>
				{catalogue.title}
			</CardTitle>
			{catalogue.description && (
				<CardSubtitle lines={2} lang={japaneseLang(catalogue.description)}>
					{catalogue.description}
				</CardSubtitle>
			)}
			<CardOwner name={catalogue.owner.name} />
			<CardTags tags={catalogue.hashtags} />
			{levels && dominantJlptLevel(levels) !== null && (
				<JlptBar size="compact" levels={levels} counts={catalogueJlptCounts(catalogue.type)} />
			)}
			<CardStats
				stats={[
					{ kind: 'views', count: toCount(engagement?.views_count) },
					{ kind: 'comments', count: toCount(engagement?.comments_count) },
					{ kind: 'likes', count: toCount(engagement?.likes_count) },
					{ kind: 'downloads', count: toCount(engagement?.downloads_count) },
				]}
			/>
		</LibraryCard>
	);
};

export default CatalogueCard;
