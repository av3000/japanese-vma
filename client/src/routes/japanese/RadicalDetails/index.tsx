import React from 'react';
import { useParams } from 'react-router-dom';
import { useRadicalQuery } from '@/api/radicals/details';
import { PageLoading } from '@/components/shared/PageLoading';
import RadicalContent from './RadicalContent';

const RadicalDetails: React.FC = () => {
	const { radical_id } = useParams<{ radical_id: string }>();
	const { data: radical, isLoading, isError } = useRadicalQuery(radical_id);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !radical) {
		return <div className="container mt-5 text-danger">Unable to load radical.</div>;
	}

	return <RadicalContent radical={radical} />;
};

export default RadicalDetails;
