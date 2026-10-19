---
title: Forms Use react-hook-form With a Zod Schema
impact: MEDIUM
impactDescription: one validation source, typed payloads, server errors mapped to fields
tags: repo, forms, react-hook-form, zod
---

## Forms Use react-hook-form With a Zod Schema

**Impact: MEDIUM (one validation source, typed payloads, server errors mapped to fields)**

New and migrated forms use `react-hook-form` with `zodResolver`, render fields through `FormControls` → `FormField`, and map server validation errors back to fields. Reuse the feature's shared form when one exists; `src/components/features/articles/ArticleForm.tsx` is the reference. Keep the Zod schema and the submit payload aligned with the generated request model. No `useState` form objects, no inline string checks, no untyped payloads.

**Incorrect (from the removed `SavedListForm` route):**

```tsx
const [form, setForm] = useState({ title: '', tags: '', type: '', publicity: '' });

const onSubmit = async () => {
	if (form.title.length < 3) {
		setError('Title too short');
		return;
	}
	const res = await apiCall({ method: 'post', path: `/list`, data: payload });
};
```

**Correct (`ArticleForm.tsx`):**

```tsx
const {
	control,
	handleSubmit,
	setError,
	formState: { isSubmitting },
} = useForm<ArticleFormValues>({
	defaultValues,
	resolver: zodResolver(schema),
});
```

Source: `client/AGENTS.md` §4.
