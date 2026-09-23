import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { PostTopic } from '@/api/generated/model/postTopic';
import { POST_ROUTES } from '@/api/posts/reads';
import { readPostWriteError, useCreatePostMutation } from '@/api/posts/writes';
import { PostForm, type PostFormValues } from '@/components/features/community/PostForm';
import { buildPostCreatePayload } from '@/components/features/community/PostForm/postFormSchema';
import { Link } from '@/components/shared/Link';
import { Container, Stack } from '@/components/shared/layout';
import styles from './PostForm.module.css';

export default function PostFormPage() {
	const navigate = useNavigate();

	const [serverErrors, setServerErrors] = useState<Record<string, string[]> | null>(null);
	const [status, setStatus] = useState<string | null>(null);

	const initialValues = useMemo<PostFormValues>(
		() => ({ title: '', content: '', topic: PostTopic.NUMBER_1, tags: [] }),
		[],
	);

	const createMutation = useCreatePostMutation();

	const handleSubmit = (values: PostFormValues) => {
		setStatus(null);
		setServerErrors(null);

		createMutation.mutate(buildPostCreatePayload(values), {
			// The write seam has already seeded the detail cache, so this navigation renders the new
			// Post without a second GET.
			onSuccess: (post) => navigate(POST_ROUTES.detail(post.uuid)),
			onError: (error) => {
				const failure = readPostWriteError(error);

				setServerErrors(failure.kind === 'validation' ? failure.errors : null);
				setStatus(failure.message);
			},
		});
	};

	return (
		<Container size="md" className={styles.page}>
			<Stack gap="lg">
				<div>
					<Link to={POST_ROUTES.list} className="tag-link">
						Back to Community
					</Link>
				</div>
				<h2 className={styles.heading}>New post</h2>
				<PostForm
					initialValues={initialValues}
					onSubmit={handleSubmit}
					isSubmitting={createMutation.isPending}
					submitLabel="Create Post"
					serverErrors={serverErrors}
					statusMessage={status}
				/>
			</Stack>
		</Container>
	);
}
