import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
	createCatalogue,
	stringifyCatalogueTags,
	type CreateCataloguePayload,
} from '@/api/catalogues/catalogues';
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

	const mutation = useMutation({
		mutationFn: (payload: CreateCataloguePayload) => createCatalogue(payload),
		onSuccess: ({ uuid }) => {
			setStatus(null);
			setServerErrors(null);
			queryClient.invalidateQueries({ queryKey: ['catalogues'] });
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
						mutation.mutate({
							title: values.title.trim(),
							type: values.type,
							publicity: values.publicity,
							tags: stringifyCatalogueTags(values.tags),
						});
					}}
				/>
			</div>
		</div>
	);
};

export default CatalogueCreatePage;
