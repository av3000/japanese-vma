import { useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useCatalogueShow } from '@/api/generated/catalogue/catalogue';
import Spinner from '@/assets/images/spinner.gif';
import { resolveLegacyCatalogueIdentity } from '@/api/catalogues/legacyCatalogues';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import { getCatalogueRouteState } from '@/routes/catalogueRouteState';
import CatalogueContent from './CatalogueContent';

const CatalogueDetailsPage = () => {
	const navigate = useNavigate();
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const routeState = getCatalogueRouteState(catalogueId);

	const legacyIdentityQuery = useQuery({
		queryKey: ['catalogue-legacy-identity', catalogueId],
		queryFn: () => resolveLegacyCatalogueIdentity(catalogueId as string),
		enabled: routeState.shouldResolveLegacyIdentity,
		retry: false,
	});

	useEffect(() => {
		if (legacyIdentityQuery.data?.uuid) {
			navigate(CATALOGUE_ROUTES.detail(legacyIdentityQuery.data.uuid), { replace: true });
		}
	}, [legacyIdentityQuery.data?.uuid, navigate]);

	const resolvedUuid = getCatalogueRouteState(catalogueId, legacyIdentityQuery.data?.uuid).resolvedUuid;
	const { data, isPending, isError } = useCatalogueShow(resolvedUuid ?? '', {
		query: {
			enabled: Boolean(resolvedUuid),
		},
	});
	const catalogue = data?.catalogue;

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
