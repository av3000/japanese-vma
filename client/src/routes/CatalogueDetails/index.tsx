import { useParams } from 'react-router-dom';
import { useCatalogueQuery } from '@/api/catalogues/details';
import { PageLoading } from '@/components/shared/PageLoading';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import CatalogueContent from './CatalogueContent';

const CatalogueDetailsPage = () => {
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const { data, isPending, isError } = useCatalogueQuery(catalogueId);
	const catalogue = data;

	if (!catalogueId || (isPending && !catalogue)) {
		return <PageLoading family="detail" />;
	}

	if (isError || !catalogue) {
		return (
			<div className="container mt-5 text-center">
				<p className="lead">Catalogue not found or was deleted.</p>
				<a href={CATALOGUE_ROUTES.list} className="btn btn-link">
					Back to all Catalogues
				</a>
			</div>
		);
	}

	return <CatalogueContent catalogue={catalogue} />;
};

export default CatalogueDetailsPage;
