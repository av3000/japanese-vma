import { useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { buildUpdateCataloguePayload } from '@/api/catalogues/payloads';
import {
	catalogueUpdate,
	getCatalogueIndexQueryKey,
	getCatalogueShowQueryKey,
	useCatalogueShow,
} from '@/api/generated/catalogue/catalogue';
import type { CatalogueResource } from '@/api/generated/model/catalogueResource';
import type { UpdateCatalogueRequest } from '@/api/generated/model/updateCatalogueRequest';
import Spinner from '@/assets/images/spinner.gif';
import {
	CatalogueForm,
	type CatalogueFormSubmitMeta,
	type CatalogueFormValues,
} from '@/components/features/catalogues/CatalogueForm';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';

const CatalogueEditPage = () => {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const [serverErrors, setServerErrors] = useState<Record<string, string[]> | null>(null);
	const [status, setStatus] = useState<string | null>(null);
	const { data, isPending, isError } = useCatalogueShow(catalogueId ?? '', {
		query: {
			enabled: Boolean(catalogueId),
		},
	});
	const catalogue = data?.catalogue;

	const mutation = useMutation<CatalogueResource, unknown, { uuid: string; payload: UpdateCatalogueRequest }>({
		mutationFn: ({ uuid, payload }: { uuid: string; payload: UpdateCatalogueRequest }) =>
			catalogueUpdate(uuid, payload),
		onSuccess: (updatedCatalogue) => {
			setStatus(null);
			setServerErrors(null);
			queryClient.invalidateQueries({ queryKey: getCatalogueIndexQueryKey() });
			queryClient.invalidateQueries({ queryKey: getCatalogueShowQueryKey(updatedCatalogue.uuid) });
			navigate(CATALOGUE_ROUTES.detail(updatedCatalogue.uuid));
		},
		onError: (error: any) => {
			const data = error?.response?.data;

			if (isHttpValidationProblemDetails(data)) {
				setServerErrors(data.errors);
				setStatus(data.title ?? 'Validation failed');
				return;
			}

			setServerErrors(null);
			setStatus('Something went wrong. Please try again.');
			console.error(error);
		},
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

	return (
		<div className="container">
			<div className="row justify-content-lg-center text-center">
				<CatalogueForm
					initialValues={initialValues}
					isSubmitting={mutation.isPending}
					submitLabel="Update Catalogue"
					serverErrors={serverErrors}
					statusMessage={status}
					disableSubmitWhenUnchanged
					onSubmit={(values, meta: CatalogueFormSubmitMeta) => {
						const payload = buildUpdateCataloguePayload(values, meta.dirtyKeys);
						setStatus(null);
						setServerErrors(null);
						mutation.mutate({ uuid: catalogueId, payload });
					}}
				/>
			</div>
		</div>
	);
};

export default CatalogueEditPage;
