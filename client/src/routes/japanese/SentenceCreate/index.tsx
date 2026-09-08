import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { readSentenceWriteError, useCreateSentenceMutation } from '@/api/sentences/authoring';
import { SentenceForm, type SentenceFormValues } from '@/components/features/japanese/sentence/SentenceForm';
import { buildSentenceWritePayload } from '@/components/features/japanese/sentence/SentenceForm/sentenceFormSchema';
import { Link } from '@/components/shared/Link';

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
		<div className="container">
			<div className="mt-4">
				<Link to="/sentences" className="tag-link">
					Back
				</Link>
			</div>
			<h2 className="mt-4">New sentence</h2>
			<div className="row justify-content-lg-center text-center">
				<SentenceForm
					initialValues={initialValues}
					onSubmit={handleSubmit}
					isSubmitting={createMutation.isPending}
					submitLabel="Create"
					serverErrors={serverErrors}
					statusMessage={status}
				/>
			</div>
		</div>
	);
}
