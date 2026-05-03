import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import { useParams } from 'react-router-dom';
import { useCatalogueShow } from '@/api/generated/catalogue/catalogue';
import Spinner from '@/assets/images/spinner.gif';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import CatalogueContent from './CatalogueContent';

const CatalogueDetailsPage = () => {
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const { data, isPending, isError } = useCatalogueShow<CatalogueDetailResource>(catalogueId ?? '', {
		query: {
			enabled: Boolean(catalogueId),
		},
	});
	const catalogue = data;

	if (!catalogueId || (isPending && !catalogue)) {
		return (
			<div className="container text-center mt-5">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
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
