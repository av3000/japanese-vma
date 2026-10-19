---
title: Keep Route Files Thin
impact: MEDIUM
impactDescription: routes stay readable; behaviour lives where it can be tested
tags: repo, routes, composition
---

## Keep Route Files Thin

**Impact: MEDIUM (routes stay readable; behaviour lives where it can be tested)**

A route parses params and search params, wires query and mutation hooks, gates loading and error states, and hands rendering to a feature component. A substantial form or detail body is its own feature component. Filter-to-query mapping lives in a route-local helper, not inside JSX.

**Incorrect (the route owns fetching, pagination, search state and all markup; from the removed `SavedLists` route):**

```tsx
class SavedLists extends Component {
	state = { url: '/lists', lists: [], paginateObject: {}, searchTotal: '', filters: [], isLoading: true };

	componentDidMount() {
		this.fetchLists(this.state.url);
	}

	fetchQuery(queryParams) {
		apiCall({ method: HttpMethod.POST, path: '/lists/search', data: queryParams }).then(/* 40 lines of setState */);
	}

	render() {
		/* filters, list markup, pagination buttons */
	}
}
```

**Correct (`src/routes/ArticleDetails/index.tsx`):**

```tsx
const ArticleDetails: React.FC = () => {
	const { article_id } = useParams<{ article_id: string }>();
	const { data: article, isLoading, isError, error } = useArticleQuery(article_id);

	if (isLoading) {
		return <PageLoading family="detail" visual={<ArticleDetailsSkeleton />} />;
	}

	if (isError || !article) {
		return <DetailUnavailable message={unavailableMessage(error, 'article')} backTo="/articles" backLabel="Back to Articles" />;
	}

	return <ArticleContent article={article} />;
};
```

Source: `client/AGENTS.md` §2.
