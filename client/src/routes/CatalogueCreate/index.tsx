import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { buildCreateCataloguePayload } from '@/api/catalogues/payloads';
import { catalogueStore, getCatalogueIndexQueryKey } from '@/api/generated/catalogue/catalogue';
import type { StoreCatalogueRequest } from '@/api/generated/model/storeCatalogueRequest';
import type { UuidCreatedResource } from '@/api/generated/model/uuidCreatedResource';
import {
	CatalogueForm,
	type CatalogueFormValues,
} from '@/components/features/catalogues/CatalogueForm';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';

const CatalogueCreatePage = () => {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const [serverErrors, setServerErrors] = useState<Record<string, string[]> | null>(null);
	const [status, setStatus] = useState<string | null>(null);

	const initialValues = useMemo<CatalogueFormValues>(
		() => ({
			title: '',
			type: 5,
			publicity: false,
			tags: [],
		}),
		[],
	);

	const mutation = useMutation<UuidCreatedResource, unknown, StoreCatalogueRequest>({
		mutationFn: (payload: StoreCatalogueRequest) => catalogueStore(payload),
		onSuccess: ({ uuid }) => {
			setStatus(null);
			setServerErrors(null);
			queryClient.invalidateQueries({ queryKey: getCatalogueIndexQueryKey() });
			navigate(CATALOGUE_ROUTES.detail(uuid));
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

	return (
		<div className="container">
			<div className="row justify-content-lg-center text-center">
				<CatalogueForm
					initialValues={initialValues}
					isSubmitting={mutation.isPending}
					submitLabel="Create Catalogue"
					serverErrors={serverErrors}
					statusMessage={status}
					onSubmit={(values) => {
						setStatus(null);
						setServerErrors(null);
						mutation.mutate(buildCreateCataloguePayload(values));
					}}
				/>
			</div>
		</div>
	);
};

export default CatalogueCreatePage;
