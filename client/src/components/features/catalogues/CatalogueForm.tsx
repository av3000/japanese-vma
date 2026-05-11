import { useEffect, useMemo } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/shared/Button';
import { InputTags } from '@/components/shared/InputTags';
import { CATALOGUE_TYPE_OPTIONS } from '@/shared/constants/catalogues';
import {
	buildCatalogueFormSchema,
	MAX_CATALOGUE_TAG_LENGTH,
	MAX_CATALOGUE_TAGS,
	MAX_CATALOGUE_TITLE_LENGTH,
	type CatalogueFormValues,
} from './catalogueFormSchema';

export type { CatalogueFormValues } from './catalogueFormSchema';

type CatalogueFormField = Extract<keyof CatalogueFormValues, string>;

export type CatalogueFormSubmitMeta = {
	dirtyKeys: CatalogueFormField[];
};

interface CatalogueFormProps {
	initialValues: CatalogueFormValues;
	onSubmit: (values: CatalogueFormValues, meta: CatalogueFormSubmitMeta) => void;
	isSubmitting?: boolean;
	submitLabel: string;
	serverErrors?: Record<string, string[]> | null;
	statusMessage?: string | null;
	disableSubmitWhenUnchanged?: boolean;
}

const isCatalogueField = (field: string): field is CatalogueFormField => {
	return field === 'title' || field === 'type' || field === 'publicity' || field === 'tags';
};

export const CatalogueForm = ({
	initialValues,
	onSubmit,
	isSubmitting = false,
	submitLabel,
	serverErrors,
	statusMessage,
	disableSubmitWhenUnchanged = false,
}: CatalogueFormProps) => {
	const schema = useMemo(() => buildCatalogueFormSchema(), []);
	const {
		register,
		control,
		handleSubmit,
		reset,
		setError,
		clearErrors,
		formState: { errors, dirtyFields, isDirty, isValid },
	} = useForm<CatalogueFormValues>({
		defaultValues: initialValues,
		mode: 'onChange',
		resolver: zodResolver(schema),
	});

	useEffect(() => {
		reset(initialValues);
	}, [initialValues, reset]);

	useEffect(() => {
		if (!serverErrors) {
			return;
		}

		let generalError: string | null = null;
		for (const [field, messages] of Object.entries(serverErrors)) {
			const message = messages?.[0];
			if (!message) continue;

			if (isCatalogueField(field)) {
				setError(field, { type: 'server', message });
				continue;
			}

			generalError ??= message;
		}

		if (generalError) {
			setError('root' as never, { type: 'server', message: generalError });
		}
	}, [serverErrors, setError]);

	const onValidSubmit = (values: CatalogueFormValues) => {
		const dirtyKeys = Object.keys(dirtyFields) as CatalogueFormField[];
		onSubmit(values, { dirtyKeys });
	};

	const titleField = register('title', {
		onChange: () => clearErrors(['title', 'root'] as never),
	});

	return (
		<form onSubmit={handleSubmit(onValidSubmit)} className="col-12">
			<h4>Title</h4>
			<input className="form-control" maxLength={MAX_CATALOGUE_TITLE_LENGTH} {...titleField} />
			{errors.title?.message && <div className="text-danger mt-2">{errors.title.message}</div>}

			<h4 className="mt-3">Type</h4>
			<Controller
				control={control}
				name="type"
				render={({ field }) => (
					<select
						className="form-control"
						value={field.value}
						onChange={(event) => {
							clearErrors(['type', 'root'] as never);
							field.onChange(Number(event.target.value));
						}}
					>
						{CATALOGUE_TYPE_OPTIONS.map((option) => (
							<option key={option.value} value={option.value}>
								{option.label}
							</option>
						))}
					</select>
				)}
			/>
			{errors.type?.message && <div className="text-danger mt-2">{errors.type.message}</div>}

			<h4 className="mt-3">Tags</h4>
			<Controller
				control={control}
				name="tags"
				render={({ field }) => (
					<InputTags
						value={field.value}
						onChange={(nextTags) => {
							clearErrors(['tags', 'root'] as never);
							field.onChange(nextTags);
						}}
						hideLabel
						label="Tags"
						maxTags={MAX_CATALOGUE_TAGS}
						maxTagLength={MAX_CATALOGUE_TAG_LENGTH}
						showTagLengthCounter
					/>
				)}
			/>
			{errors.tags?.message && <div className="text-danger mt-2">{errors.tags.message}</div>}

			<h4 className="mt-3">Publicity</h4>
			<Controller
				control={control}
				name="publicity"
				render={({ field }) => (
					<select
						className="form-control"
						value={field.value ? '1' : '0'}
						onChange={(event) => {
							clearErrors(['publicity', 'root'] as never);
							field.onChange(event.target.value === '1');
						}}
					>
						<option value="1">Public</option>
						<option value="0">Private</option>
					</select>
				)}
			/>
			{errors.publicity?.message && <div className="text-danger mt-2">{errors.publicity.message}</div>}

			<div className="mt-4">
				<Button
					type="submit"
					variant="outline"
					disabled={isSubmitting || (disableSubmitWhenUnchanged && !isDirty) || !isValid}
				>
					{isSubmitting ? 'Saving...' : submitLabel}
				</Button>
			</div>

			{statusMessage && <div className="text-danger mt-3">{statusMessage}</div>}
			{errors.root?.message && <div className="text-danger mt-3">{errors.root.message}</div>}
		</form>
	);
};
