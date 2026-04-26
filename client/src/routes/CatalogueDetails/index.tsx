import { useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import Spinner from '@/assets/images/spinner.gif';
import { resolveLegacyCatalogueIdentity } from '@/api/catalogues/catalogues';
import { useCatalogueQuery } from '@/api/catalogues/hooks/useCatalogueQuery';
import { CATALOGUE_ROUTES, isCatalogueRouteUuid } from '@/shared/constants/catalogues';
import CatalogueContent from './CatalogueContent';

const CatalogueDetailsPage = () => {
	const navigate = useNavigate();
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const hasUuidParam = Boolean(catalogueId && isCatalogueRouteUuid(catalogueId));

	const legacyIdentityQuery = useQuery({
		queryKey: ['catalogue-legacy-identity', catalogueId],
		queryFn: () => resolveLegacyCatalogueIdentity(catalogueId as string),
		enabled: Boolean(catalogueId && !hasUuidParam),
		retry: false,
	});

	useEffect(() => {
		if (legacyIdentityQuery.data?.uuid) {
			navigate(CATALOGUE_ROUTES.detail(legacyIdentityQuery.data.uuid), { replace: true });
		}
	}, [legacyIdentityQuery.data?.uuid, navigate]);

	const resolvedUuid = hasUuidParam ? catalogueId : legacyIdentityQuery.data?.uuid;
	const { data: catalogue, isPending, isError } = useCatalogueQuery(resolvedUuid, Boolean(resolvedUuid));

	if (!catalogueId || legacyIdentityQuery.isPending || (isPending && !catalogue)) {
		return (
			<div className="container text-center mt-5">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
	}

	if (legacyIdentityQuery.isError || isError || !catalogue) {
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
