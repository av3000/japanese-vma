import type { MappedSentenceDetail } from '@/api/sentences/details';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

interface SentenceContentProps {
	sentence: MappedSentenceDetail;
}

const SentenceContent = ({ sentence }: SentenceContentProps) => {
	const { isAuthenticated } = useAuth();

	return (
		<div className="container">
			<div className="mt-4">
				<Link to="/sentences" className="tag-link">Back</Link>
			</div>
			<div className="row justify-content-center mt-5">
				<div className="col-md-8">
					<h4>{sentence.content}</h4>
					{sentence.user_id ? (
						<p>User Author - {sentence.user_id}</p>
					) : (
						<p>
							Tatoeba link:{' '}
							<a href={`https://tatoeba.org/eng/sentences/show/${sentence.tatoeba_entry}`} target="_blank" rel="noopener noreferrer">
								{sentence.tatoeba_entry}
							</a>
						</p>
					)}
				</div>
				{isAuthenticated && (
					<AuthorizedBookmarkWidget
						instanceObjectType={SavedListType.SENTENCES}
						isKnownType={SavedListType.KNOWNSENTENCES}
						entityId={sentence.id}
						modalTitle="Choose Sentence List to add"
					/>
				)}
			</div>
			<hr />
			<h4>Kanjis ({sentence.kanjis.length}) results</h4>
			<div className="container">
				{sentence.kanjis.map((kanji) => (
					<div className="row justify-content-center mt-5" key={kanji.uuid}>
						<div className="col-md-10">
							<div className="row">
								<div className="col-md-6"><h3>{kanji.character}</h3></div>
								<div className="col-md-4">{kanji.meanings.slice(0, 3).join(', ')}</div>
								<div className="col-md-2">
									<Link to={`/kanji/${kanji.uuid}`} className="float-right">Open</Link>
								</div>
							</div>
							<hr />
						</div>
					</div>
				))}
			</div>
		</div>
	);
};

export default SentenceContent;
