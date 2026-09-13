import { Suspense, lazy, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useLikeCatalogueMutation, type MappedCatalogue } from '@/api/catalogues/details';
import {
	catalogueExportKanjisPdf,
	catalogueExportWordsPdf,
	catalogueRemoveItem,
	getCatalogueIndexQueryKey,
	getCatalogueShowQueryKey,
	useCatalogueDestroy,
} from '@/api/generated/catalogue/catalogue';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import AvatarImg from '@/assets/images/avatar-woman.svg';
import DefaultListImg from '@/assets/images/smartphone-screen-with-art-photo-gallery-application-3850271-mid.jpg';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import { CatalogueItems } from '@/components/features/catalogues/CatalogueItems';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Cluster, Container, Stack } from '@/components/shared/layout';
import { formatDate } from '@/helpers';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import {
	CATALOGUE_ROUTES,
	isCataloguePdfExportSupported,
	resolveCataloguePdfExportKind,
} from '@/shared/constants/catalogues';
import { ObjectTemplateType } from '@/shared/constants/enums';
import styles from './CatalogueContent.module.css';

interface CatalogueContentProps {
	catalogue: MappedCatalogue;
}

const LazyCommentsBlock = lazy(() => import('@/components/features/comment/CommentsBlock'));

const CatalogueContent = ({ catalogue }: CatalogueContentProps) => {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const { user: currentUser, isAuthenticated } = useAuth();
	const [editMode, setEditMode] = useState(false);
	const deleteDialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteModal = useModal(deleteDialogRef, { id: 'catalogue-delete-modal' });
	const isOwner = currentUser?.id === catalogue.owner.id;
	const likesCount = Number(catalogue.engagement?.likes_count ?? 0);
	const viewsCount = Number(catalogue.engagement?.views_count ?? 0);
	const downloadCount = Number(catalogue.engagement?.downloads_count ?? 0);
	const likeMutation = useLikeCatalogueMutation(catalogue.uuid);
	const isPdfExportSupported = isCataloguePdfExportSupported(catalogue.type);

	const deleteMutation = useCatalogueDestroy({
		mutation: {
			onSuccess: () => {
				queryClient.invalidateQueries({ queryKey: getCatalogueIndexQueryKey() });
				queryClient.invalidateQueries({ queryKey: getCatalogueShowQueryKey(catalogue.uuid) });
				navigate(CATALOGUE_ROUTES.list);
			},
		},
	});

	const removeItemMutation = useMutation<unknown, unknown, number>({
		mutationFn: (itemId: number) => catalogueRemoveItem(catalogue.uuid, itemId),
		onSuccess: (_, itemId) => {
			queryClient.setQueryData(
				getCatalogueShowQueryKey(catalogue.uuid),
				(old: CatalogueDetailResource | undefined) => {
					if (!old) return old;
					return {
						...old,
						items: (old.items as unknown as Array<{ id: number }>).filter((item) => item.id !== itemId),
						items_count: Math.max(0, Number(old.items_count) - 1),
					};
				},
			);
		},
	});

	const handleDownloadPdf = async () => {
		if (!isAuthenticated) {
			navigate('/login');
			return;
		}

		const pdfKind = resolveCataloguePdfExportKind(catalogue.type);
		if (!pdfKind) {
			return;
		}

		try {
			const response =
				pdfKind === 'kanji'
					? await catalogueExportKanjisPdf(catalogue.uuid, { responseType: 'blob' })
					: await catalogueExportWordsPdf(catalogue.uuid, { responseType: 'blob' });
			const file = new Blob([response], { type: 'application/pdf' });
			window.open(URL.createObjectURL(file));
		} catch (error) {
			console.error('Catalogue PDF download failed', error);
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
		<Container size="sm" className={styles.page}>
			<Stack gap="2xl">
				<Stack as="section" gap="md">
					<div>
						<Link to={CATALOGUE_ROUTES.list} className="tag-link">
							<Icon name="arrowDownSolid" rotate="90" size="sm" /> Back to Catalogues
						</Link>
					</div>

					<h1 className={styles.title}>{catalogue.title}</h1>

					<Cluster justify="between" gap="sm" className={styles.muted}>
						<div>
							{formatDate(catalogue.created_at, 'ja')} <br />
							<span>{viewsCount} views</span>
							{isOwner && <span> | {catalogue.publicity === 1 ? 'Public' : 'Private'}</span>}
							<br />
							<strong>{catalogue.type_label}</strong>
						</div>

						{isOwner && (
							<Cluster gap="2xs">
								<Button
									onClick={deleteModal.open}
									variant="ghost"
									hasOnlyIcon
									aria-controls={deleteModal.id}
									aria-expanded={deleteModal.isOpen}
								>
									<Icon name="trashbinSolid" size="md" />
								</Button>
								<Button
									onClick={() => navigate(CATALOGUE_ROUTES.edit(catalogue.uuid))}
									variant="ghost"
									hasOnlyIcon
								>
									<Icon name="penSolid" size="md" />
								</Button>
							</Cluster>
						)}
					</Cluster>

					<img className={styles.cover} src={DefaultListImg} alt="Cover" />
					<p className={styles.description}>{catalogue.description ?? 'No description yet.'}</p>

					{catalogue.hashtags && catalogue.hashtags.length > 0 && (
						<Cluster as="ul" gap="2xs" className={styles.tagList}>
							{catalogue.hashtags.map((tag) => (
								<li key={tag.id}>
									<Chip readonly title={tag.content}>
										{tag.content}
									</Chip>
								</li>
							))}
						</Cluster>
					)}

					<hr className={styles.divider} />

					<Cluster justify="between" gap="sm">
						<Cluster gap="md">
							<img src={AvatarImg} alt="user" width="40" className={styles.avatar} />
							<p className={styles.text}>
								Created by <strong>{catalogue.owner.name}</strong>
							</p>
						</Cluster>
						<Cluster gap="xs">
							<p className={styles.text}>{likesCount}</p>
							<Button
								variant="ghost"
								hasOnlyIcon
								aria-label={isLiked ? 'Unlike this catalogue' : 'Like this catalogue'}
								aria-pressed={isLiked}
								disabled={likeMutation.isTogglingInstance(catalogue.id)}
								onClick={handleLikeClick}
							>
								<Icon size="md" name={isLiked ? 'thumbsUpSolid' : 'thumbsUpRegular'} />
							</Button>
							{isPdfExportSupported && (
								<Button variant="ghost" hasOnlyIcon onClick={handleDownloadPdf}>
									<Icon size="md" name="filePdfSolid" />
								</Button>
							)}
							{downloadCount > 0 && <span className={styles.muted}>{downloadCount} downloads</span>}
						</Cluster>
					</Cluster>
				</Stack>

				<Stack as="section" gap="xs">
					{catalogue.items.length > 0 ? (
						<>
							{isOwner && (
								<div>
									<Button
										onClick={() => setEditMode((current) => !current)}
										size="sm"
										variant={editMode ? 'success' : 'ghost'}
									>
										{editMode ? 'End' : 'Edit'}
									</Button>
								</div>
							)}
							<CatalogueItems
								// TODO: Backend - add generic items type as 'InstanceItem[]' that would be a list of kanji, words, sentences, radicals or articals type. Orvel autogenerates and use it here.
								items={catalogue.items}
								catalogueType={catalogue.type}
								currentUser={currentUser}
								ownerId={catalogue.owner.id}
								editMode={editMode}
								onRemoveItem={(itemId) => removeItemMutation.mutate(itemId)}
							/>
						</>
					) : (
						<p className={styles.muted}>This catalogue has no items yet.</p>
					)}
				</Stack>

				<section>
					<Suspense fallback={null}>
						<LazyCommentsBlock
							readObjectType="catalogue"
							readObjectUuid={catalogue.uuid}
							entityId={catalogue.id}
							entityType={ObjectTemplateType.LIST}
							entityUuid={catalogue.uuid}
						/>
					</Suspense>
				</section>
			</Stack>

			<DeleteInstanceModal
				controller={deleteModal}
				instanceName={catalogue.title}
				onDelete={() => deleteMutation.mutate({ uuid: catalogue.uuid })}
				isProcessing={deleteMutation.isPending}
				deleteLabel="Yes, Delete Catalogue"
				ariaLabel="Delete catalogue"
			/>
		</Container>
	);
};

export default CatalogueContent;
