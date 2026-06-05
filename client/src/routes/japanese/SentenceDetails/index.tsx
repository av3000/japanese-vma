import React from 'react';
import { Link, useParams } from 'react-router-dom';
import { useSentenceQuery } from '@/api/sentences/details';
import Spinner from '@/assets/images/spinner.gif';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

const getKanjiMeanings = (kanji: { meanings?: string | string[]; meaning?: string }) => {
	if (Array.isArray(kanji.meanings)) {
		return kanji.meanings.slice(0, 3).join(', ');
	}

	return (kanji.meanings ?? kanji.meaning ?? '').split('|').slice(0, 3).join(', ');
};

const SentenceDetails: React.FC = () => {
	const { sentence_id } = useParams();
	const entityId = Number(sentence_id);
	const { isAuthenticated } = useAuth();
	const sentenceQuery = useSentenceQuery(sentence_id);
	const sentence = sentenceQuery.data;

	if (sentenceQuery.isLoading) {
		return (
			<div className="container mt-5">
				<div className="row justify-content-center">
					<img src={Spinner} alt="Loading..." />
				</div>
			</div>
		);
	}

	if (sentenceQuery.error || !sentence) {
		return (
			<div className="container mt-5">
				<div className="row justify-content-center">
					<p>Sentence could not be loaded.</p>
				</div>
			</div>
		);
	}

	return (
		<div className="container">
			<span className="mt-4">
				<Link to="/sentences" className="tag-link">
					Back
				</Link>
			</span>

			<div className="row justify-content-center mt-5">
				<div className="col-md-8">
					<h4>{sentence.content}</h4>
					{sentence.user_id ? (
						<p>User Author - {sentence.user_id}</p>
					) : (
						<p>
							Tatoeba link:{' '}
							<a
								href={`https://tatoeba.org/eng/sentences/show/${sentence.tatoeba_entry}`}
								target="_blank"
								rel="noopener noreferrer"
							>
								{sentence.tatoeba_entry}
							</a>
						</p>
					)}
				</div>

				{isAuthenticated && (
					<AuthorizedBookmarkWidget
						instanceObjectType={SavedListType.SENTENCES}
						isKnownType={SavedListType.KNOWNSENTENCES}
						entityId={entityId}
						modalTitle="Choose Sentence List to add"
					/>
				)}
			</div>

			<hr />

			<>
				<h4>Kanjis ({sentence.kanjis.length}) results</h4>
				<div className="container">
					{sentence.kanjis.map((kanji) => (
						<div className="row justify-content-center mt-5" key={kanji.uuid}>
							<div className="col-md-10">
								<div className="row">
									<div className="col-md-6">
										<h3>{kanji.character}</h3>
									</div>
									<div className="col-md-4">{getKanjiMeanings(kanji)}</div>
									<div className="col-md-2">
										<Link to={`/kanji/${kanji.uuid}`} className="float-right">
											<i className="fas fa-external-link-alt fa-lg"></i>
										</Link>
									</div>
								</div>
								<hr />
							</div>
						</div>
					))}
				</div>
			</>

			<hr />
			<br />
		</div>
	);
};

export default SentenceDetails;
