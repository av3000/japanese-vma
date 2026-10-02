import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { PostTopic } from '@/api/generated/model/postTopic';
import { POST_ROUTES } from '@/api/posts/reads';
import { readPostWriteError, useCreatePostMutation, type PostWriteFailure } from '@/api/posts/writes';
import { PostForm, type PostFormValues } from '@/components/features/community/PostForm';
import { buildPostCreatePayload } from '@/components/features/community/PostForm/postFormSchema';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';

export default function PostFormPage() {
	const navigate = useNavigate();

	const [failure, setFailure] = useState<PostWriteFailure | null>(null);

	const initialValues = useMemo<PostFormValues>(
		() => ({ title: '', content: '', topic: PostTopic.NUMBER_1, tags: [] }),
		[],
	);

	const createMutation = useCreatePostMutation();

	const handleSubmit = (values: PostFormValues) => {
		setFailure(null);

		createMutation.mutate(buildPostCreatePayload(values), {
			// The write seam has already seeded the detail cache, so this navigation renders the new
			// Post without a second GET.
			onSuccess: (post) => navigate(POST_ROUTES.detail(post.uuid)),
			onError: (error) => setFailure(readPostWriteError(error)),
		});
	};

	return (
		<FormPage title="New post" backLink={{ to: POST_ROUTES.list, label: 'Community' }}>
			<PostForm
				initialValues={initialValues}
				onSubmit={handleSubmit}
				isSubmitting={createMutation.isPending}
				submitLabel="Publish post"
				failure={failure}
				cancel={
					<Button variant="ghost" to={POST_ROUTES.list}>
						Cancel
					</Button>
				}
			/>
		</FormPage>
	);
}
