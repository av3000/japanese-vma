import { useParams } from 'react-router-dom';
import { useCatalogueQuery } from '@/api/catalogues/details';
import { Button } from '@/components/shared/Button';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container } from '@/components/shared/layout';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import CatalogueContent from './CatalogueContent';
import styles from './CatalogueDetails.module.css';

const CatalogueDetailsPage = () => {
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const { data, isPending, isError } = useCatalogueQuery(catalogueId);
	const catalogue = data;

	if (!catalogueId || (isPending && !catalogue)) {
		return <PageLoading family="detail" />;
	}

	if (isError || !catalogue) {
		return (
			<Container as="section" className={styles.notFound}>
				<p className="u-text-lead">Catalogue not found or was deleted.</p>
				<Button variant="linkButton" href={CATALOGUE_ROUTES.list}>
					Back to all Catalogues
				</Button>
			</Container>
		);
	}

	return <CatalogueContent catalogue={catalogue} />;
};

export default CatalogueDetailsPage;
