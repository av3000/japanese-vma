import { useParams } from 'react-router-dom';
import { useCatalogueQuery } from '@/api/catalogues/details';
import { DetailUnavailable, unavailableMessage } from '@/components/shared/DetailLayout';
import { PageLoading } from '@/components/shared/PageLoading';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import CatalogueContent from './CatalogueContent';

const CatalogueDetailsPage = () => {
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const { data, isPending, isError, error } = useCatalogueQuery(catalogueId);
	const catalogue = data;

	if (!catalogueId || (isPending && !catalogue)) {
		return <PageLoading family="detail" />;
	}

	if (isError || !catalogue) {
		return (
			<DetailUnavailable
				message={unavailableMessage(error, 'catalogue')}
				backTo={CATALOGUE_ROUTES.list}
				backLabel="Back to Catalogues"
			/>
		);
	}

	return <CatalogueContent catalogue={catalogue} />;
};

export default CatalogueDetailsPage;
