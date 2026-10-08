import { Suspense, lazy, useId, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useLikeCatalogueMutation, type MappedCatalogue } from '@/api/catalogues/details';
import { useDeleteCatalogueMutation } from '@/api/catalogues/hooks/useDeleteCatalogueMutation';
import {
	catalogueExportKanjisPdf,
	catalogueExportRadicalsPdf,
	catalogueExportSentencesPdf,
	catalogueExportWordsPdf,
} from '@/api/generated/catalogue/catalogue';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import { catalogueCoverGlyph, catalogueJlptCounts } from '@/components/features/LibraryCards/coverRule';
import { CatalogueItems } from '@/components/features/catalogues/CatalogueItems';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Byline } from '@/components/shared/Byline';
import { Chip } from '@/components/shared/Chip';
import { DetailActionGroup, DetailActions } from '@/components/shared/DetailActions';
import { DetailFacts } from '@/components/shared/DetailFacts';
import { DetailLayout } from '@/components/shared/DetailLayout';
import { Icon } from '@/components/shared/Icon';
import { dominantJlptLevel, JlptBar } from '@/components/shared/JlptBar';
import { Link } from '@/components/shared/Link';
import { VisibilityCue } from '@/components/shared/VisibilityCue';
import { Cluster } from '@/components/shared/layout';
import { downloadFile, toDownloadFileName } from '@/helpers/downloadFile';
import { japaneseLang } from '@/helpers/japaneseLang';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import {
	CATALOGUE_ROUTES,
	type CataloguePdfExportKind,
	isCataloguePdfExportSupported,
	isCatalogueStudySupported,
	resolveCataloguePdfExportKind,
	resolveCatalogueTypeLabel,
} from '@/shared/constants/catalogues';
import styles from './CatalogueContent.module.css';

interface CatalogueContentProps {
	catalogue: MappedCatalogue;
}

const LazyCommentsBlock = lazy(() => import('@/components/features/comment/CommentsBlock'));

// A lookup rather than a ternary: a kind added to CataloguePdfExportKind without a client
// here is a type error, where a ternary would quietly export it as words.
const pdfExportClients: Record<CataloguePdfExportKind, typeof catalogueExportKanjisPdf> = {
	kanji: catalogueExportKanjisPdf,
	words: catalogueExportWordsPdf,
	radicals: catalogueExportRadicalsPdf,
	sentences: catalogueExportSentencesPdf,
};

