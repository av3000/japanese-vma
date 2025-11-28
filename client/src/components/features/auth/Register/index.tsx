import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';

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
				passwordConfirmation: formData.get('password_confirmation') as string,
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
		<div className="container">
			<div className="row justify-content-md-center text-center mt-5">
				<div className="col-md-6">
					<form onSubmit={handleSubmit}>
						<h2>{heading}</h2>
						<h6 className="mb-5">
							Already have an account? <Link to="/login">Login.</Link>
						</h6>

						{error && <div className="alert alert-danger">{error}</div>}

						<label className="mt-3" htmlFor="name">
							Username:
						</label>
						<input
							className="form-control"
							id="name"
							name="name"
							type="text"
							required
							autoComplete="username"
						/>

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
							autoComplete="new-password"
						/>

						<label className="mt-3" htmlFor="password_confirmation">
							Confirm password:
						</label>
						<input
							className="form-control"
							id="password_confirmation"
							name="password_confirmation"
							type="password"
							required
							autoComplete="new-password"
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

export default RegisterForm;
