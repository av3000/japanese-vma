import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { buildCreateCataloguePayload } from '@/api/catalogues/payloads';
import { readCatalogueWriteError, type CatalogueWriteFailure } from '@/api/catalogues/writes';
import { catalogueStore, getCatalogueIndexQueryKey } from '@/api/generated/catalogue/catalogue';
import type { StoreCatalogueRequest } from '@/api/generated/model/storeCatalogueRequest';
import type { UuidCreatedResource } from '@/api/generated/model/uuidCreatedResource';
import { CatalogueForm, type CatalogueFormValues } from '@/components/features/catalogues/CatalogueForm';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';

const CatalogueCreatePage = () => {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const [failure, setFailure] = useState<CatalogueWriteFailure | null>(null);

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
			setFailure(null);
			queryClient.invalidateQueries({ queryKey: getCatalogueIndexQueryKey() });
			navigate(CATALOGUE_ROUTES.detail(uuid));
		},
		onError: (error) => setFailure(readCatalogueWriteError(error)),
	});

	return (
		<FormPage title="New catalogue" size="sm" backLink={{ to: CATALOGUE_ROUTES.list, label: 'Catalogues' }}>
			<CatalogueForm
				initialValues={initialValues}
				isSubmitting={mutation.isPending}
				submitLabel="Create catalogue"
				failure={failure}
				note={<p>Add items from any kanji, word, radical or sentence page with Save to catalogue.</p>}
				cancel={
					<Button variant="ghost" to={CATALOGUE_ROUTES.list}>
						Cancel
					</Button>
				}
				onSubmit={(values) => {
					setFailure(null);
					mutation.mutate(buildCreateCataloguePayload(values));
				}}
			/>
		</FormPage>
	);
};

export default CatalogueCreatePage;
