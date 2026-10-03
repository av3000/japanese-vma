import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { type ApiError, parseApiError } from '@/api/apiError';
import { useCreateSentenceMutation } from '@/api/sentences/authoring';
import { SentenceForm, type SentenceFormValues } from '@/components/features/japanese/sentence/SentenceForm';
import { buildSentenceWritePayload } from '@/components/features/japanese/sentence/SentenceForm/sentenceFormSchema';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';

const SENTENCES_ROUTE = '/sentences';

export default function SentenceCreatePage() {
	const navigate = useNavigate();

	const [apiError, setApiError] = useState<ApiError | null>(null);

	const initialValues = useMemo<SentenceFormValues>(() => ({ content: '' }), []);

	const createMutation = useCreateSentenceMutation();

	const handleSubmit = (values: SentenceFormValues) => {
		setApiError(null);

		createMutation.mutate(buildSentenceWritePayload(values), {
			onSuccess: (sentence) => navigate(`/sentence/${sentence.uuid}`),
			onError: (error) => setApiError(parseApiError(error)),
		});
	};

	return (
		<FormPage title="New sentence" size="sm" backLink={{ to: SENTENCES_ROUTE, label: 'Sentences' }}>
			<SentenceForm
				initialValues={initialValues}
				onSubmit={handleSubmit}
				isSubmitting={createMutation.isPending}
				submitLabel="Create sentence"
				apiError={apiError}
				cancel={
					<Button variant="ghost" to={SENTENCES_ROUTE}>
						Cancel
					</Button>
				}
			/>
		</FormPage>
	);
}
