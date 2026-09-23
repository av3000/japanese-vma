import { useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
	canMutateSentence,
	isImportedSentence,
	readSentenceWriteError,
	useUpdateSentenceMutation,
} from '@/api/sentences/authoring';
import { useSentenceQuery } from '@/api/sentences/details';
import { SentenceForm, type SentenceFormValues } from '@/components/features/japanese/sentence/SentenceForm';
import { buildSentenceWritePayload } from '@/components/features/japanese/sentence/SentenceForm/sentenceFormSchema';
import { Alert } from '@/components/shared/Alert';
import { Link } from '@/components/shared/Link';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import styles from '../japaneseFormPage.module.css';

const BackToSentences = () => (
	<div>
		<Link to="/sentences">Back</Link>
	</div>
);

export default function SentenceEditPage() {
	const { sentence_id } = useParams<{ sentence_id: string }>();
	const navigate = useNavigate();
	const { user } = useAuth();

	const { data: sentence, isLoading, isError } = useSentenceQuery(sentence_id);

	const [serverErrors, setServerErrors] = useState<Record<string, string[]> | null>(null);
	const [status, setStatus] = useState<string | null>(null);

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
		return (
			<Container className={styles.page}>
				<Alert tone="danger">Sentence could not be loaded.</Alert>
			</Container>
		);
	}

	if (isImportedSentence(sentence)) {
		return (
			<Container className={styles.page}>
				<Stack gap="lg">
					<Alert tone="danger">Imported sentences cannot be edited.</Alert>
					<BackToSentences />
				</Stack>
			</Container>
		);
	}

	if (!canMutateSentence(user, sentence)) {
		return (
			<Container className={styles.page}>
				<Stack gap="lg">
					<Alert tone="danger">You do not have permission to edit this sentence.</Alert>
					<BackToSentences />
				</Stack>
			</Container>
		);
	}

	const handleSubmit = (values: SentenceFormValues) => {
		setStatus(null);
		setServerErrors(null);

		updateMutation.mutate(buildSentenceWritePayload(values), {
			onSuccess: (updated) => navigate(`/sentence/${updated.uuid}`),
			onError: (error) => {
				const failure = readSentenceWriteError(error);

				setServerErrors(failure.kind === 'validation' ? failure.errors : null);
				setStatus(failure.message);
			},
		});
	};

	return (
		<Container className={styles.page}>
			<Stack gap="lg">
				<BackToSentences />
				<h2>Edit sentence</h2>
				<div className={styles.formArea}>
					<SentenceForm
						initialValues={initialValues}
						onSubmit={handleSubmit}
						isSubmitting={updateMutation.isPending}
						submitLabel="Update"
						serverErrors={serverErrors}
						statusMessage={status}
						disableSubmitWhenUnchanged
					/>
				</div>
			</Stack>
		</Container>
	);
}
