import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Field, Input, Label } from '@/components/shared/FormControls';
import { Container, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import styles from './Register.module.css';

interface RegisterFormProps {
	heading: string;
	buttonText: string;
}

const RegisterForm: React.FC<RegisterFormProps> = ({ heading, buttonText }) => {
	const [isLoading, setIsLoading] = useState(false);
	const [error, setError] = useState<string | null>(null);
	const navigate = useNavigate();
	const { register, isAuthenticated } = useAuth();

	useEffect(() => {
		if (isAuthenticated) {
			navigate('/');
		}
	}, [isAuthenticated, navigate]);

	const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
		event.preventDefault();

		const formData = new FormData(event.currentTarget);

		setIsLoading(true);
		setError(null);

		try {
			await register({
				name: formData.get('name') as string,
				email: formData.get('email') as string,
				password: formData.get('password') as string,
				password_confirmation: formData.get('password_confirmation') as string,
			});
			navigate('/');
		} catch (err: any) {
			console.error('Register error:', err);
			setError(err.response?.data?.message || err.message || 'Registration failed. Please try again.');
		} finally {
			setIsLoading(false);
		}
	};

	return (
		<Container size="xs" as="section" className={styles.card}>
			<form onSubmit={handleSubmit}>
				<Stack gap="md">
					<h2 className={styles.title}>{heading}</h2>
					<h6 className={styles.intro}>
						Already have an account? <Link to="/login">Login.</Link>
					</h6>

					{error && <Alert tone="danger">{error}</Alert>}

					<Field>
						<Label htmlFor="name">Username:</Label>
						<Input id="name" name="name" type="text" required autoComplete="username" />
					</Field>

					<Field>
						<Label htmlFor="email">Email:</Label>
						<Input id="email" name="email" type="email" required autoComplete="email" />
					</Field>

					<Field>
						<Label htmlFor="password">Password:</Label>
						<Input id="password" name="password" type="password" required autoComplete="new-password" />
					</Field>

					<Field>
						<Label htmlFor="password_confirmation">Confirm password:</Label>
						<Input
							id="password_confirmation"
							name="password_confirmation"
							type="password"
							required
							autoComplete="new-password"
						/>
					</Field>

					<Button type="submit" variant="outline" className={styles.submit} isLoading={isLoading}>
						{buttonText}
					</Button>
				</Stack>
			</form>
		</Container>
	);
};

export default RegisterForm;
