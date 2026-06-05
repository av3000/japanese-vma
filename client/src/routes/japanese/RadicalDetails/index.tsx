import React from 'react';
import { Link, useParams } from 'react-router-dom';
import { useRadicalQuery } from '@/api/radicals/details';
import Spinner from '@/assets/images/spinner.gif';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

const RadicalDetails: React.FC = () => {
	const { radical_id } = useParams();
	const entityId = Number(radical_id);
	const { isAuthenticated } = useAuth();
	const { data: radical, isLoading: radicalIsLoading, isError } = useRadicalQuery(radical_id);

	if (radicalIsLoading) {
		return (
			<div className="container">
				<div className="row justify-content-center">
					<img src={Spinner} alt="spinner" />
				</div>
			</div>
		);
	}

	if (isError || !radical) {
		return (
			<div className="container">
				<div className="mt-5">
					<Link to="/radicals" className="tag-link">
						Back
					</Link>
				</div>
				<div className="row justify-content-center mt-5">
					<p>Unable to load radical.</p>
				</div>
			</div>
		);
	}

	return (
		<div className="container">
			<div className="mt-5">
				<Link to="/radicals" className="tag-link">
					Back
				</Link>
			</div>
			<div className="row justify-content-center mt-5">
				<div className="col-md-6">
					<h1>
						{radical.radical} <br />
						{radical.hiragana}
					</h1>
				</div>
				<div className="col-md-6">
					<p>meaning: {radical.meaning}</p>
					<p>strokes: {radical.strokes}</p>
					{isAuthenticated && (
						<AuthorizedBookmarkWidget
							instanceObjectType={SavedListType.RADICALS}
							isKnownType={SavedListType.KNOWNRADICALS}
							entityId={entityId}
							modalTitle="Choose Radical List to add"
						/>
					)}
				</div>
			</div>

			<hr />
			{radical.kanjis.length > 0 && (
				<>
					<h4>kanjis ({radical.kanjis.length}) results</h4>
					{radical.kanjis.map((kanji) => (
						<div className="row justify-content-center mt-5" key={kanji.uuid}>
							<div className="col-md-8">
								<div className="row justify-content-center">
									<div className="col-md-6">
										<h3>{kanji.character}</h3>
									</div>
									<div className="col-md-4">{kanji.meanings}</div>
									<div className="col-md-2">
										<Link to={`/kanji/${kanji.character}`} className="float-right">
											<i className="fas fa-external-link-alt fa-lg"></i>
										</Link>
									</div>
								</div>
								<hr />
							</div>
						</div>
					))}
				</>
			)}
		</div>
	);
};

export default RadicalDetails;
