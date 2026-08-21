import type { MappedKanji } from '@/api/kanjis/details';
import { Link } from '@/components/shared/Link';
import styles from './KanjiRelatedResources.module.scss';

interface KanjiRelatedSentencesProps {
	items: MappedKanji['related']['sentences'];
	total: number;
}

const KanjiRelatedSentences = ({ items, total }: KanjiRelatedSentencesProps) => (
	<section className="mt-5">
		<h2>Found in ({total}) sentences</h2>
		{items.length === 0 ? (
			<p>No related sentences found.</p>
		) : (
			items.map((sentence) => (
				<div
					className={`${styles.relatedResource} post-preview d-flex justify-content-between align-items-start`}
					key={sentence.uuid}
				>
					<div>
						<p>{sentence.content}</p>
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
					<Link className="ml-3 flex-shrink-0" to={`/sentence/${sentence.uuid}`}>
						Open
					</Link>
				</div>
			))
		)}
	</section>
);

export default KanjiRelatedSentences;
