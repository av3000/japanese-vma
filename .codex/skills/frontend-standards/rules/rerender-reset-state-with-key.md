---
title: Reset State With a key, Not an Effect
impact: MEDIUM
impactDescription: removes a wasted render pass and a frame of stale UI
tags: rerender, key, state, useEffect, reconciliation
---

## Reset State With a key, Not an Effect

To clear component state when a prop changes, give the component a `key`. React treats a changed key as a different component: it discards the old tree and remounts with fresh state, in the same render pass.

Doing it with an effect instead costs an extra render, and the first of those renders shows the *previous* user's data under the new user's name.

**Incorrect (renders stale state, then clears it):**

```tsx
function ProfilePage({ userId }: { userId: string }) {
  const [comment, setComment] = useState('')

  // Runs after the render that already painted the old comment.
  useEffect(() => {
    setComment('')
  }, [userId])

  return <CommentBox value={comment} onChange={setComment} />
}
```

This also only resets the state you remembered to list. Any state held by children stays stale.

**Correct (key the subtree):**

```tsx
export default function ProfilePage({ userId }: { userId: string }) {
  return <Profile userId={userId} key={userId} />
}

function Profile({ userId }: { userId: string }) {
  // Reset automatically on userId change - including all child state.
  const [comment, setComment] = useState('')

  return <CommentBox value={comment} onChange={setComment} />
}
```

The outer component passes the key; the inner component does not know it is being reset. Resetting the whole subtree is the point — scroll position, draft text, and expanded/collapsed flags in children all clear too.

**Do not key on an unstable value.** `key={Math.random()}` or `key={{}}` remounts on every render, throwing away the DOM and all state each time. The key must be the identity of the thing being displayed.

**Partial resets:** when only *some* state should reset, the React docs describe adjusting state directly during render (comparing a stored previous prop to the current one). This is a rarely-appropriate escape hatch and should not be reached for by default — prefer splitting the component so the part that resets can take a `key`, or deriving the value during render so there is no state to reset.

Reference: [Resetting all state when a prop changes](https://react.dev/learn/you-might-not-need-an-effect#resetting-all-state-when-a-prop-changes)

_Source: local addition based on react.dev; not in the upstream Vercel skill._
