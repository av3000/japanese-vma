import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { readSentenceWriteError, useCreateSentenceMutation } from '@/api/sentences/authoring';
import { SentenceForm, type SentenceFormValues } from '@/components/features/japanese/sentence/SentenceForm';
import { buildSentenceWritePayload } from '@/components/features/japanese/sentence/SentenceForm/sentenceFormSchema';
import { Link } from '@/components/shared/Link';
import { Container, Stack } from '@/components/shared/layout';
import styles from '../japaneseFormPage.module.css';

export default function SentenceCreatePage() {
	const navigate = useNavigate();

	const [serverErrors, setServerErrors] = useState<Record<string, string[]> | null>(null);
	const [status, setStatus] = useState<string | null>(null);

	const initialValues = useMemo<SentenceFormValues>(() => ({ content: '' }), []);

	const createMutation = useCreateSentenceMutation();

	const handleSubmit = (values: SentenceFormValues) => {
		setStatus(null);
		setServerErrors(null);

		createMutation.mutate(buildSentenceWritePayload(values), {
			onSuccess: (sentence) => navigate(`/sentence/${sentence.uuid}`),
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
				<div>
					<Link to="/sentences">Back</Link>
				</div>
				<h2>New sentence</h2>
				<div className={styles.formArea}>
					<SentenceForm
						initialValues={initialValues}
						onSubmit={handleSubmit}
						isSubmitting={createMutation.isPending}
						submitLabel="Create"
						serverErrors={serverErrors}
						statusMessage={status}
					/>
				</div>
			</Stack>
		</Container>
	);
}