const CatalogueContent = ({ catalogue }: CatalogueContentProps) => {
	const navigate = useNavigate();
	const { user: currentUser, isAuthenticated } = useAuth();
	const tagsHeadingId = useId();
	const commentsHeadingId = useId();
	const [isPdfPending, setIsPdfPending] = useState(false);
	const [pdfErrorMessage, setPdfErrorMessage] = useState<string | null>(null);
	const deleteDialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteModal = useModal(deleteDialogRef, { id: 'catalogue-delete-modal' });
	const isOwner = currentUser?.id === catalogue.owner.id;
	const likesCount = Number(catalogue.engagement?.likes_count ?? 0);
	const viewsCount = Number(catalogue.engagement?.views_count ?? 0);
	const downloadCount = Number(catalogue.engagement?.downloads_count ?? 0);
	const itemsCount = Number(catalogue.items_count ?? 0);
	const likeMutation = useLikeCatalogueMutation(catalogue.uuid);
	const deleteMutation = useDeleteCatalogueMutation();
	const isPdfExportSupported = isCataloguePdfExportSupported(catalogue.type);
	// Study needs cards: visitors can play a public deck (epic #413), but an empty one has nothing to play.
	const isStudyOffered = isCatalogueStudySupported(catalogue.type) && itemsCount > 0;
	const showStudyHint = isOwner && isCatalogueStudySupported(catalogue.type) && itemsCount === 0;
	const typeLabel = catalogue.type_label || resolveCatalogueTypeLabel(catalogue.type);
	const levels = catalogue.jlpt_levels;

	const handleDownloadPdf = async () => {
		if (!isAuthenticated) {
			navigate('/login');
			return;
		}

		const pdfKind = resolveCataloguePdfExportKind(catalogue.type);
		if (!pdfKind) {
			return;
		}

		setIsPdfPending(true);
		setPdfErrorMessage(null);

		try {
			const response = await pdfExportClients[pdfKind](catalogue.uuid, { responseType: 'blob' });
			const file = new Blob([response], { type: 'application/pdf' });

			downloadFile(toDownloadFileName(catalogue.title, `catalogue-${pdfKind}`, 'pdf'), file);
		} catch (error) {
			console.error('Catalogue PDF download failed', error);
			setPdfErrorMessage('The PDF could not be generated. Please try again.');
		} finally {
			setIsPdfPending(false);
		}
	};

	const isLiked = catalogue.engagement?.is_liked_by_viewer ?? false;

	const handleLikeClick = () => {
		// The endpoint answers an anonymous caller with a 401, so the login redirect happens here
		// rather than as error handling after a pointless request.
		if (!isAuthenticated) {
			navigate('/login');
			return;
		}

		likeMutation.mutate(catalogue.id);
	};

	return (
		<>
			<DetailLayout
				railLabel="About this catalogue"
				header={
					<div className={styles.header}>
						<Link to={CATALOGUE_ROUTES.list} className={styles.back}>
							<Icon name="arrowDownSolid" rotate="90" size="sm" /> Catalogues
						</Link>
						<h1 className={styles.title} lang={japaneseLang(catalogue.title)}>
							{catalogue.title}
						</h1>
						{catalogue.description?.trim() ? (
							<p className={styles.description} lang={japaneseLang(catalogue.description)}>
								{catalogue.description}
							</p>
						) : null}
						<Byline name={catalogue.owner.name} date={catalogue.created_at} views={viewsCount} />
						{isOwner ? (
							<Cluster gap="xs">
								<VisibilityCue publicity={catalogue.publicity} />
							</Cluster>
						) : null}
					</div>
				}
				main={
					<CatalogueItems
						catalogueUuid={catalogue.uuid}
						catalogueType={catalogue.type}
						payloadItems={catalogue.items}
						isOwner={isOwner}
						showSave={isAuthenticated}
					/>
				}
				facts={
					<DetailFacts
						title="In this catalogue"
						facts={[
							{ term: 'Items', value: itemsCount },
							{ term: 'Views', value: viewsCount },
							{ term: 'Downloads', value: downloadCount },
						]}
					>
						<div className={styles.type}>
							<span className={styles.typeGlyph} lang="ja" aria-hidden="true">
								{catalogueCoverGlyph(catalogue.type)}
							</span>
							<span>{typeLabel}</span>
						</div>
						{levels && dominantJlptLevel(levels) !== null ? (
							<JlptBar
								size="compact"
								levels={levels}
								counts={catalogueJlptCounts(catalogue.type)}
								className={styles.levels}
							/>
						) : null}
					</DetailFacts>
				}
				actions={
					<DetailActions>
						{isStudyOffered ? (
							<Button variant="primary" isFullWidth to={CATALOGUE_ROUTES.study(catalogue.uuid)}>
								Study
							</Button>
						) : null}
						{showStudyHint ? (
							<p className={styles.hint}>Add kanji, words or radicals to study this catalogue.</p>
						) : null}
						<Button
							variant="outline"
							isFullWidth
							aria-pressed={isLiked}
							disabled={likeMutation.isTogglingInstance(catalogue.id)}
							onClick={handleLikeClick}
						>
							<Icon size="sm" name={isLiked ? 'thumbsUpSolid' : 'thumbsUpRegular'} />
							{`Like · ${likesCount}`}
						</Button>
						{isPdfExportSupported ? (
							<Button variant="outline" isFullWidth isLoading={isPdfPending} onClick={handleDownloadPdf}>
								<Icon size="sm" name="filePdfSolid" />
								Download PDF
							</Button>
						) : null}
						{pdfErrorMessage ? <Alert tone="danger">{pdfErrorMessage}</Alert> : null}
						{isOwner ? (
							<DetailActionGroup heading="Your catalogue">
								<Button variant="outline" isFullWidth to={CATALOGUE_ROUTES.edit(catalogue.uuid)}>
									<Icon size="sm" name="penSolid" />
									Edit catalogue
								</Button>
								<Button
									variant="outline"
									isFullWidth
									aria-controls={deleteModal.id}
									aria-expanded={deleteModal.isOpen}
									onClick={deleteModal.open}
								>
									<Icon size="sm" name="trashbinSolid" />
									Delete catalogue
								</Button>
							</DetailActionGroup>
						) : null}
					</DetailActions>
				}
				extra={
					catalogue.hashtags && catalogue.hashtags.length > 0 ? (
						<section aria-labelledby={tagsHeadingId}>
							<h2 id={tagsHeadingId} className={styles.railHeading}>
								Tags
							</h2>
							<Cluster as="ul" gap="2xs" className={styles.tags}>
								{catalogue.hashtags.map((tag) => (
									<li key={tag.id}>
										<Chip readonly title={tag.content}>
											{tag.content}
										</Chip>
									</li>
								))}
							</Cluster>
						</section>
					) : null
				}
				after={
					<section aria-labelledby={commentsHeadingId} className={styles.comments}>
						<h2 id={commentsHeadingId} className={styles.sectionHeading}>
							Comments
						</h2>
						<Suspense fallback={null}>
							<LazyCommentsBlock parent="catalogue" entityId={catalogue.id} entityUuid={catalogue.uuid} />
						</Suspense>
					</section>
				}
			/>

			<DeleteInstanceModal
				controller={deleteModal}
				instanceName={catalogue.title}
				onDelete={() =>
					deleteMutation.mutate(catalogue.uuid, { onSuccess: () => navigate(CATALOGUE_ROUTES.list) })
				}
				isProcessing={deleteMutation.isPending}
				deleteLabel="Yes, Delete Catalogue"
				ariaLabel="Delete catalogue"
			/>
		</>
	);
};

export default CatalogueContent;
