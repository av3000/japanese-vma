import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';

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
		<div className="container">
			<div className="row justify-content-md-center text-center mt-5">
				<div className="col-md-6">
					<form onSubmit={handleSubmit}>
						<h2>{heading}</h2>
						<h6 className="mb-5">
							Don't have an account yet? <Link to="/register">Create now.</Link>
						</h6>

						{sessionExpired && (
							<div className="alert alert-warning">Session expired, please login again</div>
						)}

						{error && <div className="alert alert-danger">{error}</div>}

						<label className="mt-3" htmlFor="email">
							Email:
						</label>
						<input
							className="form-control"
							id="email"
							name="email"
							type="email"
							required
							autoComplete="email"
						/>

						<label className="mt-3" htmlFor="password">
							Password:
						</label>
						<input
							className="form-control"
							id="password"
							name="password"
							type="password"
							required
							autoComplete="current-password"
						/>

						<button
							type="submit"
							className="btn btn-outline-primary col-md-3 brand-button mt-5"
							disabled={isLoading}
						>
							{isLoading ? (
								<span className="spinner-border spinner-border-sm" role="status" aria-hidden="true" />
							) : (
								buttonText
							)}
						</button>
					</form>
				</div>
			</div>
		</div>
	);
};

export default LoginForm;
