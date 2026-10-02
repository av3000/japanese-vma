import { useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { PostTopic } from '@/api/generated/model/postTopic';
import { POST_ROUTES, isPostTopic, usePostQuery } from '@/api/posts/reads';
import { canUpdatePost, readPostWriteError, useUpdatePostMutation, type PostWriteFailure } from '@/api/posts/writes';
import { PostForm, type PostFormSubmitMeta, type PostFormValues } from '@/components/features/community/PostForm';
import { buildPostUpdatePayload } from '@/components/features/community/PostForm/postFormSchema';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';
import { PageLoading } from '@/components/shared/PageLoading';
import { useAuth } from '@/hooks/useAuth';

const COMMUNITY_LINK = { to: POST_ROUTES.list, label: 'Community' };

export default function PostEditPage() {
	const { post_id: routeIdentifier } = useParams<{ post_id: string }>();
	const navigate = useNavigate();
	const { user } = useAuth();

	// The detail route seeds this cache entry, so arriving from a Post page costs no extra request.
	const { data: post, isLoading, isError } = usePostQuery(routeIdentifier);

	const [failure, setFailure] = useState<PostWriteFailure | null>(null);

	const initialValues = useMemo<PostFormValues>(
		() => ({
			title: post?.title ?? '',
			content: post?.content ?? '',
			// A legacy Post can carry a code outside the canonical vocabulary; the form has to open on
			// a value the select can actually render.
			topic: post && isPostTopic(post.topic) ? post.topic : PostTopic.NUMBER_1,
			tags: post?.hashtags.map((hashtag) => hashtag.content) ?? [],
		}),
		[post],
	);

	// The route param may be a transitional numeric id; writes only accept the UUID.
	const updateMutation = useUpdatePostMutation(post?.uuid ?? '');

	if (isLoading) {
		return <PageLoading family="form" />;
	}

	if (isError || !post) {
		return (
			<FormPage title="Edit post" backLink={COMMUNITY_LINK}>
				<Alert tone="danger">Post could not be loaded.</Alert>
			</FormPage>
		);
	}

	// Mirrors `PostPolicy::canUpdate`: editing is owner-only, so an admin viewing this URL is
	// refused here exactly as the server would refuse the PUT.
	if (!canUpdatePost(user, post.author.id)) {
		return (
			<FormPage title="Edit post" backLink={COMMUNITY_LINK}>
				<Alert tone="danger">You do not have permission to edit this post.</Alert>
			</FormPage>
		);
	}

	const postRoute = POST_ROUTES.detail(post.uuid);

	// The form refuses an unchanged submit itself (`requireChanges`), so dirtyKeys is never empty here.
	const handleSubmit = (values: PostFormValues, { dirtyKeys }: PostFormSubmitMeta) => {
		setFailure(null);

		updateMutation.mutate(buildPostUpdatePayload(values, dirtyKeys), {
			onSuccess: (updated) => navigate(POST_ROUTES.detail(updated.uuid)),
			onError: (error) => setFailure(readPostWriteError(error)),
		});
	};

	return (
		<FormPage title="Edit post" backLink={{ to: postRoute, label: post.title }}>
			<PostForm
				initialValues={initialValues}
				onSubmit={handleSubmit}
				isSubmitting={updateMutation.isPending}
				submitLabel="Save changes"
				failure={failure}
				requireChanges
				cancel={
					<Button variant="ghost" to={postRoute}>
						Cancel
					</Button>
				}
			/>
		</FormPage>
	);
}
