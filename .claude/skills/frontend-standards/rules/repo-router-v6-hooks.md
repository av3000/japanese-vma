---
title: Use React Router v6 Hooks, Not v5 Route Props
impact: HIGH
impactDescription: v5 route props do not exist in v6 and fail at runtime
tags: repo, routing, react-router
---

## Use React Router v6 Hooks, Not v5 Route Props

**Impact: HIGH (v5 route props do not exist in v6 and fail at runtime)**

**Enforced by ESLint** (`no-restricted-syntax` for `props.match` and `props.history`) and TypeScript (route elements receive no route props). Reviewers skip this rule; the tools report it.

Read route state with `useParams`, `useNavigate`, `useSearchParams` and `useLocation`. React Router v6 passes no `match`, `history` or `location` props to route elements, so `props.match.params` is `undefined` at runtime.

**Incorrect:**

```tsx
const SavedListDetails = (props) => {
	const listId = props.match.params.list_id;
	const goBack = () => props.history.push('/lists');
};
```

**Correct:**

```tsx
const CatalogueStudy = () => {
	const { uuid } = useParams<{ uuid: string }>();
	const [searchParams, setSearchParams] = useSearchParams();
	const navigate = useNavigate();
};
```

Source: `client/AGENTS.md` §2 and §9.
