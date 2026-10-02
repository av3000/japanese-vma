import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
	readSentenceWriteError,
	useCreateSentenceMutation,
	type SentenceWriteFailure,
} from '@/api/sentences/authoring';
import { SentenceForm, type SentenceFormValues } from '@/components/features/japanese/sentence/SentenceForm';
import { buildSentenceWritePayload } from '@/components/features/japanese/sentence/SentenceForm/sentenceFormSchema';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';

const SENTENCES_ROUTE = '/sentences';

export default function SentenceCreatePage() {
	const navigate = useNavigate();

	const [failure, setFailure] = useState<SentenceWriteFailure | null>(null);

	const initialValues = useMemo<SentenceFormValues>(() => ({ content: '' }), []);

	const createMutation = useCreateSentenceMutation();

	const handleSubmit = (values: SentenceFormValues) => {
		setFailure(null);

		createMutation.mutate(buildSentenceWritePayload(values), {
			onSuccess: (sentence) => navigate(`/sentence/${sentence.uuid}`),
			onError: (error) => setFailure(readSentenceWriteError(error)),
		});
	};

	return (
		<FormPage title="New sentence" size="sm" backLink={{ to: SENTENCES_ROUTE, label: 'Sentences' }}>
			<SentenceForm
				initialValues={initialValues}
				onSubmit={handleSubmit}
				isSubmitting={createMutation.isPending}
				submitLabel="Create sentence"
				failure={failure}
				cancel={
					<Button variant="ghost" to={SENTENCES_ROUTE}>
						Cancel
					</Button>
				}
			/>
		</FormPage>
	);
}
