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
import { Link } from '@/components/shared/Link';
import { PageLoading } from '@/components/shared/PageLoading';
import { useAuth } from '@/hooks/useAuth';

const BackToSentences = () => (
	<div className="mt-4">
		<Link to="/sentences" className="tag-link">
			Back
		</Link>
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
		return <div className="container mt-5 text-danger">Sentence could not be loaded.</div>;
	}

	if (isImportedSentence(sentence)) {
		return (
			<div className="container mt-5">
				<p className="text-danger">Imported sentences cannot be edited.</p>
				<BackToSentences />
			</div>
		);
	}

	if (!canMutateSentence(user, sentence)) {
		return (
			<div className="container mt-5">
				<p className="text-danger">You do not have permission to edit this sentence.</p>
				<BackToSentences />
			</div>
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
		<div className="container">
			<BackToSentences />
			<h2 className="mt-4">Edit sentence</h2>
			<div className="row justify-content-lg-center text-center">
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
		</div>
	);
}
