import React, { useEffect, useState } from 'react';
import { type FieldError, useForm } from 'react-hook-form';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { zodResolver } from '@hookform/resolvers/zod';
import { parseAuthError } from '@/api/auth/authError';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { FormField, Input } from '@/components/shared/FormControls';
import { Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { AuthCard } from '../AuthCard';
import { resolveReturnTo } from '../returnTo';
import { registerSchema, type RegisterValues } from './registerSchema';

const FIELDS = ['name', 'email', 'password', 'password_confirmation'] as const;

/**
 * Every message on a field. `criteriaMode: 'all'` collects each broken rule under `types`, keyed by
 * issue code, with repeated codes gathered into an array; server errors arrive the same way.
 */
const messagesOf = (error: FieldError | undefined): string[] => {
	if (!error) return [];
	if (!error.types) return error.message ? [error.message] : [];

	return Object.values(error.types).flatMap((value) =>
		typeof value === 'string' ? [value] : Array.isArray(value) ? value : [],
	);
};

const RegisterForm: React.FC = () => {
	const navigate = useNavigate();
	const location = useLocation();
	const { register: registerAccount, isAuthenticated } = useAuth();
	const [generalError, setGeneralError] = useState<string | null>(null);

	const {
		register,
		handleSubmit,
		setError,
		formState: { errors, isSubmitting },
	} = useForm<RegisterValues>({
		resolver: zodResolver(registerSchema),
		criteriaMode: 'all',
		defaultValues: { name: '', email: '', password: '', password_confirmation: '' },
	});

	useEffect(() => {
		if (isAuthenticated) {
			navigate(resolveReturnTo(location.state), { replace: true });
		}
	}, [isAuthenticated, location.state, navigate]);

	const onSubmit = async (values: RegisterValues) => {
		setGeneralError(null);

		try {
			await registerAccount(values);
		} catch (error) {
			const authError = parseAuthError(error);

			setGeneralError(authError.message);

			let focused = false;
			for (const field of FIELDS) {
				const messages = authError.fieldErrors[field];

				if (messages?.length) {
					setError(
						field,
						{ type: 'server', message: messages[0], types: { server: messages } },
						{ shouldFocus: !focused },
					);
					focused = true;
				}
			}
		}
	};

	return (
		<AuthCard
			title="Create your account"
			alert={generalError ? <Alert tone="danger">{generalError}</Alert> : null}
			footer={
				<>
					Already have an account?{' '}
					<Link to="/login" state={location.state}>
						Log in
					</Link>
				</>
			}
		>
			<form onSubmit={handleSubmit(onSubmit)} noValidate>
				<Stack gap="md">
					<FormField
						label="Username"
						id="name"
						hint="Letters, numbers, underscores and hyphens. Other learners see this name."
						error={messagesOf(errors.name)}
					>
						{(control) => <Input autoComplete="nickname" {...control} {...register('name')} />}
					</FormField>

					<FormField label="Email" id="email" error={messagesOf(errors.email)}>
						{(control) => <Input type="email" autoComplete="email" {...control} {...register('email')} />}
					</FormField>

					<FormField
						label="Password"
						id="password"
						hint="At least 8 characters, with upper- and lowercase letters, a number and a symbol. Passwords found in known data breaches are rejected."
						error={messagesOf(errors.password)}
					>
						{(control) => (
							<Input type="password" autoComplete="new-password" {...control} {...register('password')} />
						)}
					</FormField>

					<FormField
						label="Confirm password"
						id="password_confirmation"
						error={messagesOf(errors.password_confirmation)}
					>
						{(control) => (
							<Input
								type="password"
								autoComplete="new-password"
								{...control}
								{...register('password_confirmation')}
							/>
						)}
					</FormField>

					<Button type="submit" variant="primary" isFullWidth isLoading={isSubmitting}>
						Sign up
					</Button>
				</Stack>
			</form>
		</AuthCard>
	);
};

export default RegisterForm;
