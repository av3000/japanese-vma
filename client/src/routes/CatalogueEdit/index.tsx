import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useCatalogueQuery } from '@/api/catalogues/hooks/useCatalogueQuery';
import {
	resolveLegacyCatalogueIdentity,
	stringifyCatalogueTags,
	updateCatalogue,
	type UpdateCataloguePayload,
} from '@/api/catalogues/catalogues';
import Spinner from '@/assets/images/spinner.gif';
import {
	CatalogueForm,
	type CatalogueFormSubmitMeta,
	type CatalogueFormValues,
} from '@/components/features/catalogues/CatalogueForm';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';
import { CATALOGUE_ROUTES, isCatalogueRouteUuid } from '@/shared/constants/catalogues';

const buildCatalogueUpdatePayload = (values: CatalogueFormValues, dirtyKeys: string[]): UpdateCataloguePayload => {
	const payload: UpdateCataloguePayload = {};

	if (dirtyKeys.includes('title')) {
		payload.title = values.title.trim();
	}
	if (dirtyKeys.includes('type')) {
		payload.type = values.type;
	}
	if (dirtyKeys.includes('publicity')) {
		payload.publicity = values.publicity;
	}
	if (dirtyKeys.includes('tags')) {
		payload.tags = stringifyCatalogueTags(values.tags);
	}

	return payload;
};

const CatalogueEditPage = () => {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const [serverErrors, setServerErrors] = useState<Record<string, string[]> | null>(null);
	const [status, setStatus] = useState<string | null>(null);
	const hasUuidParam = Boolean(catalogueId && isCatalogueRouteUuid(catalogueId));

	const legacyIdentityQuery = useQuery({
		queryKey: ['catalogue-edit-legacy-identity', catalogueId],
		queryFn: () => resolveLegacyCatalogueIdentity(catalogueId as string),
		enabled: Boolean(catalogueId && !hasUuidParam),
		retry: false,
	});

	useEffect(() => {
		if (legacyIdentityQuery.data?.uuid) {
			navigate(CATALOGUE_ROUTES.edit(legacyIdentityQuery.data.uuid), { replace: true });
		}
	}, [legacyIdentityQuery.data?.uuid, navigate]);

	const resolvedUuid = hasUuidParam ? catalogueId : legacyIdentityQuery.data?.uuid;
	const { data: catalogue, isPending, isError } = useCatalogueQuery(resolvedUuid, Boolean(resolvedUuid));

	const mutation = useMutation({
		mutationFn: ({ uuid, payload }: { uuid: string; payload: UpdateCataloguePayload }) => updateCatalogue(uuid, payload),
		onSuccess: (updatedCatalogue) => {
			setStatus(null);
			setServerErrors(null);
			queryClient.invalidateQueries({ queryKey: ['catalogues'] });
			queryClient.invalidateQueries({ queryKey: ['catalogue', updatedCatalogue.uuid] });
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

	if (!catalogueId || legacyIdentityQuery.isPending || (isPending && !catalogue)) {
		return (
			<div className="container text-center mt-5">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
	}

	if (legacyIdentityQuery.isError || isError || !catalogue || !resolvedUuid) {
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
						const payload = buildCatalogueUpdatePayload(values, meta.dirtyKeys);
						setStatus(null);
						setServerErrors(null);
						mutation.mutate({ uuid: resolvedUuid, payload });
					}}
				/>
			</div>
		</div>
	);
};

export default CatalogueEditPage;
