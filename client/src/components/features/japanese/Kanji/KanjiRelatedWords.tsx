import type { MappedKanji } from '@/api/kanjis/details';
import { Link } from '@/components/shared/Link';
import styles from './KanjiRelatedResources.module.scss';

interface KanjiRelatedWordsProps {
	items: MappedKanji['related']['words'];
	total: number;
}

const KanjiRelatedWords = ({ items, total }: KanjiRelatedWordsProps) => (
	<section>
		<h2>Found in ({total}) words</h2>
		{items.length === 0 ? (
			<p>No related words found.</p>
		) : (
			<ul className={styles.relatedList}>
				{items.map((word) => (
					<li className={styles.relatedResource} key={word.uuid}>
						<div>
							<h3>
								{word.word} <small>{word.furigana}</small>
							</h3>
							<h3>{word.meanings.slice(0, 3).join(', ')}</h3>
							{word.jlpt && word.jlpt !== '-' && <p>JLPT: {word.jlpt}</p>}
						</div>
						<Link className={styles.openLink} to={`/word/${word.uuid}`}>
							Open
						</Link>
					</li>
				))}
			</ul>
		)}
	</section>
);

export default KanjiRelatedWords;
