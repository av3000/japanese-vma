// @ts-nocheck
/* eslint-disable */
import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';

const RegisterForm: React.FC = ({ onAuth, heading, buttonText, errors, removeError }) => {
	const [formData, setFormData] = useState({
		name: '',
		email: '',
		password: '',
		password_confirmation: '',
	});
	const [isLoading, setIsLoading] = useState(false);
	const navigate = useNavigate();

	useEffect(() => {
		return () => {
			removeError();
		};
	}, [removeError]);

	const handleSubmit = async (e) => {
		e.preventDefault();
		setIsLoading(true);

		try {
			await onAuth('register', formData);
			setIsLoading(false);
			navigate('/');
		} catch (error) {
			console.log(error);
			setIsLoading(false);
		}
	};

	const handleChange = (e) => {
		setFormData({
			...formData,
			[e.target.name]: e.target.value,
		});
	};

	return (
		<div className="container">
			<div className="row justify-content-md-center text-center mt-5">
				<div className="col-md-6">
					<form onSubmit={handleSubmit}>
						<h2>{heading}</h2>
						<h6 className="mb-5">
							Already have an account? <Link to="/login">Login.</Link>{' '}
						</h6>
						{errors.message && (
							<div className="alert alert-danger">
								{errors.message} <pre>{JSON.stringify(errors, null, 2)}</pre>
							</div>
						)}
						<label className="mt-3" htmlFor="email">
							Email:
						</label>
						<input
							className="form-control"
							id="email"
							name="email"
							onChange={handleChange}
							value={formData.email}
							type="text"
						/>
						<label className="mt-3" htmlFor="password">
							Password:
						</label>
						<input
							className="form-control"
							id="password"
							name="password"
							onChange={handleChange}
							type="password"
						/>
						<label className="mt-3" htmlFor="password_confirmation">
							Confirm password:
						</label>
						<input
							className="form-control"
							id="password_confirmation"
							name="password_confirmation"
							onChange={handleChange}
							type="password"
						/>
						<label className="mt-3" htmlFor="name">
							Username:
						</label>
						<input
							className="form-control"
							id="name"
							name="name"
							onChange={handleChange}
							value={formData.name}
							type="text"
						/>
						<button
							type="submit"
							className="btn btn-outline-primary col-md-3 brand-button mt-5"
							disabled={isLoading}
						>
							{isLoading ? (
								<span
									className="spinner-border spinner-border-sm"
									role="status"
									aria-hidden="true"
								></span>
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
