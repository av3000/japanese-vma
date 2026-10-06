import React, { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { parseAuthError } from '@/api/auth/authError';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { FormField, Input } from '@/components/shared/FormControls';
import { Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { AuthCard } from '../AuthCard';
import { resolveReturnTo } from '../returnTo';

// No strength rules here: accounts created before the current password policy must still sign in.
const loginSchema = z.object({
	email: z.string().trim().min(1, 'Enter your email.').email('Enter a valid email address.'),
	password: z.string().min(1, 'Enter your password.'),
});

type LoginValues = z.infer<typeof loginSchema>;

const LoginForm: React.FC = () => {
	const navigate = useNavigate();
	const location = useLocation();
	const { login, sessionExpired, clearSessionExpired, isAuthenticated } = useAuth();
	const [generalError, setGeneralError] = useState<string | null>(null);

	const {
		register,
		handleSubmit,
		setError,
		formState: { errors, isSubmitting },
	} = useForm<LoginValues>({
		resolver: zodResolver(loginSchema),
		defaultValues: { email: '', password: '' },
	});

	// The one navigation path: a successful login flips `isAuthenticated`, and so does arriving here
	// already signed in.
	useEffect(() => {
		if (isAuthenticated) {
			navigate(resolveReturnTo(location.state), { replace: true });
		}
	}, [isAuthenticated, location.state, navigate]);

	const onSubmit = async (values: LoginValues) => {
		setGeneralError(null);
		clearSessionExpired();

		try {
			await login(values);
		} catch (error) {
			const authError = parseAuthError(error);

			setGeneralError(authError.message);

			let focused = false;
			for (const field of ['email', 'password'] as const) {
				const messages = authError.fieldErrors[field];

				if (messages?.length) {
					setError(field, { type: 'server', message: messages.join(' ') }, { shouldFocus: !focused });
					focused = true;
				}
			}
		}
	};

	const alert = generalError ? (
		<Alert tone="danger">{generalError}</Alert>
	) : sessionExpired ? (
		<Alert tone="warning">Your session expired. Log in again to continue.</Alert>
	) : null;

	return (
		<AuthCard
			title="Welcome back"
			alert={alert}
			footer={
				<>
					New here?{' '}
					<Link to="/register" state={location.state}>
						Create an account
					</Link>
				</>
			}
		>
			<form onSubmit={handleSubmit(onSubmit)} noValidate>
				<Stack gap="md">
					<FormField label="Email" id="email" error={errors.email?.message}>
						{(control) => <Input type="email" autoComplete="email" {...control} {...register('email')} />}
					</FormField>

					<FormField label="Password" id="password" error={errors.password?.message}>
						{(control) => (
							<Input
								type="password"
								autoComplete="current-password"
								{...control}
								{...register('password')}
							/>
						)}
					</FormField>

					<Button type="submit" variant="primary" isFullWidth isLoading={isSubmitting}>
						Log in
					</Button>
				</Stack>
			</form>
		</AuthCard>
	);
};

export default LoginForm;
