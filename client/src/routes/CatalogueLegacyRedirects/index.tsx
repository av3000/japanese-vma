import { useEffect } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { useCatalogueResolveLegacyId } from '@/api/generated/catalogue/catalogue';
import type { CatalogueLegacyIdentityResource } from '@/api/generated/model/catalogueLegacyIdentityResource';
import Spinner from '@/assets/images/spinner.gif';
import { Button } from '@/components/shared/Button';
import { Container } from '@/components/shared/layout';
import { CATALOGUE_ROUTES, parseCatalogueLegacyId } from '@/shared/constants/catalogues';
import styles from './CatalogueLegacyRedirects.module.css';

const CatalogueStaticRedirect = ({ to }: { to: string }) => {
	const navigate = useNavigate();

	useEffect(() => {
		navigate(to, { replace: true });
	}, [navigate, to]);

	return null;
};

export type CatalogueLegacyResolution =
	| { status: 'redirect'; to: string }
	| { status: 'pending' }
	| { status: 'failed' };

/**
 * Kept pure so every branch is assertable without a DOM: an id the route
 * constraint rejects and a resolver error share the one failure path, and the
 * canonical UUID target is only produced once the identity has arrived.
 */
export const resolveCatalogueLegacyTarget = ({
	legacyId,
	identity,
	isError,
	to,
}: {
	legacyId: number | null;
	identity: CatalogueLegacyIdentityResource | undefined;
	isError: boolean;
	to: (catalogueUuid: string) => string;
}): CatalogueLegacyResolution => {
	if (legacyId === null || isError) {
		return { status: 'failed' };
	}

	if (!identity) {
		return { status: 'pending' };
	}

	return { status: 'redirect', to: to(identity.uuid) };
};

const CatalogueIdentityRedirect = ({ to }: { to: (catalogueUuid: string) => string }) => {
	const navigate = useNavigate();
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const legacyId = parseCatalogueLegacyId(catalogueId);

	const { data: identity, isError } = useCatalogueResolveLegacyId(legacyId ?? 0, {
		query: { enabled: legacyId !== null, retry: false },
	});

	const resolution = resolveCatalogueLegacyTarget({ legacyId, identity, isError, to });
	const redirectTo = resolution.status === 'redirect' ? resolution.to : null;

	useEffect(() => {
		if (!redirectTo) return;

		navigate(redirectTo, { replace: true });
	}, [navigate, redirectTo]);

	if (resolution.status === 'failed') {
		return (
			<Container as="section" className={styles.page}>
				<p className="u-text-lead">Catalogue not found or was deleted.</p>
				<Button variant="linkButton" href={CATALOGUE_ROUTES.list}>
					Back to all Catalogues
				</Button>
			</Container>
		);
	}

	return (
		<Container as="section" className={styles.page}>
			<img src={Spinner} alt="Loading..." />
		</Container>
	);
};

export const CatalogueLegacyListRedirect = () => <CatalogueStaticRedirect to={CATALOGUE_ROUTES.list} />;

export const CatalogueLegacyCreateRedirect = () => <CatalogueStaticRedirect to={CATALOGUE_ROUTES.create} />;

export const CatalogueLegacyDetailRedirect = () => <CatalogueIdentityRedirect to={CATALOGUE_ROUTES.detail} />;

export const CatalogueLegacyEditRedirect = () => <CatalogueIdentityRedirect to={CATALOGUE_ROUTES.edit} />;

export const getCatalogueLegacyRedirectVariant = (pathname: string) => {
	if (pathname === CATALOGUE_ROUTES.legacyList) {
		return 'list';
	}

	if (pathname === CATALOGUE_ROUTES.legacyCreate) {
		return 'create';
	}

	if (pathname.startsWith(CATALOGUE_ROUTES.legacyEdit(''))) {
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
