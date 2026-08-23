import type { MappedRadical } from '@/api/radicals/details';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

interface RadicalContentProps {
	radical: MappedRadical;
}

const RadicalContent = ({ radical }: RadicalContentProps) => {
	const { isAuthenticated } = useAuth();

	return (
		<div className="container">
			<div className="mt-5">
				<Link to="/radicals" className="tag-link">Back</Link>
			</div>
			<div className="row justify-content-center mt-5">
				<div className="col-md-6">
					<h1>{radical.radical} <br /> {radical.hiragana}</h1>
				</div>
				<div className="col-md-6">
					<p>meaning: {radical.meaning}</p>
					<p>strokes: {radical.strokes}</p>
					{isAuthenticated && (
						<AuthorizedBookmarkWidget
							instanceObjectType={SavedListType.RADICALS}
							isKnownType={SavedListType.KNOWNRADICALS}
							entityId={radical.id}
							modalTitle="Choose Radical List to add"
						/>
					)}
				</div>
			</div>
			<hr />
			{radical.kanjis.length > 0 && (
				<>
					<h4>Kanjis ({radical.kanjis.length}) results</h4>
					{radical.kanjis.map((kanji) => (
						<div className="row justify-content-center mt-5" key={kanji.uuid}>
							<div className="col-md-8">
								<div className="row justify-content-center">
									<div className="col-md-6"><h3>{kanji.character}</h3></div>
									<div className="col-md-4">{kanji.meanings.join(', ')}</div>
									<div className="col-md-2">
										<Link to={`/kanji/${kanji.uuid}`} className="float-right">Open</Link>
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

export default RadicalContent;
