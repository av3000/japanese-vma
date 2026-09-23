import type { MappedKanji } from '@/api/kanjis/details';
import { Link } from '@/components/shared/Link';
import styles from './KanjiRelatedResources.module.css';

interface KanjiRelatedSentencesProps {
	items: MappedKanji['related']['sentences'];
	total: number;
}

const KanjiRelatedSentences = ({ items, total }: KanjiRelatedSentencesProps) => (
	<section>
		<h2>Found in ({total}) sentences</h2>
		{items.length === 0 ? (
			<p>No related sentences found.</p>
		) : (
			<ul className={styles.relatedList}>
				{items.map((sentence) => (
					<li className={styles.relatedResource} key={sentence.uuid}>
						<div>
							<p lang="ja">{sentence.content}</p>
							{sentence.tatoeba_entry !== null && (
								<a
									href={`https://tatoeba.org/en/sentences/show/${sentence.tatoeba_entry}`}
									target="_blank"
									rel="noreferrer"
								>
									Tatoeba
								</a>
							)}
						</div>
						<Link className={styles.openLink} to={`/sentence/${sentence.uuid}`}>
							Open
						</Link>
					</li>
				))}
			</ul>
		)}
	</section>
);

export default KanjiRelatedSentences;
