import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Field, Input, Label } from '@/components/shared/FormControls';
import { Container, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import styles from './Login.module.css';

interface LoginFormProps {
	heading: string;
	buttonText: string;
}

const LoginForm: React.FC<LoginFormProps> = ({ heading, buttonText }) => {
	const [isLoading, setIsLoading] = useState(false);
	const [error, setError] = useState<string | null>(null);
	const navigate = useNavigate();
	const { login, sessionExpired, clearSessionExpired, isAuthenticated } = useAuth();

	useEffect(() => {
		if (isAuthenticated) {
			navigate('/');
		}
	}, [isAuthenticated, navigate]);

	const handleSubmit = async (event) => {
		event.preventDefault();

		const formData = new FormData(event.target);
		setIsLoading(true);
		setError(null);
		clearSessionExpired();

		try {
			await login({ email: formData.get('email') as string, password: formData.get('password') as string });
			navigate('/');
		} catch (err: any) {
			console.error('Login error:', err);
			setError(err.response?.data?.message || err.message || 'Login failed. Please try again.');
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
						Don't have an account yet? <Link to="/register">Create now.</Link>
					</h6>

					{sessionExpired && <Alert tone="warning">Session expired, please login again</Alert>}

					{error && <Alert tone="danger">{error}</Alert>}

					<Field>
						<Label htmlFor="email">Email:</Label>
						<Input id="email" name="email" type="email" required autoComplete="email" />
					</Field>

					<Field>
						<Label htmlFor="password">Password:</Label>
						<Input id="password" name="password" type="password" required autoComplete="current-password" />
					</Field>

					<Button type="submit" variant="outline" className={styles.submit} isLoading={isLoading}>
						{buttonText}
					</Button>
				</Stack>
			</form>
		</Container>
	);
};

export default LoginForm;
