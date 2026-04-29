import { useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import Spinner from '@/assets/images/spinner.gif';
import { resolveLegacyCatalogueIdentity } from '@/api/catalogues/legacyCatalogues';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';

const CatalogueStaticRedirect = ({ to }: { to: string }) => {
	const navigate = useNavigate();

	useEffect(() => {
		navigate(to, { replace: true });
	}, [navigate, to]);

	return null;
};

const CatalogueIdentityRedirect = ({ to }: { to: (catalogueUuid: string) => string }) => {
	const navigate = useNavigate();
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const [isError, setIsError] = useState(false);

	useEffect(() => {
		if (!catalogueId) {
			setIsError(true);
			return;
		}

		let isMounted = true;

		resolveLegacyCatalogueIdentity(catalogueId)
			.then(({ uuid }) => {
				if (!isMounted) return;
				navigate(to(uuid), { replace: true });
			})
			.catch(() => {
				if (!isMounted) return;
				setIsError(true);
			});

		return () => {
			isMounted = false;
		};
	}, [catalogueId, navigate, to]);

	if (isError) {
		return (
			<div className="container mt-5 text-center">
				<p className="lead">Catalogue not found or was deleted.</p>
				<a href={CATALOGUE_ROUTES.list} className="btn btn-link">
					Back to all Catalogues
				</a>
			</div>
		);
	}

	return (
		<div className="container text-center mt-5">
			<img src={Spinner} alt="Loading..." />
		</div>
	);
};

export const CatalogueLegacyListRedirect = () => <CatalogueStaticRedirect to={CATALOGUE_ROUTES.list} />;

export const CatalogueLegacyCreateRedirect = () => <CatalogueStaticRedirect to={CATALOGUE_ROUTES.create} />;

export const CatalogueLegacyDetailRedirect = () => (
	<CatalogueIdentityRedirect to={CATALOGUE_ROUTES.detail} />
);

export const CatalogueLegacyEditRedirect = () => <CatalogueIdentityRedirect to={CATALOGUE_ROUTES.edit} />;

export const getCatalogueLegacyRedirectVariant = (pathname: string) => {
	if (pathname === CATALOGUE_ROUTES.legacyList) {
		return 'list';
	}

	if (pathname === CATALOGUE_ROUTES.legacyCreate) {
		return 'create';
	}

	if (pathname.startsWith('/list/edit/')) {
		return 'edit';
	}

	return 'detail';
};

const CatalogueLegacyRedirectsPage = () => {
	const location = useLocation();

	const redirectVariant = getCatalogueLegacyRedirectVariant(location.pathname);

	if (redirectVariant === 'list') {
		return <CatalogueLegacyListRedirect />;
	}

	if (redirectVariant === 'create') {
		return <CatalogueLegacyCreateRedirect />;
	}

	if (redirectVariant === 'edit') {
		return <CatalogueLegacyEditRedirect />;
	}

	return <CatalogueLegacyDetailRedirect />;
};

export default CatalogueLegacyRedirectsPage;
