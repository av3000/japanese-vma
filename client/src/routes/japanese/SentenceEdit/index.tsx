import { useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { type ApiError, parseApiError } from '@/api/apiError';
import { canMutateSentence, isImportedSentence, useUpdateSentenceMutation } from '@/api/sentences/authoring';
import { useSentenceQuery } from '@/api/sentences/details';
import { SentenceForm, type SentenceFormValues } from '@/components/features/japanese/sentence/SentenceForm';
import { buildSentenceWritePayload } from '@/components/features/japanese/sentence/SentenceForm/sentenceFormSchema';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';
import { PageLoading } from '@/components/shared/PageLoading';
import { useAuth } from '@/hooks/useAuth';

const SENTENCES_LINK = { to: '/sentences', label: 'Sentences' };

const Refusal = ({ message }: { message: string }) => (
	<FormPage title="Edit sentence" size="sm" backLink={SENTENCES_LINK}>
		<Alert tone="danger">{message}</Alert>
	</FormPage>
);

export default function SentenceEditPage() {
	const { sentence_id } = useParams<{ sentence_id: string }>();
	const navigate = useNavigate();
	const { user } = useAuth();

	const { data: sentence, isLoading, isError } = useSentenceQuery(sentence_id);

	const [apiError, setApiError] = useState<ApiError | null>(null);

	const initialValues = useMemo<SentenceFormValues>(
		() => ({ content: sentence?.content ?? '' }),
		[sentence?.content],
	);

	// The route param may be a legacy numeric id; writes only accept the UUID.
	const updateMutation = useUpdateSentenceMutation(sentence?.uuid ?? '');

	if (isLoading) {
		return <PageLoading family="form" />;
	}

	if (isError || !sentence) {
		return <Refusal message="Sentence could not be loaded." />;
	}

	if (isImportedSentence(sentence)) {
		return <Refusal message="Imported sentences cannot be edited." />;
	}

	if (!canMutateSentence(user, sentence)) {
		return <Refusal message="You do not have permission to edit this sentence." />;
	}

	const sentenceRoute = `/sentence/${sentence.uuid}`;

	const handleSubmit = (values: SentenceFormValues) => {
		setApiError(null);

		updateMutation.mutate(buildSentenceWritePayload(values), {
			onSuccess: (updated) => navigate(`/sentence/${updated.uuid}`),
			onError: (error) => setApiError(parseApiError(error)),
		});
	};

	return (
		<FormPage title="Edit sentence" size="sm" backLink={{ to: sentenceRoute, label: 'Sentence' }}>
			<SentenceForm
				initialValues={initialValues}
				onSubmit={handleSubmit}
				isSubmitting={updateMutation.isPending}
				submitLabel="Save changes"
				apiError={apiError}
				requireChanges
				cancel={
					<Button variant="ghost" to={sentenceRoute}>
						Cancel
					</Button>
				}
			/>
		</FormPage>
	);
}
