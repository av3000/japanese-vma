import React from 'react';
import { useParams } from 'react-router-dom';
import { useWordQuery } from '@/api/words/details';
import Spinner from '@/assets/images/spinner.gif';
import WordContent from './WordContent';

const WordDetails: React.FC = () => {
	const { word_id } = useParams<{ word_id: string }>();
	const { data: word, isLoading, isError } = useWordQuery(word_id);

	if (isLoading) {
		return (
			<div className="container text-center mt-5">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
	}

	if (isError || !word) {
		return <div className="container mt-5 text-danger">Unable to load word.</div>;
	}

	return <WordContent word={word} />;
};

export default WordDetails;
