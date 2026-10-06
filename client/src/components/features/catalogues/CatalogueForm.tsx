import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { Controller, useForm, useWatch, type FieldPath } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import type { ApiError } from '@/api/apiError';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { ChoiceGroup, FormField, Input, type ChoiceOption } from '@/components/shared/FormControls';
import { FormCard, FormLayout, FormNote } from '@/components/shared/FormPage';
import { InputTags } from '@/components/shared/InputTags';
import { getFieldErrorMessages } from '@/helpers/formErrors';
import { NO_CHANGES_MESSAGE, useApiErrorInForm } from '@/hooks/useApiErrorInForm';
import { CATALOGUE_TYPE_OPTIONS, type CustomCatalogueType } from '@/shared/constants/catalogues';
import { VISIBILITY_OPTIONS } from '@/shared/constants/visibility';
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

/** Visual order: server errors focus the first of these that failed. */
const CATALOGUE_FORM_FIELDS: readonly FieldPath<CatalogueFormValues>[] = ['title', 'type', 'tags', 'publicity'];

const TYPE_GLYPHS: Record<CustomCatalogueType, string> = { 5: '部', 6: '漢', 7: '語', 8: '文', 9: '記' };

const TYPE_OPTIONS: ReadonlyArray<ChoiceOption<CustomCatalogueType>> = CATALOGUE_TYPE_OPTIONS.map((option) => ({
	value: option.value,
	// The constants say "Kanjis"; the form speaks of kanji as a set.
	label: option.label === 'Kanjis' ? 'Kanji' : option.label,
	glyph: TYPE_GLYPHS[option.value],
}));

export const TYPE_LOCKED_HINT = "This catalogue has items, so its type can't be changed.";
const TYPE_HINT = "The type decides what you can add. It can't be changed once the catalogue has items.";

interface CatalogueFormProps {
	initialValues: CatalogueFormValues;
	onSubmit: (values: CatalogueFormValues, meta: CatalogueFormSubmitMeta) => void;
	isSubmitting?: boolean;
	submitLabel: string;
	/** The last rejected save, from `parseApiError`. */
	apiError?: ApiError | null;
	/** Edit forms: an unchanged form says so instead of sending an empty update. */
	requireChanges?: boolean;
	/** The catalogue already holds items, so the server refuses a type change. */
	isTypeLocked?: boolean;
	/** Body of the "What happens next" note. */
	note?: ReactNode;
	/** The Cancel control. */
	cancel: ReactNode;
}

export const CatalogueForm = ({
	initialValues,
	onSubmit,
	isSubmitting = false,
	submitLabel,
	apiError,
	requireChanges = false,
	isTypeLocked = false,
	note,
	cancel,
}: CatalogueFormProps) => {
	const schema = useMemo(() => buildCatalogueFormSchema(), []);
	const [showNoChanges, setShowNoChanges] = useState(false);

	const {
		register,
		control,
		handleSubmit,
		reset,
		setError,
		formState: { errors, dirtyFields, isDirty },
	} = useForm<CatalogueFormValues>({
		defaultValues: initialValues,
		mode: 'onTouched',
		resolver: zodResolver(schema),
	});

	const title = useWatch({ control, name: 'title' }) ?? '';

	useEffect(() => {
		reset(initialValues);
	}, [initialValues, reset]);

	useEffect(() => {
		if (isDirty) setShowNoChanges(false);
	}, [isDirty]);

	const alertMessage = useApiErrorInForm(apiError, setError, CATALOGUE_FORM_FIELDS);

	const onValidSubmit = (values: CatalogueFormValues) => {
		if (requireChanges && !isDirty) {
			setShowNoChanges(true);
			return;
		}

		setShowNoChanges(false);
		const dirtyKeys = Object.keys(dirtyFields) as CatalogueFormField[];
		// A locked type never changes, so it is never sent.
		onSubmit(values, { dirtyKeys: isTypeLocked ? dirtyKeys.filter((key) => key !== 'type') : dirtyKeys });
	};

	const alert = showNoChanges ? (
		<Alert tone="info">{NO_CHANGES_MESSAGE}</Alert>
	) : alertMessage ? (
		<Alert tone="danger">{alertMessage}</Alert>
	) : null;

	return (
		<form onSubmit={handleSubmit(onValidSubmit)} noValidate>
			<FormLayout
				alert={alert}
				actions={
					<>
						<Button type="submit" variant="primary" isLoading={isSubmitting}>
							{submitLabel}
						</Button>
						{cancel}
					</>
				}
			>
				<FormCard>
					<FormField
						label="Title"
						counter={`${title.length} / ${MAX_CATALOGUE_TITLE_LENGTH}`}
						error={getFieldErrorMessages(errors.title)}
					>
						{(fieldControl) => (
							<Input
								maxLength={MAX_CATALOGUE_TITLE_LENGTH}
								required
								{...fieldControl}
								{...register('title')}
							/>
						)}
					</FormField>

					<Controller
						control={control}
						name="type"
						render={({ field }) => (
							<ChoiceGroup
								legend="Type"
								name={field.name}
								options={TYPE_OPTIONS}
								value={field.value as CustomCatalogueType}
								onChange={field.onChange}
								onBlur={field.onBlur}
								inputRef={field.ref}
								disabled={isTypeLocked}
								hint={isTypeLocked ? TYPE_LOCKED_HINT : TYPE_HINT}
								error={getFieldErrorMessages(errors.type)}
							/>
						)}
					/>

					<FormField
						label="Tags"
						hint={`Up to ${MAX_CATALOGUE_TAGS} tags, ${MAX_CATALOGUE_TAG_LENGTH} characters each. Press Enter, comma or space to add one.`}
						error={getFieldErrorMessages(errors.tags)}
					>
						{(fieldControl) => (
							<Controller
								control={control}
								name="tags"
								render={({ field }) => (
									<InputTags
										ref={field.ref}
										id={fieldControl.id}
										aria-describedby={fieldControl['aria-describedby']}
										isInvalid={fieldControl.isInvalid}
										value={field.value}
										onChange={field.onChange}
										onBlur={field.onBlur}
										maxTags={MAX_CATALOGUE_TAGS}
										maxTagLength={MAX_CATALOGUE_TAG_LENGTH}
									/>
								)}
							/>
						)}
					</FormField>

					<Controller
						control={control}
						name="publicity"
						render={({ field }) => (
							<ChoiceGroup
								legend="Visibility"
								name={field.name}
								options={VISIBILITY_OPTIONS}
								value={field.value ? 'public' : 'private'}
								onChange={(next) => field.onChange(next === 'public')}
								onBlur={field.onBlur}
								inputRef={field.ref}
								error={getFieldErrorMessages(errors.publicity)}
							/>
						)}
					/>
				</FormCard>
				{note ? <FormNote title="What happens next">{note}</FormNote> : null}
			</FormLayout>
		</form>
	);
};
