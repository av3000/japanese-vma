---
title: No Class Components
impact: HIGH
impactDescription: one component model; no legacy lifecycles
tags: repo, components, hooks, legacy
---

## No Class Components

**Impact: HIGH (one component model; no legacy lifecycles)**

**Enforced by ESLint** (`no-restricted-syntax` for classes extending `Component` or `PureComponent`). Reviewers skip this rule; the linter reports it.

New and touched components are function components with hooks. No `extends Component` or `extends PureComponent`, and so no `componentWillMount`, `componentDidMount` or `setState` chains. Server data goes through a query hook (`client-query-dedup`), not a lifecycle method.

**Incorrect (from the removed `SavedLists` route):**

```tsx
class SavedLists extends Component {
	componentDidMount() {
		this.fetchLists(this.state.url);
	}
}
```

**Correct:**

```tsx
const CataloguesList = () => {
	const { catalogues, isPending } = useInfiniteCatalogues({ filters });
	if (isPending) {
		return <PageLoading family="list" />;
	}
	return <LibraryLayout items={catalogues} />;
};
```

Source: `client/AGENTS.md` §2, §7 and §9.
