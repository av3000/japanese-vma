import type { MappedWordDetail } from '@/api/words/details';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Chip } from '@/components/shared/Chip';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

interface WordContentProps {
	word: MappedWordDetail;
}

const WordContent = ({ word }: WordContentProps) => {
	const { isAuthenticated } = useAuth();

	return (
		<div className="container">
			<div className="mt-4">
				<Link to="/words" className="tag-link">Back</Link>
			</div>
			<div className="row justify-content-center mt-5">
				<div className="col-md-4">
					<h1>{word.word}</h1>
					<p>Furigana: {word.furigana}</p>
				</div>
				<div className="col-md-4"><p>Type: {word.word_type}</p></div>
				<div className="col-md-4">
					<p>JLPT: {word.jlpt} <br /> Meaning: {word.meanings.join(', ')}</p>
					{isAuthenticated && (
						<AuthorizedBookmarkWidget
							instanceObjectType={SavedListType.WORDS}
							isKnownType={SavedListType.KNOWNWORDS}
							entityId={word.id}
							modalTitle="Choose Word List to add"
						/>
					)}
				</div>
			</div>

			<hr />
			<h4>Kanjis ({word.kanjis.length}) results</h4>
			<div className="container">
				{word.kanjis.map((kanji) => (
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

			<hr />
			<h4>Articles ({word.articles.length}) results</h4>
			<div className="container">
				{word.articles.map((article) => (
					<div className="row justify-content-center mt-5" key={article.uuid}>
						<div className="col-md-12">
							<div className="row">
								<div className="col-md-8">
									<h3>{article.title_jp}</h3>
									<section className="mt-2 d-flex align-items-center flex-wrap">
										{article.hashtags.map((tag) => (
											<Chip className="mr-1" readonly key={tag.id} title={tag.content} name={tag.content}>
												{tag.content}
											</Chip>
										))}
									</section>
								</div>
								<div className="col-md-2">
									<p>Views: {article.views_total} <br /> Likes: {article.likes_total} <br /> Comments: {article.comments_total}</p>
								</div>
								<div className="col-md-2">
									<Link to={`/articles/${article.uuid}`} className="float-right" target="_blank">Open</Link>
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

export default WordContent;
