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
import { formatDate } from '@/helpers';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import {
	CATALOGUE_ROUTES,
	isCataloguePdfExportSupported,
	resolveCataloguePdfExportKind,
} from '@/shared/constants/catalogues';
import { ObjectTemplateType } from '@/shared/constants/enums';

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

	return (
		<div className="container pb-5">
			<div className="row justify-content-center">
				<div className="col-lg-8">
					<span className="row mt-4">
						<Link to={CATALOGUE_ROUTES.list} className="tag-link">
							<Icon name="arrowDownSolid" rotate="90" size="sm" /> Back to Catalogues
						</Link>
					</span>

					<h1 className="mt-4">{catalogue.title}</h1>

					<div className="row text-muted w-100 mb-3 justify-content-between align-items-center">
						<div className="col">
							{formatDate(catalogue.created_at, 'ja')} <br />
							<span>{viewsCount} views</span>
							{isOwner && <span> | {catalogue.publicity === 1 ? 'Public' : 'Private'}</span>}
							<br />
							<strong>{catalogue.type_label}</strong>
						</div>

						<div className="d-flex align-items-center">
							{isOwner && (
								<div className="d-flex ml-2">
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
								</div>
							)}
						</div>
					</div>

					<img className="img-fluid rounded mb-3 w-100" src={DefaultListImg} alt="Cover" />
					<p className="lead">{catalogue.description ?? 'No description yet.'}</p>

					<section className="mt-2 d-flex align-items-center flex-wrap">
						{catalogue.hashtags?.map((tag) => (
							<Chip className="mr-1 mb-1" readonly key={tag.id} title={tag.content}>
								{tag.content}
							</Chip>
						))}
					</section>

					<hr className="my-4" />

					<div className="d-flex justify-content-between align-items-center">
						<div className="d-flex align-items-center">
							<img src={AvatarImg} alt="user" width="40" className="rounded-circle" />
							<p className="ml-3 mb-0">
								Created by <strong>{catalogue.owner.name}</strong>
							</p>
						</div>
						<div className="d-flex align-items-center">
							<p className="mb-0 mr-2">{likesCount}</p>
							<Button
								variant="ghost"
								hasOnlyIcon
								onClick={() => {
									if (!isAuthenticated) {
										navigate('/login');
										return;
									}

									likeMutation.mutate(catalogue.id);
								}}
							>
								<Icon size="md" name={isLiked ? 'thumbsUpSolid' : 'thumbsUpRegular'} />
							</Button>
							{isPdfExportSupported && (
								<Button variant="ghost" hasOnlyIcon onClick={handleDownloadPdf}>
									<Icon size="md" name="filePdfSolid" />
								</Button>
							)}
							{downloadCount > 0 && <span className="ml-2 text-muted">{downloadCount} downloads</span>}
						</div>
					</div>
				</div>
			</div>

			<div className="row justify-content-center mt-5">
				<div className="col-lg-8">
					{catalogue.items.length > 0 ? (
						<>
							{isOwner && (
								<div className="mt-3 mb-2">
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
								items={catalogue.items as unknown as unknown[]}
								catalogueType={catalogue.type}
								currentUser={currentUser}
								ownerId={catalogue.owner.id}
								editMode={editMode}
								onRemoveItem={(itemId) => removeItemMutation.mutate(itemId)}
							/>
						</>
					) : (
						<p className="text-muted">This catalogue has no items yet.</p>
					)}
				</div>
			</div>

			<div className="row justify-content-center mt-5">
				<div className="col-lg-8">
					<Suspense fallback={null}>
						<LazyCommentsBlock
							readObjectType="catalogue"
							readObjectUuid={catalogue.uuid}
							entityId={catalogue.id}
							entityType={ObjectTemplateType.LIST}
							entityUuid={catalogue.uuid}
						/>
					</Suspense>
				</div>
			</div>

			<DeleteInstanceModal
				controller={deleteModal}
				instanceName={catalogue.title}
				onDelete={() => deleteMutation.mutate({ uuid: catalogue.uuid })}
				isProcessing={deleteMutation.isPending}
				deleteLabel="Yes, Delete Catalogue"
				ariaLabel="Delete catalogue"
			/>
		</div>
	);
};

export default CatalogueContent;
