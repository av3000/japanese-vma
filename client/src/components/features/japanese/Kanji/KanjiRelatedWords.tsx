import type { MappedKanji } from '@/api/kanjis/details';
import { Link } from '@/components/shared/Link';
import styles from './KanjiRelatedResources.module.scss';

interface KanjiRelatedWordsProps {
	items: MappedKanji['related']['words'];
	total: number;
}

const KanjiRelatedWords = ({ items, total }: KanjiRelatedWordsProps) => (
	<section className="mt-5">
		<h2>Found in ({total}) words</h2>
		{items.length === 0 ? (
			<p>No related words found.</p>
		) : (
			items.map((word) => (
				<div
					className={`${styles.relatedResource} post-preview d-flex justify-content-between align-items-start`}
					key={word.uuid}
				>
					<div>
						<h3>
							{word.word} <small>{word.furigana}</small>
						</h3>
						<h3>{word.meanings.slice(0, 3).join(', ')}</h3>
						{word.jlpt && word.jlpt !== '-' && <p>JLPT: {word.jlpt}</p>}
					</div>
					<Link className="ml-3 flex-shrink-0" to={`/word/${word.uuid}`}>
						Open
					</Link>
				</div>
			))
		)}
	</section>
);

export default KanjiRelatedWords;
