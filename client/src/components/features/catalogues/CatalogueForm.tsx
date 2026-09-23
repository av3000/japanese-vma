import { useEffect, useId, useMemo } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/shared/Button';
import { Field, FieldMessage, Input, Label, Select } from '@/components/shared/FormControls';
import { InputTags } from '@/components/shared/InputTags';
import { Stack } from '@/components/shared/layout';
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
	const fieldId = useId();
	const titleId = `${fieldId}-title`;
	const typeId = `${fieldId}-type`;
	const tagsId = `${fieldId}-tags`;
	const publicityId = `${fieldId}-publicity`;
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
		<Stack as="form" gap="md" onSubmit={handleSubmit(onValidSubmit)}>
			<Field>
				<Label htmlFor={titleId}>Title</Label>
				<Input
					id={titleId}
					maxLength={MAX_CATALOGUE_TITLE_LENGTH}
					isInvalid={Boolean(errors.title)}
					{...titleField}
				/>
				<FieldMessage tone="error">{errors.title?.message}</FieldMessage>
			</Field>

			<Field>
				<Label htmlFor={typeId}>Type</Label>
				<Controller
					control={control}
					name="type"
					render={({ field }) => (
						<Select
							id={typeId}
							isInvalid={Boolean(errors.type)}
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
						</Select>
					)}
				/>
				<FieldMessage tone="error">{errors.type?.message}</FieldMessage>
			</Field>

			<Field>
				<Label htmlFor={tagsId}>Tags</Label>
				<Controller
					control={control}
					name="tags"
					render={({ field }) => (
						<InputTags
							id={tagsId}
							value={field.value}
							onChange={(nextTags) => {
								clearErrors(['tags', 'root'] as never);
								field.onChange(nextTags);
							}}
							maxTags={MAX_CATALOGUE_TAGS}
							maxTagLength={MAX_CATALOGUE_TAG_LENGTH}
							showTagLengthCounter
						/>
					)}
				/>
				<FieldMessage tone="error">{errors.tags?.message}</FieldMessage>
			</Field>

			<Field>
				<Label htmlFor={publicityId}>Publicity</Label>
				<Controller
					control={control}
					name="publicity"
					render={({ field }) => (
						<Select
							id={publicityId}
							isInvalid={Boolean(errors.publicity)}
							value={field.value ? '1' : '0'}
							onChange={(event) => {
								clearErrors(['publicity', 'root'] as never);
								field.onChange(event.target.value === '1');
							}}
						>
							<option value="1">Public</option>
							<option value="0">Private</option>
						</Select>
					)}
				/>
				<FieldMessage tone="error">{errors.publicity?.message}</FieldMessage>
			</Field>

			<div>
				<Button
					type="submit"
					variant="outline"
					disabled={isSubmitting || (disableSubmitWhenUnchanged && !isDirty) || !isValid}
				>
					{isSubmitting ? 'Saving...' : submitLabel}
				</Button>
			</div>

			<FieldMessage tone="error">{statusMessage}</FieldMessage>
			<FieldMessage tone="error">{errors.root?.message}</FieldMessage>
		</Stack>
	);
};
