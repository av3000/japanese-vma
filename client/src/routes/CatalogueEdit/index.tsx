import { useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { buildUpdateCataloguePayload } from '@/api/catalogues/payloads';
import { readCatalogueWriteError, type CatalogueWriteFailure } from '@/api/catalogues/writes';
import {
	catalogueUpdate,
	getCatalogueIndexQueryKey,
	getCatalogueShowQueryKey,
	useCatalogueShow,
} from '@/api/generated/catalogue/catalogue';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import type { CatalogueResource } from '@/api/generated/model/catalogueResource';
import type { UpdateCatalogueRequest } from '@/api/generated/model/updateCatalogueRequest';
import {
	CatalogueForm,
	type CatalogueFormSubmitMeta,
	type CatalogueFormValues,
} from '@/components/features/catalogues/CatalogueForm';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';
import { PageLoading } from '@/components/shared/PageLoading';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';

const CatalogueEditPage = () => {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const [failure, setFailure] = useState<CatalogueWriteFailure | null>(null);
	const { data, isPending, isError } = useCatalogueShow<CatalogueDetailResource>(catalogueId ?? '', {
		query: {
			enabled: Boolean(catalogueId),
		},
	});
	const catalogue = data;

	const updateMutation = useMutation<CatalogueResource, unknown, { uuid: string; payload: UpdateCatalogueRequest }>({
		mutationFn: ({ uuid, payload }: { uuid: string; payload: UpdateCatalogueRequest }) =>
			catalogueUpdate(uuid, payload),
		onSuccess: (updatedCatalogue) => {
			setFailure(null);
			queryClient.invalidateQueries({ queryKey: getCatalogueIndexQueryKey() });
			queryClient.invalidateQueries({ queryKey: getCatalogueShowQueryKey(updatedCatalogue.uuid) });
			navigate(CATALOGUE_ROUTES.detail(updatedCatalogue.uuid));
		},
		onError: (error) => setFailure(readCatalogueWriteError(error)),
	});

	const initialValues = useMemo<CatalogueFormValues>(() => {
		if (!catalogue) {
			return {
				title: '',
				type: 5,
				publicity: false,
				tags: [],
			};
		}

		return {
			title: catalogue.title,
			type: catalogue.type as CatalogueFormValues['type'],
			publicity: catalogue.publicity === 1,
			tags: catalogue.hashtags.map((tag) => tag.content),
		};
	}, [catalogue]);

	if (!catalogueId || (isPending && !catalogue)) {
		return <PageLoading family="form" />;
	}

	if (isError || !catalogue) {
		return (
			<FormPage title="Edit catalogue" size="sm" backLink={{ to: CATALOGUE_ROUTES.list, label: 'Catalogues' }}>
				<Alert tone="danger">This catalogue could not be loaded. It may have been deleted.</Alert>
			</FormPage>
		);
	}

	const detailRoute = CATALOGUE_ROUTES.detail(catalogue.uuid);

	return (
		<FormPage title="Edit catalogue" size="sm" backLink={{ to: detailRoute, label: catalogue.title }}>
			<CatalogueForm
				initialValues={initialValues}
				isSubmitting={updateMutation.isPending}
				submitLabel="Save changes"
				failure={failure}
				requireChanges
				isTypeLocked={catalogue.items_count > 0}
				cancel={
					<Button variant="ghost" to={detailRoute}>
						Cancel
					</Button>
				}
				onSubmit={(values, meta: CatalogueFormSubmitMeta) => {
					setFailure(null);
					updateMutation.mutate({
						uuid: catalogueId,
						payload: buildUpdateCataloguePayload(values, meta.dirtyKeys),
					});
				}}
			/>
		</FormPage>
	);
};

export default CatalogueEditPage;
