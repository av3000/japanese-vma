import { useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { PostTopic } from '@/api/generated/model/postTopic';
import { POST_ROUTES, isPostTopic, usePostQuery } from '@/api/posts/reads';
import { canUpdatePost, readPostWriteError, useUpdatePostMutation } from '@/api/posts/writes';
import { PostForm, type PostFormSubmitMeta, type PostFormValues } from '@/components/features/community/PostForm';
import { buildPostUpdatePayload } from '@/components/features/community/PostForm/postFormSchema';
import { Link } from '@/components/shared/Link';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import styles from './PostEdit.module.css';

const BackToCommunity = () => (
	<div>
		<Link to={POST_ROUTES.list} className="tag-link">
			Back to Community
		</Link>
	</div>
);

export default function PostEditPage() {
	const { post_id: routeIdentifier } = useParams<{ post_id: string }>();
	const navigate = useNavigate();
	const { user } = useAuth();

	// The detail route seeds this cache entry, so arriving from a Post page costs no extra request.
	const { data: post, isLoading, isError } = usePostQuery(routeIdentifier);

	const [serverErrors, setServerErrors] = useState<Record<string, string[]> | null>(null);
	const [status, setStatus] = useState<string | null>(null);

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
			<Container size="md" className={styles.page}>
				<Stack gap="lg">
					<p className={styles.error}>Post could not be loaded.</p>
					<BackToCommunity />
				</Stack>
			</Container>
		);
	}

	// Mirrors `PostPolicy::canUpdate`: editing is owner-only, so an admin viewing this URL is
	// refused here exactly as the server would refuse the PUT.
	if (!canUpdatePost(user, post.author.id)) {
		return (
			<Container size="md" className={styles.page}>
				<Stack gap="lg">
					<p className={styles.error}>You do not have permission to edit this post.</p>
					<BackToCommunity />
				</Stack>
			</Container>
		);
	}

	const handleSubmit = (values: PostFormValues, { dirtyKeys }: PostFormSubmitMeta) => {
		setStatus(null);
		setServerErrors(null);

		// `UpdatePostRequest` rejects an empty body. The submit button is already disabled while the
		// form is pristine; this keeps a stray submit from turning into a 422.
		if (dirtyKeys.length === 0) {
			navigate(POST_ROUTES.detail(post.uuid));

			return;
		}

		updateMutation.mutate(buildPostUpdatePayload(values, dirtyKeys), {
			onSuccess: (updated) => navigate(POST_ROUTES.detail(updated.uuid)),
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
				<BackToCommunity />
				<h2 className={styles.heading}>Edit post</h2>
				<PostForm
					initialValues={initialValues}
					onSubmit={handleSubmit}
					isSubmitting={updateMutation.isPending}
					submitLabel="Update Post"
					serverErrors={serverErrors}
					statusMessage={status}
					disableSubmitWhenUnchanged
				/>
			</Stack>
		</Container>
	);
}
