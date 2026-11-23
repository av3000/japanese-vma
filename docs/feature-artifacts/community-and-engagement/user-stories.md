# Community and Engagement — User Stories

> **Status:** Stories grouped by verified and target capability; community posts verified on v1
> **Last reviewed:** 2026-09-19
> **Evidence baseline:** Repository working tree inspected on 2026-08-18; Post retirement (RET-POST-01) reflected 2026-09-19
> **Audience:** Product-minded engineers, QA, and planners

## Verified Current Stories

### Comments and likes

- As a visitor, I can read comments on supported public articles and catalogues.
- As an authenticated user, I can add a comment to a supported entity.
- As an authenticated user, I can provide a parent comment ID when creating a reply.
- As an authenticated user, I can toggle my like on a supported entity.
- As a viewer, I can see engagement summaries exposed by migrated resource responses.

### Community posts

- As a visitor, I can browse community posts with keyword, hashtag, topic and sort filters, and open a post detail by UUID or by an old numeric link that is rewritten to the UUID.
- As an authenticated contributor, I can create a post, edit my own post, and delete my own post; deleting removes its comments, likes, views and hashtag links together.
- As an administrator, I can delete another user's post and lock or unlock a post with an explicit state, but I cannot edit another user's post.
- As a reader of a locked post, I can still read its comments; as a commenter I receive a conflict when I try to add a root comment or a reply until the post is unlocked.

### Cross-feature engagement

- As a learner, my views, likes, comments, tags, and downloads can be associated with articles or catalogues.
- As a content owner deleting a catalogue, related engagement records are removed by the owning application transaction.

## Target Stories

The stories that used to sit here - typed comment update/delete, consistent reply inclusion, v1 post reads and writes, explicit moderation authorization, and shared generated like/comment contracts - are all verified above. No community or engagement target story is open; new capability requests belong in a fresh issue rather than this list.

## Requirement Links

- FR-ENG-001 through FR-ENG-006 in [Product Requirements](../../ai/product-requirements.md)
- NFR-SEC-001 and NFR-CON-001 in [Product Requirements](../../ai/product-requirements.md)
